<?php

namespace Tests\Feature;

use App\Enums\InvitationDispatchKind;
use App\Enums\InvitationDispatchStatus;
use App\Jobs\SendInvitationJob;
use App\Models\Event;
use App\Services\WhatsappGateway;
use App\Support\SendResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendInvitationButtonsTest extends TestCase
{
    use RefreshDatabase;

    public function test_text_event_sends_rsvp_buttons_in_initial_invite(): void
    {
        $event = Event::query()->create([
            'name' => 'Festa',
            'host_name' => 'Gabriel',
            'description' => 'Teste',
            'event_date' => '2026-12-01',
            'start_time' => '20:00',
            'location_name' => 'Buffet Central',
            'location_address' => 'Rua A, 10',
            'invitation_message_template' => 'Ola :guest_name, confirme sua presenca.',
            'invitation_asset_type' => 'text',
            'reminder_days_before' => 15,
            'status' => 'draft',
        ]);

        $guest = $event->guests()->create([
            'name' => 'Ana',
            'phone_e164' => '+5561995706650',
            'normalized_phone' => '5561995706650',
            'current_status' => 'pending',
        ]);

        $dispatch = $event->invitationDispatches()->create([
            'guest_id' => $guest->id,
            'kind' => InvitationDispatchKind::InitialInvite,
            'message_type' => 'text',
            'delivery_status' => InvitationDispatchStatus::Pending,
        ]);

        $fakeGateway = new class implements WhatsappGateway
        {
            public array $buttonCalls = [];

            public function sendText(string $phone, string $message, array $options = []): SendResult
            {
                return new SendResult(successful: true, providerMessageId: 'text-1', payload: []);
            }

            public function sendButtonList(string $phone, string $message, array $buttons, array $options = []): SendResult
            {
                $this->buttonCalls[] = compact('phone', 'message', 'buttons', 'options');

                return new SendResult(successful: true, providerMessageId: 'button-1', payload: ['messageId' => 'button-1']);
            }

            public function sendImage(string $phone, string $imageUrl, ?string $caption = null, array $options = []): SendResult
            {
                return new SendResult(successful: true, providerMessageId: 'image-1', payload: []);
            }

            public function sendDocument(string $phone, string $documentUrl, string $extension, string $fileName, ?string $caption = null, array $options = []): SendResult
            {
                return new SendResult(successful: true, providerMessageId: 'doc-1', payload: []);
            }
        };

        app()->instance(WhatsappGateway::class, $fakeGateway);

        (new SendInvitationJob($dispatch->id))->handle(
            $fakeGateway,
            app(\App\Services\InvitationMessageBuilder::class),
        );

        $dispatch->refresh();

        $this->assertCount(1, $fakeGateway->buttonCalls);
        $this->assertSame('5561995706650', $fakeGateway->buttonCalls[0]['phone']);
        $this->assertCount(4, $fakeGateway->buttonCalls[0]['buttons']);
        $this->assertSame([
            "evt:{$event->id}:guest:{$guest->id}:confirmed",
            "evt:{$event->id}:guest:{$guest->id}:declined",
            "evt:{$event->id}:guest:{$guest->id}:undecided",
            "evt:{$event->id}:guest:{$guest->id}:with_companion",
        ], array_column($fakeGateway->buttonCalls[0]['buttons'], 'id'));
        $this->assertSame(InvitationDispatchStatus::Sent, $dispatch->delivery_status);
        $this->assertSame('button-1', $dispatch->provider_message_id);
    }
}
