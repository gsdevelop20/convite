<?php

namespace App\Jobs;

use App\Enums\InvitationAssetType;
use App\Enums\InvitationDispatchStatus;
use App\Models\InvitationDispatch;
use App\Services\InvitationMessageBuilder;
use App\Services\WhatsappGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SendInvitationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $dispatchId)
    {
        $this->onQueue('default');
    }

    public function handle(WhatsappGateway $gateway, InvitationMessageBuilder $messageBuilder): void
    {
        $dispatch = InvitationDispatch::with(['event', 'guest'])->findOrFail($this->dispatchId);

        if ($this->hasBeenSuperseded($dispatch)) {
            $dispatch->forceFill([
                'delivery_status' => InvitationDispatchStatus::Failed,
                'failure_reason' => 'Dispatch substituído por um envio mais recente.',
            ])->save();

            return;
        }

        $event = $dispatch->event;
        $guest = $dispatch->guest;
        $message = $dispatch->outbound_message ?: $messageBuilder->build($event, $guest, $dispatch->kind->value === 'reminder');
        $buttons = $this->rsvpButtons();
        $assetUrl = $this->resolveAssetUrl($dispatch);

        $result = match ($event->invitation_asset_type) {
            InvitationAssetType::Image => $this->sendAssetWithButtons(
                fn() => $gateway->sendImage(
                    $guest->normalized_phone,
                    $assetUrl,
                    $message,
                ),
                fn() => $event->send_location_pin
                    ? $gateway->sendLocation(
                        $guest->normalized_phone,
                        $event->location_name ?? 'Local do evento',
                        $event->location_address ?? 'Endereço não informado',
                        $event->location_latitude ?? '0',
                        $event->location_longitude ?? '0'
                    )
                    : null,
                fn() => $gateway->sendButtonList(
                    $guest->normalized_phone,
                    'Como deseja responder ao convite?',
                    $buttons,
                ),
            ),
            InvitationAssetType::Pdf => $this->sendAssetWithButtons(
                fn() => $gateway->sendDocument(
                    $guest->normalized_phone,
                    $assetUrl,
                    'pdf',
                    "convite-{$guest->id}.pdf",
                    $message,
                ),
                fn() => $event->send_location_pin
                    ? $gateway->sendLocation(
                        $guest->normalized_phone,
                        $event->location_name ?? 'Local do evento',
                        $event->location_address ?? 'Endereço não informado',
                        $event->location_latitude ?? '0',
                        $event->location_longitude ?? '0'
                    )
                    : null,
                fn() => $gateway->sendButtonList(
                    $guest->normalized_phone,
                    'Como deseja responder ao convite?',
                    $buttons,
                ),
            ),
            default => $gateway->sendButtonList($guest->normalized_phone, $message, $buttons),
        };

        $dispatch->forceFill([
            'outbound_message' => $message,
            'delivery_status' => $result->successful ? InvitationDispatchStatus::Sent : InvitationDispatchStatus::Failed,
            'provider_message_id' => $result->providerMessageId ?: null,
            'provider_zaap_id' => $result->providerZaapId,
            'sent_at' => $result->successful ? now() : null,
            'failure_reason' => $result->failureReason,
            'payload_json' => $result->payload,
        ])->save();

        if ($result->successful) {
            $guest->forceFill([
                'invited_at' => $dispatch->kind->value === 'initial_invite' ? now() : $guest->invited_at,
                'last_reminder_sent_at' => $dispatch->kind->value === 'reminder' ? now() : $guest->last_reminder_sent_at,
            ])->save();
        }
    }

    protected function rsvpButtons(): array
    {
        $dispatch = InvitationDispatch::with('guest')->findOrFail($this->dispatchId);

        return [
            ['id' => $this->buildButtonId($dispatch->event_id, $dispatch->guest_id, 'confirmed'), 'label' => 'Vou'],
            ['id' => $this->buildButtonId($dispatch->event_id, $dispatch->guest_id, 'declined'), 'label' => 'Não vou'],
            ['id' => $this->buildButtonId($dispatch->event_id, $dispatch->guest_id, 'undecided'), 'label' => 'Ainda não sei'],
            ['id' => $this->buildButtonId($dispatch->event_id, $dispatch->guest_id, 'with_children'), 'label' => 'Vou com crianças'],
        ];
    }

    protected function buildButtonId(int $eventId, int $guestId, string $action): string
    {
        return "evt:{$eventId}:guest:{$guestId}:{$action}";
    }

    protected function resolveAssetUrl(InvitationDispatch $dispatch): ?string
    {
        if ($dispatch->event->invitation_asset_type === InvitationAssetType::Text) {
            return null;
        }

        return Storage::disk('public')->url($dispatch->event->invitation_asset_url);
    }

    protected function hasBeenSuperseded(InvitationDispatch $dispatch): bool
    {
        return InvitationDispatch::query()
            ->where('guest_id', $dispatch->guest_id)
            ->where('event_id', $dispatch->event_id)
            ->where('kind', $dispatch->kind)
            ->where('id', '>', $dispatch->id)
            ->exists();
    }

    protected function sendAssetWithButtons(callable $sendAsset, ?callable $sendLocation, callable $sendButtons)
    {
        $assetResult = $sendAsset();

        if (!$assetResult->successful) {
            return $assetResult;
        }

        if (is_callable($sendLocation)) {
            $sendLocation();
        }

        $buttonResult = $sendButtons();

        if ($buttonResult->successful) {
            return $buttonResult;
        }

        return $assetResult;
    }
}
