<?php

namespace App\Http\Controllers;

use App\Enums\GuestStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Models\ReminderSchedule;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $events = Event::query()
            ->withCount([
                'guests',
                'guests as pending_guests_count' => fn ($query) => $query->where('current_status', GuestStatus::Pending->value),
                'guests as confirmed_guests_count' => fn ($query) => $query->whereIn('current_status', [
                    GuestStatus::Confirmed->value,
                    GuestStatus::ConfirmedWithCompanion->value,
                ]),
                'guests as declined_guests_count' => fn ($query) => $query->where('current_status', GuestStatus::Declined->value),
                'guests as undecided_guests_count' => fn ($query) => $query->where('current_status', GuestStatus::Undecided->value),
            ])
            ->latest()
            ->take(8)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'events' => Event::count(),
                'guests' => Guest::count(),
                'confirmed' => Guest::whereIn('current_status', [
                    GuestStatus::Confirmed->value,
                    GuestStatus::ConfirmedWithCompanion->value,
                ])->count(),
                'pendingReminders' => ReminderSchedule::where('status', 'pending')->count(),
            ],
            'rsvpByEvent' => $events->map(fn (Event $event) => [
                'id' => $event->id,
                'name' => $event->name,
                'event_date' => optional($event->event_date)->format('d/m/Y'),
                'guests_count' => $event->guests_count,
                'pending_count' => $event->pending_guests_count,
                'confirmed_count' => $event->confirmed_guests_count,
                'declined_count' => $event->declined_guests_count,
                'undecided_count' => $event->undecided_guests_count,
            ]),
            'recentEvents' => $events->map(fn (Event $event) => [
                'id' => $event->id,
                'name' => $event->name,
                'host_name' => $event->host_name,
                'event_date' => optional($event->event_date)->format('Y-m-d'),
                'status' => $event->status->value,
                'guests_count' => $event->guests_count,
            ]),
        ]);
    }
}
