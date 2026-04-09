<?php

namespace Tests\Feature;

use App\Enums\GuestStatus;
use App\Jobs\ProcessIncomingWhatsappMessageJob;
use App\Models\Event;
use App\Models\GuestResponse;
use App\Models\WebhookReceipt;
use App\Services\WhatsappGateway;
use App\Support\SendResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessIncomingWebhookContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_button_context_updates_the_correct_event_when_phone_is_reused(): void
    {
        $firstEvent = Event::query()->create([
            'name' => 'Evento 1',
            'host_name' => 'Gabriel',
            'description' => 'Teste',
            'event_date' => '2026-12-01',
            'start_time' => '20:00',
            'location_name' => 'Buffet A',
            'location_address' => 'Rua A, 10',
            'invitation_message_template' => 'Mensagem',
            'invitation_asset_type' => 'text',
            'reminder_days_before' => 15,
            'status' => 'draft',
        ]);

        $secondEvent = Event::query()->create([
            'name' => 'Evento 2',
            'host_name' => 'Gabriel',
            'description' => 'Teste',
            'event_date' => '2026-12-10',
            'start_time' => '21:00',
            'location_name' => 'Buffet B',
            'location_address' => 'Rua B, 20',
            'invitation_message_template' => 'Mensagem',
            'invitation_asset_type' => 'text',
            'reminder_days_before' => 15,
            'status' => 'draft',
        ]);

        $firstGuest = $firstEvent->guests()->create([
            'name' => 'Ana',
            'phone_e164' => '+5561995706650',
            'normalized_phone' => '5561995706650',
            'current_status' => GuestStatus::Pending,
        ]);

        $secondGuest = $secondEvent->guests()->create([
            'name' => 'Ana',
            'phone_e164' => '+5561995706650',
            'normalized_phone' => '5561995706650',
            'current_status' => GuestStatus::Pending,
        ]);

        app()->instance(WhatsappGateway::class, new class implements WhatsappGateway
        {
            public function sendText(string $phone, string $message, array $options = []): SendResult
            {
                return new SendResult(successful: true, providerMessageId: 'reply-1');
            }

            public function sendButtonList(string $phone, string $message, array $buttons, array $options = []): SendResult
            {
                return new SendResult(successful: true, providerMessageId: 'buttons-1');
            }

            public function sendImage(string $phone, string $imageUrl, ?string $caption = null, array $options = []): SendResult
            {
                return new SendResult(successful: true, providerMessageId: 'image-1');
            }

            public function sendDocument(string $phone, string $documentUrl, string $extension, string $fileName, ?string $caption = null, array $options = []): SendResult
            {
                return new SendResult(successful: true, providerMessageId: 'doc-1');
            }
        });

        $receipt = WebhookReceipt::query()->create([
            'provider' => 'zapi',
            'event_type' => 'message_received',
            'external_id' => 'msg-123',
            'headers_json' => [],
            'payload_json' => [
                'phone' => '5561995706650',
                'messageId' => 'msg-123',
                'fromMe' => false,
                'buttonsResponseMessage' => [
                    'buttonId' => "evt:{$secondEvent->id}:guest:{$secondGuest->id}:confirmed",
                    'message' => 'Vou',
                ],
            ],
            'received_at' => now(),
        ]);

        (new ProcessIncomingWhatsappMessageJob($receipt->id))->handle(
            app(\App\Services\PhoneNormalizer::class),
            app(\App\Services\WhatsappResponseParser::class),
            app(\App\Services\GuestStateMachine::class),
            app(\App\Services\ReminderScheduler::class),
            app(WhatsappGateway::class),
        );

        $firstGuest->refresh();
        $secondGuest->refresh();

        $this->assertSame(GuestStatus::Pending, $firstGuest->current_status);
        $this->assertSame(GuestStatus::Confirmed, $secondGuest->current_status);
        $this->assertDatabaseHas(GuestResponse::class, [
            'guest_id' => $secondGuest->id,
            'event_id' => $secondEvent->id,
            'inbound_message_id' => 'msg-123',
        ]);
    }

    public function test_button_context_still_resolves_guest_when_phone_differs_in_payload(): void
    {
        $event = Event::query()->create([
            'name' => 'Evento 1',
            'host_name' => 'Gabriel',
            'description' => 'Teste',
            'event_date' => '2026-12-01',
            'start_time' => '20:00',
            'location_name' => 'Buffet A',
            'location_address' => 'Rua A, 10',
            'invitation_message_template' => 'Mensagem',
            'invitation_asset_type' => 'text',
            'reminder_days_before' => 15,
            'status' => 'draft',
        ]);

        $guest = $event->guests()->create([
            'name' => 'Ana',
            'phone_e164' => '+5561995706650',
            'normalized_phone' => '5561995706650',
            'current_status' => GuestStatus::Pending,
        ]);

        app()->instance(WhatsappGateway::class, new class implements WhatsappGateway
        {
            public function sendText(string $phone, string $message, array $options = []): SendResult
            {
                return new SendResult(successful: true, providerMessageId: 'reply-1');
            }

            public function sendButtonList(string $phone, string $message, array $buttons, array $options = []): SendResult
            {
                return new SendResult(successful: true, providerMessageId: 'buttons-1');
            }

            public function sendImage(string $phone, string $imageUrl, ?string $caption = null, array $options = []): SendResult
            {
                return new SendResult(successful: true, providerMessageId: 'image-1');
            }

            public function sendDocument(string $phone, string $documentUrl, string $extension, string $fileName, ?string $caption = null, array $options = []): SendResult
            {
                return new SendResult(successful: true, providerMessageId: 'doc-1');
            }
        });

        $receipt = WebhookReceipt::query()->create([
            'provider' => 'zapi',
            'event_type' => 'message_received',
            'external_id' => 'msg-456',
            'headers_json' => [],
            'payload_json' => [
                'phone' => '5561888888888',
                'messageId' => 'msg-456',
                'fromMe' => false,
                'buttonsResponseMessage' => [
                    'buttonId' => "evt:{$event->id}:guest:{$guest->id}:confirmed",
                    'message' => 'Vou',
                ],
            ],
            'received_at' => now(),
        ]);

        (new ProcessIncomingWhatsappMessageJob($receipt->id))->handle(
            app(\App\Services\PhoneNormalizer::class),
            app(\App\Services\WhatsappResponseParser::class),
            app(\App\Services\GuestStateMachine::class),
            app(\App\Services\ReminderScheduler::class),
            app(WhatsappGateway::class),
        );

        $guest->refresh();

        $this->assertSame(GuestStatus::Confirmed, $guest->current_status);
        $this->assertDatabaseHas(GuestResponse::class, [
            'guest_id' => $guest->id,
            'event_id' => $event->id,
            'inbound_message_id' => 'msg-456',
        ]);
    }

    public function test_with_companion_response_sends_companion_buttons_in_small_groups(): void
    {
        $event = Event::query()->create([
            'name' => 'Evento 1',
            'host_name' => 'Gabriel',
            'description' => 'Teste',
            'event_date' => '2026-12-01',
            'start_time' => '20:00',
            'location_name' => 'Buffet A',
            'location_address' => 'Rua A, 10',
            'invitation_message_template' => 'Mensagem',
            'invitation_asset_type' => 'text',
            'reminder_days_before' => 15,
            'status' => 'draft',
        ]);

        $guest = $event->guests()->create([
            'name' => 'Ana',
            'phone_e164' => '+5561995706650',
            'normalized_phone' => '5561995706650',
            'current_status' => GuestStatus::Pending,
        ]);

        $fakeGateway = new class implements WhatsappGateway
        {
            public array $buttonCalls = [];

            public function sendText(string $phone, string $message, array $options = []): SendResult
            {
                return new SendResult(successful: true, providerMessageId: 'reply-1');
            }

            public function sendButtonList(string $phone, string $message, array $buttons, array $options = []): SendResult
            {
                $this->buttonCalls[] = compact('phone', 'message', 'buttons', 'options');

                return new SendResult(successful: true, providerMessageId: 'buttons-1');
            }

            public function sendImage(string $phone, string $imageUrl, ?string $caption = null, array $options = []): SendResult
            {
                return new SendResult(successful: true, providerMessageId: 'image-1');
            }

            public function sendDocument(string $phone, string $documentUrl, string $extension, string $fileName, ?string $caption = null, array $options = []): SendResult
            {
                return new SendResult(successful: true, providerMessageId: 'doc-1');
            }

            public function sendLocation(string $phone, string $title, string $address, string $latitude, string $longitude, array $options = []): SendResult
            {
                return new SendResult(successful: true, providerMessageId: 'location');
            }
        };

        app()->instance(WhatsappGateway::class, $fakeGateway);

        $receipt = WebhookReceipt::query()->create([
            'provider' => 'zapi',
            'event_type' => 'message_received',
            'external_id' => 'msg-789',
            'headers_json' => [],
            'payload_json' => [
                'phone' => '5561995706650',
                'messageId' => 'msg-789',
                'fromMe' => false,
                'buttonsResponseMessage' => [
                    'buttonId' => "evt:{$event->id}:guest:{$guest->id}:with_companion",
                    'message' => 'Vou com acompanhante',
                ],
            ],
            'received_at' => now(),
        ]);

        (new ProcessIncomingWhatsappMessageJob($receipt->id))->handle(
            app(\App\Services\PhoneNormalizer::class),
            app(\App\Services\WhatsappResponseParser::class),
            app(\App\Services\GuestStateMachine::class),
            app(\App\Services\ReminderScheduler::class),
            $fakeGateway,
        );

        $guest->refresh();

        $this->assertSame(GuestStatus::WaitingCompanionCount, $guest->current_status);
        $this->assertCount(3, $fakeGateway->buttonCalls);
        $this->assertCount(4, $fakeGateway->buttonCalls[0]['buttons']);
        $this->assertCount(4, $fakeGateway->buttonCalls[1]['buttons']);
        $this->assertCount(2, $fakeGateway->buttonCalls[2]['buttons']);
        $this->assertSame('Quantos acompanhantes irao com voce?', $fakeGateway->buttonCalls[0]['message']);
        $this->assertSame('Mais opções de acompanhantes:', $fakeGateway->buttonCalls[1]['message']);
        $this->assertSame('Mais opções de acompanhantes:', $fakeGateway->buttonCalls[2]['message']);
        $this->assertSame("evt:{$event->id}:guest:{$guest->id}:companions:1", $fakeGateway->buttonCalls[0]['buttons'][0]['id']);
        $this->assertSame("evt:{$event->id}:guest:{$guest->id}:companions:10", $fakeGateway->buttonCalls[2]['buttons'][1]['id']);
    }
}
