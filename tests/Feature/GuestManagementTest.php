<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_does_not_overwrite_existing_guest_when_phone_already_exists(): void
    {
        $user = User::factory()->create();
        $event = Event::query()->create([
            'name' => 'Casamento',
            'host_name' => 'Ana',
            'description' => 'Teste',
            'event_date' => '2026-10-10',
            'start_time' => '18:00',
            'location_name' => 'Salao Azul',
            'location_address' => 'Rua Central, 100',
            'invitation_message_template' => 'Mensagem',
            'invitation_asset_type' => 'text',
            'reminder_days_before' => 15,
            'status' => 'draft',
        ]);

        $event->guests()->create([
            'name' => 'Carlos',
            'phone_e164' => '+5511999999999',
            'normalized_phone' => '5511999999999',
            'current_status' => 'pending',
        ]);

        $response = $this->actingAs($user)->post(route('events.guests.store', $event), [
            'name' => 'Marina',
            'phone' => '(11) 99999-9999',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('guests', 1);
        $this->assertDatabaseHas('guests', [
            'name' => 'Carlos',
            'normalized_phone' => '5511999999999',
        ]);
    }

    public function test_it_requires_phone_in_numeric_pattern(): void
    {
        $user = User::factory()->create();
        $event = Event::query()->create([
            'name' => 'Casamento',
            'host_name' => 'Ana',
            'description' => 'Teste',
            'event_date' => '2026-10-10',
            'start_time' => '18:00',
            'location_name' => 'Salao Azul',
            'location_address' => 'Rua Central, 100',
            'invitation_message_template' => 'Mensagem',
            'invitation_asset_type' => 'text',
            'reminder_days_before' => 15,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->post(route('events.guests.store', $event), [
            'name' => 'Marina',
            'phone' => '(61) 99570-6650',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('guests', 0);
    }
}
