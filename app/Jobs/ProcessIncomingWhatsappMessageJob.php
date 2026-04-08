<?php

namespace App\Jobs;

use App\Enums\InvitationDispatchKind;
use App\Enums\InvitationDispatchStatus;
use App\Enums\ParsedIntent;
use App\Models\Guest;
use App\Models\GuestResponse;
use App\Models\InvitationDispatch;
use App\Models\WebhookReceipt;
use App\Services\GuestStateMachine;
use App\Services\PhoneNormalizer;
use App\Services\ReminderScheduler;
use App\Services\WhatsappGateway;
use App\Services\WhatsappResponseParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessIncomingWhatsappMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $receiptId)
    {
        $this->onQueue('high');
    }

    public function handle(
        PhoneNormalizer $phoneNormalizer,
        WhatsappResponseParser $parser,
        GuestStateMachine $stateMachine,
        ReminderScheduler $reminderScheduler,
        WhatsappGateway $gateway,
    ): void {
        $receipt = WebhookReceipt::findOrFail($this->receiptId);
        $payload = $receipt->payload_json ?? [];

        if (($payload['fromMe'] ?? false) === true) {
            $receipt->forceFill(['processed_at' => now()])->save();

            return;
        }

        $normalizedPhone = $phoneNormalizer->normalize((string) ($payload['phone'] ?? ''));
        $messageId = (string) ($payload['messageId'] ?? $receipt->external_id ?? '');
        $messageText = $payload['buttonsResponseMessage']['buttonId']
            ?? $payload['text']['message']
            ?? $payload['message']
            ?? $payload['body']
            ?? '';

        $guest = $this->resolveGuest($payload, $normalizedPhone, $parser);

        if (! $guest || $messageId === '') {
            $receipt->forceFill(['processed_at' => now()])->save();

            return;
        }

        if (GuestResponse::where('inbound_message_id', $messageId)->exists()) {
            $receipt->forceFill(['processed_at' => now()])->save();

            return;
        }

        $parsed = $parser->parsePayload($payload, $guest->current_status);

        GuestResponse::create([
            'event_id' => $guest->event_id,
            'guest_id' => $guest->id,
            'inbound_message_id' => $messageId,
            'raw_message' => (string) $messageText,
            'parsed_intent' => $parsed->intent,
            'companions_count' => $parsed->companionsCount,
            'source_payload_json' => $payload,
            'received_at' => now(),
        ]);

        $transition = $stateMachine->transition($guest->current_status, $parsed);

        $guest->forceFill([
            'current_status' => $transition->status,
            'companions_count' => $transition->companionsCount,
            'last_response_at' => now(),
        ])->save();

        if ($transition->scheduleReminder) {
            $reminderScheduler->scheduleUndecidedReminder($guest->event, $guest);
        }

        if ($transition->cancelReminder) {
            $reminderScheduler->cancelPendingReminders($guest);
        }

        if ($transition->autoReply) {
            $dispatch = InvitationDispatch::create([
                'event_id' => $guest->event_id,
                'guest_id' => $guest->id,
                'kind' => InvitationDispatchKind::Followup,
                'message_type' => 'text',
                'outbound_message' => $transition->autoReply,
                'delivery_status' => InvitationDispatchStatus::Pending,
            ]);

            $result = $gateway->sendText($guest->normalized_phone, $transition->autoReply);

            $dispatch->forceFill([
                'delivery_status' => $result->successful ? InvitationDispatchStatus::Sent : InvitationDispatchStatus::Failed,
                'provider_message_id' => $result->providerMessageId ?: null,
                'provider_zaap_id' => $result->providerZaapId,
                'sent_at' => $result->successful ? now() : null,
                'failure_reason' => $result->failureReason,
                'payload_json' => $result->payload,
            ])->save();
        }

        $receipt->forceFill(['processed_at' => now()])->save();
    }

    protected function resolveGuest(array $payload, string $normalizedPhone, WhatsappResponseParser $parser): ?Guest
    {
        $buttonContext = $parser->extractButtonContext($payload);

        if ($buttonContext) {
            $guest = Guest::with('event')
                ->whereKey($buttonContext['guest_id'])
                ->where('event_id', $buttonContext['event_id'])
                ->first();

            if (! $guest) {
                return null;
            }

            if ($normalizedPhone !== '' && $guest->normalized_phone !== $normalizedPhone) {
                logger()->warning('Webhook button context phone mismatch.', [
                    'guest_id' => $guest->id,
                    'event_id' => $guest->event_id,
                    'expected_phone' => $guest->normalized_phone,
                    'received_phone' => $normalizedPhone,
                    'button_id' => $payload['buttonsResponseMessage']['buttonId'] ?? null,
                ]);
            }

            return $guest;
        }

        return Guest::with('event')
            ->where('normalized_phone', $normalizedPhone)
            ->latest('id')
            ->first();
    }
}
