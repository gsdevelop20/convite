<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GuestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Events\StoreEventRequest;
use App\Models\Event;
use App\Models\InvitationDispatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        $events = Event::query()
            ->with(['guests' => fn ($query) => $query->latest()])
            ->withCount('guests')
            ->latest()
            ->get();

        return Inertia::render('Events/Index', [
            'events' => $events->map(fn (Event $event) => [
                'id' => $event->id,
                'name' => $event->name,
                'host_name' => $event->host_name,
                'description' => $event->description,
                'event_date' => optional($event->event_date)->format('Y-m-d'),
                'start_time' => $this->normalizeStartTime($event->start_time),
                'location_name' => $event->location_name,
                'location_address' => $event->location_address,
                'invitation_message_template' => $event->invitation_message_template,
                'invitation_asset_type' => $event->invitation_asset_type->value,
                'invitation_asset_url' => $event->invitation_asset_url,
                'status' => $event->status->value,
                'reminder_days_before' => $event->reminder_days_before,
                'guests_count' => $event->guests_count,
                'guests' => $event->guests->map(fn ($guest) => [
                    'id' => $guest->id,
                    'name' => $guest->name,
                    'phone_e164' => $guest->phone_e164,
                    'current_status' => $guest->current_status->value,
                    'companions_count' => $guest->companions_count,
                ]),
            ]),
        ]);
    }

    public function store(StoreEventRequest $request)
    {
        $event = Event::create($this->payloadFromRequest($request));

        return redirect()
            ->route('events.index')
            ->with('success', 'Evento criado com sucesso.');
    }

    public function update(StoreEventRequest $request, Event $event)
    {
        $event->update($this->payloadFromRequest($request, $event));

        return redirect()
            ->route('events.index')
            ->with('success', 'Evento atualizado com sucesso.');
    }

    public function show(Event $event): Response
    {
        $event->load([
            'guests' => fn ($query) => $query->latest(),
            'reminderSchedules' => fn ($query) => $query->with('guest')->latest()->take(10),
            'invitationDispatches' => fn ($query) => $query->with('guest')->latest()->take(10),
        ]);

        return Inertia::render('Events/Show', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'host_name' => $event->host_name,
                'description' => $event->description,
                'event_date' => optional($event->event_date)->format('Y-m-d'),
                'start_time' => $this->normalizeStartTime($event->start_time),
                'location_name' => $event->location_name,
                'location_address' => $event->location_address,
                'invitation_message_template' => $event->invitation_message_template,
                'invitation_asset_type' => $event->invitation_asset_type->value,
                'invitation_asset_url' => $event->invitation_asset_url,
                'status' => $event->status->value,
                'reminder_days_before' => $event->reminder_days_before,
            ],
            'summary' => [
                'total_guests' => $event->guests->count(),
                'confirmed' => $event->guests->filter(fn ($guest) => in_array($guest->current_status, [GuestStatus::Confirmed, GuestStatus::ConfirmedWithCompanion], true))->count(),
                'declined' => $event->guests->filter(fn ($guest) => $guest->current_status === GuestStatus::Declined)->count(),
                'undecided' => $event->guests->filter(fn ($guest) => $guest->current_status === GuestStatus::Undecided)->count(),
            ],
            'guests' => $event->guests->map(fn ($guest) => [
                'id' => $guest->id,
                'name' => $guest->name,
                'phone_e164' => $guest->phone_e164,
                'current_status' => $guest->current_status->value,
                'companions_count' => $guest->companions_count,
                'invited_at' => optional($guest->invited_at)->format('Y-m-d H:i'),
                'last_response_at' => optional($guest->last_response_at)->format('Y-m-d H:i'),
            ]),
            'dispatches' => $event->invitationDispatches->map(fn ($dispatch) => [
                'id' => $dispatch->id,
                'kind' => $dispatch->kind->value,
                'message_type' => $dispatch->message_type,
                'delivery_status' => $dispatch->delivery_status->value,
                'sent_at' => optional($dispatch->sent_at)->format('Y-m-d H:i'),
                'guest_name' => $dispatch->guest->name,
            ]),
            'reminders' => $event->reminderSchedules->map(fn ($reminder) => [
                'id' => $reminder->id,
                'status' => $reminder->status->value,
                'scheduled_for' => optional($reminder->scheduled_for)->format('Y-m-d H:i'),
                'guest_name' => $reminder->guest->name,
            ]),
        ]);
    }

    public function queue(Event $event): Response
    {
        $dispatches = InvitationDispatch::query()
            ->with('guest')
            ->where('event_id', $event->id)
            ->latest()
            ->get();

        return Inertia::render('Events/Queue', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'event_date' => optional($event->event_date)->format('Y-m-d'),
                'location_name' => $event->location_name,
            ],
            'summary' => [
                'total' => $dispatches->count(),
                'pending' => $dispatches->where('delivery_status', 'pending')->count(),
                'sent' => $dispatches->where('delivery_status', 'sent')->count(),
                'failed' => $dispatches->where('delivery_status', 'failed')->count(),
            ],
            'dispatches' => $dispatches->map(fn (InvitationDispatch $dispatch) => [
                'id' => $dispatch->id,
                'guest_name' => $dispatch->guest?->name ?? 'Convidado removido',
                'kind' => $dispatch->kind?->value ?? 'unknown',
                'message_type' => $dispatch->message_type,
                'delivery_status' => $dispatch->delivery_status?->value ?? 'pending',
                'provider_message_id' => $dispatch->provider_message_id,
                'scheduled_for' => optional($dispatch->scheduled_for)->format('Y-m-d H:i:s'),
                'sent_at' => optional($dispatch->sent_at)->format('Y-m-d H:i'),
                'created_at' => optional($dispatch->created_at)->format('Y-m-d H:i'),
                'failure_reason' => $dispatch->failure_reason,
            ]),
        ]);
    }

    protected function payloadFromRequest(StoreEventRequest $request, ?Event $event = null): array
    {
        $data = $request->safe()->except('invitation_asset_file');

        if ($request->input('invitation_asset_type') === 'text') {
            $this->deleteStoredInvitationAsset($event?->invitation_asset_url);
            $data['invitation_asset_url'] = null;

            return $data;
        }

        if ($request->hasFile('invitation_asset_file')) {
            $this->deleteStoredInvitationAsset($event?->invitation_asset_url);
            $path = $request->file('invitation_asset_file')->store('invitation-assets', 'public');
            $data['invitation_asset_url'] = Storage::disk('public')->url($path);

            return $data;
        }

        $data['invitation_asset_url'] = $event?->invitation_asset_url;

        return $data;
    }

    protected function normalizeStartTime(?string $startTime): ?string
    {
        if (blank($startTime)) {
            return null;
        }

        return Carbon::createFromFormat(str_contains($startTime, ':') && substr_count($startTime, ':') === 2 ? 'H:i:s' : 'H:i', $startTime)
            ->format('H:i');
    }

    protected function deleteStoredInvitationAsset(?string $assetUrl): void
    {
        if (blank($assetUrl)) {
            return;
        }

        $path = parse_url($assetUrl, PHP_URL_PATH);

        if (! is_string($path) || ! str_starts_with($path, '/storage/')) {
            return;
        }

        $path = ltrim(substr($path, strlen('/storage/')), '/');

        if ($path !== '') {
            Storage::disk('public')->delete($path);
        }
    }
}
