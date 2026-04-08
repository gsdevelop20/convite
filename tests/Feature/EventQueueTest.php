<?php

namespace Tests\Feature;

use App\Enums\GuestStatus;
use App\Enums\InvitationDispatchKind;
use App\Enums\InvitationDispatchStatus;
use App\Jobs\SendInvitationJob;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
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
        $response->assertSee('guest_name');
        $response->assertSee('Ana', false);
    }

    public function test_it_enqueues_pending_and_undecided_guests_for_event_sending(): void
    {
        Queue::fake();

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

        $pendingGuest = $event->guests()->create([
            'name' => 'Ana',
            'phone_e164' => '+5561995706650',
            'normalized_phone' => '5561995706650',
            'current_status' => GuestStatus::Pending,
        ]);

        $undecidedGuest = $event->guests()->create([
            'name' => 'Bianca',
            'phone_e164' => '+5561995706659',
            'normalized_phone' => '5561995706659',
            'current_status' => GuestStatus::Undecided,
        ]);

        $event->guests()->create([
            'name' => 'Bruno',
            'phone_e164' => '+5561995706651',
            'normalized_phone' => '5561995706651',
            'current_status' => GuestStatus::Confirmed,
        ]);

        $event->guests()->create([
            'name' => 'Carla',
            'phone_e164' => '+5561995706652',
            'normalized_phone' => '5561995706652',
            'current_status' => GuestStatus::Declined,
        ]);

        $response = $this->actingAs($user)->post(route('events.invitations.send', $event));

        $response->assertRedirect();

        $this->assertDatabaseCount('invitation_dispatches', 2);
        $this->assertDatabaseHas('invitation_dispatches', [
            'guest_id' => $pendingGuest->id,
            'event_id' => $event->id,
        ]);
        $this->assertDatabaseHas('invitation_dispatches', [
            'guest_id' => $undecidedGuest->id,
            'event_id' => $event->id,
        ]);

        Queue::assertPushed(SendInvitationJob::class, 2);
    }
}
