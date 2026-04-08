<?php

namespace Tests\Feature;

use App\Enums\InvitationDispatchKind;
use App\Enums\InvitationDispatchStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_event_queue_page(): void
    {
        $user = User::factory()->create();

        $event = Event::query()->create([
            'name' => 'Festa',
            'host_name' => 'Gabriel',
            'description' => 'Teste',
            'event_date' => '2026-12-01',
            'start_time' => '20:00',
            'location_name' => 'Buffet Central',
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
            'current_status' => 'pending',
        ]);

        $event->invitationDispatches()->create([
            'guest_id' => $guest->id,
            'kind' => InvitationDispatchKind::InitialInvite,
            'message_type' => 'text',
            'delivery_status' => InvitationDispatchStatus::Pending,
        ]);

        $response = $this->actingAs($user)->get(route('events.queue', $event));

        $response->assertOk();
        $response->assertSee('Events/Queue');
        $response->assertSee('Ana');
    }
}
