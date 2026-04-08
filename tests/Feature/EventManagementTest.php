<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_text_event(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('events.store'), [
            'name' => 'Festa Julina',
            'host_name' => 'Gabriel',
            'description' => 'Evento de teste',
            'event_date' => '2026-07-18',
            'start_time' => '19:30',
            'location_name' => 'Casa da Praia',
            'location_address' => 'Rua das Flores, 123',
            'invitation_message_template' => 'Ola :guest_name, venha para :event_name.',
            'invitation_asset_type' => 'text',
            'reminder_days_before' => 15,
            'status' => 'draft',
        ]);

        $response->assertRedirect(route('events.index'));

        $this->assertDatabaseHas(Event::class, [
            'name' => 'Festa Julina',
            'host_name' => 'Gabriel',
            'invitation_asset_type' => 'text',
        ]);
    }

    public function test_authenticated_user_can_update_text_event(): void
    {
        $user = User::factory()->create();

        $event = Event::query()->create([
            'name' => 'Festa Julina',
            'host_name' => 'Gabriel',
            'description' => 'Evento de teste',
            'event_date' => '2026-07-18',
            'start_time' => '19:30',
            'location_name' => 'Casa da Praia',
            'location_address' => 'Rua das Flores, 123',
            'invitation_message_template' => 'Ola :guest_name, venha para :event_name.',
            'invitation_asset_type' => 'text',
            'reminder_days_before' => 15,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->patch(route('events.update', $event), [
            'name' => 'Festa Atualizada',
            'host_name' => 'Gabriel',
            'description' => 'Evento atualizado',
            'event_date' => '2026-07-20',
            'start_time' => '20:00',
            'location_name' => 'Novo Local',
            'location_address' => 'Avenida Central, 999',
            'invitation_message_template' => 'Ola :guest_name, seu convite foi atualizado para :event_name.',
            'invitation_asset_type' => 'text',
            'reminder_days_before' => 10,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('events.index'));

        $this->assertDatabaseHas(Event::class, [
            'id' => $event->id,
            'name' => 'Festa Atualizada',
            'location_name' => 'Novo Local',
            'status' => 'active',
        ]);
    }

    public function test_update_accepts_start_time_with_seconds_from_existing_form_state(): void
    {
        $user = User::factory()->create();

        $event = Event::query()->create([
            'name' => 'Festa Julina',
            'host_name' => 'Gabriel',
            'description' => 'Evento de teste',
            'event_date' => '2026-07-18',
            'start_time' => '19:30:00',
            'location_name' => 'Casa da Praia',
            'location_address' => 'Rua das Flores, 123',
            'invitation_message_template' => 'Ola :guest_name, venha para :event_name.',
            'invitation_asset_type' => 'text',
            'reminder_days_before' => 15,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->patch(route('events.update', $event), [
            'name' => 'Festa Atualizada',
            'host_name' => 'Gabriel',
            'description' => 'Evento atualizado',
            'event_date' => '2026-07-20',
            'start_time' => '20:00:00',
            'location_name' => 'Novo Local',
            'location_address' => 'Avenida Central, 999',
            'invitation_message_template' => 'Ola :guest_name, seu convite foi atualizado para :event_name.',
            'invitation_asset_type' => 'text',
            'reminder_days_before' => 10,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('events.index'));

        $this->assertDatabaseHas(Event::class, [
            'id' => $event->id,
            'start_time' => '20:00',
        ]);
    }

    public function test_authenticated_user_can_change_invitation_type_from_image_to_text(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        Storage::disk('public')->put('invitation-assets/exemplo.png', 'fake-image');

        $event = Event::query()->create([
            'name' => 'Festa Julina',
            'host_name' => 'Gabriel',
            'description' => 'Evento de teste',
            'event_date' => '2026-07-18',
            'start_time' => '19:30',
            'location_name' => 'Casa da Praia',
            'location_address' => 'Rua das Flores, 123',
            'invitation_message_template' => 'Ola :guest_name, venha para :event_name.',
            'invitation_asset_type' => 'image',
            'invitation_asset_url' => Storage::disk('public')->url('invitation-assets/exemplo.png'),
            'reminder_days_before' => 15,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->patch(route('events.update', $event), [
            'name' => 'Festa Julina',
            'host_name' => 'Gabriel',
            'description' => 'Evento de teste',
            'event_date' => '2026-07-18',
            'start_time' => '19:30',
            'location_name' => 'Casa da Praia',
            'location_address' => 'Rua das Flores, 123',
            'invitation_message_template' => 'Ola :guest_name, venha para :event_name.',
            'invitation_asset_type' => 'text',
            'reminder_days_before' => 15,
            'status' => 'draft',
        ]);

        $response->assertRedirect(route('events.index'));

        $this->assertDatabaseHas(Event::class, [
            'id' => $event->id,
            'invitation_asset_type' => 'text',
            'invitation_asset_url' => null,
        ]);
        Storage::disk('public')->assertMissing('invitation-assets/exemplo.png');
    }

    public function test_authenticated_user_can_create_image_event_with_uploaded_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('events.store'), [
            'name' => 'Festa com Imagem',
            'host_name' => 'Gabriel',
            'description' => 'Evento com imagem',
            'event_date' => '2026-07-18',
            'start_time' => '19:30',
            'location_name' => 'Casa da Praia',
            'location_address' => 'Rua das Flores, 123',
            'invitation_message_template' => 'Olá :guest_name, venha para :event_name.',
            'invitation_asset_type' => 'image',
            'invitation_asset_file' => UploadedFile::fake()->image('convite.png'),
            'reminder_days_before' => 15,
            'status' => 'draft',
        ]);

        $response->assertRedirect(route('events.index'));

        $event = Event::query()->where('name', 'Festa com Imagem')->firstOrFail();

        $this->assertSame('image', $event->invitation_asset_type->value);
        $this->assertNotNull($event->invitation_asset_url);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $event->invitation_asset_url));
    }

    public function test_authenticated_user_can_update_pdf_event_with_uploaded_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $event = Event::query()->create([
            'name' => 'Festa PDF',
            'host_name' => 'Gabriel',
            'description' => 'Evento de teste',
            'event_date' => '2026-07-18',
            'start_time' => '19:30',
            'location_name' => 'Casa da Praia',
            'location_address' => 'Rua das Flores, 123',
            'invitation_message_template' => 'Ola :guest_name, venha para :event_name.',
            'invitation_asset_type' => 'text',
            'reminder_days_before' => 15,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->post(route('events.update', $event), [
            '_method' => 'patch',
            'name' => 'Festa PDF',
            'host_name' => 'Gabriel',
            'description' => 'Evento de teste',
            'event_date' => '2026-07-18',
            'start_time' => '19:30',
            'location_name' => 'Casa da Praia',
            'location_address' => 'Rua das Flores, 123',
            'invitation_message_template' => 'Ola :guest_name, venha para :event_name.',
            'invitation_asset_type' => 'pdf',
            'invitation_asset_file' => UploadedFile::fake()->create('convite.pdf', 120, 'application/pdf'),
            'reminder_days_before' => 15,
            'status' => 'draft',
        ]);

        $response->assertRedirect(route('events.index'));

        $event->refresh();

        $this->assertSame('pdf', $event->invitation_asset_type->value);
        $this->assertNotNull($event->invitation_asset_url);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $event->invitation_asset_url));
    }

    public function test_replacing_uploaded_asset_removes_previous_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        Storage::disk('public')->put('invitation-assets/antigo.pdf', 'old-pdf');

        $event = Event::query()->create([
            'name' => 'Festa PDF',
            'host_name' => 'Gabriel',
            'description' => 'Evento de teste',
            'event_date' => '2026-07-18',
            'start_time' => '19:30',
            'location_name' => 'Casa da Praia',
            'location_address' => 'Rua das Flores, 123',
            'invitation_message_template' => 'Ola :guest_name, venha para :event_name.',
            'invitation_asset_type' => 'pdf',
            'invitation_asset_url' => Storage::disk('public')->url('invitation-assets/antigo.pdf'),
            'reminder_days_before' => 15,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->post(route('events.update', $event), [
            '_method' => 'patch',
            'name' => 'Festa PDF',
            'host_name' => 'Gabriel',
            'description' => 'Evento de teste',
            'event_date' => '2026-07-18',
            'start_time' => '19:30',
            'location_name' => 'Casa da Praia',
            'location_address' => 'Rua das Flores, 123',
            'invitation_message_template' => 'Ola :guest_name, venha para :event_name.',
            'invitation_asset_type' => 'pdf',
            'invitation_asset_file' => UploadedFile::fake()->create('novo.pdf', 120, 'application/pdf'),
            'reminder_days_before' => 15,
            'status' => 'draft',
        ]);

        $response->assertRedirect(route('events.index'));

        $event->refresh();

        Storage::disk('public')->assertMissing('invitation-assets/antigo.pdf');
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $event->invitation_asset_url));
    }
}
