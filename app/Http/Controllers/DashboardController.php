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
            ->withCount('guests')
            ->latest()
            ->take(5)
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
            'guestStatusBreakdown' => [
                ['label' => 'Pendentes', 'value' => Guest::where('current_status', GuestStatus::Pending->value)->count()],
                ['label' => 'Confirmados', 'value' => Guest::where('current_status', GuestStatus::Confirmed->value)->count()],
                ['label' => 'Com acompanhante', 'value' => Guest::where('current_status', GuestStatus::ConfirmedWithCompanion->value)->count()],
                ['label' => 'Recusados', 'value' => Guest::where('current_status', GuestStatus::Declined->value)->count()],
                ['label' => 'Indecisos', 'value' => Guest::where('current_status', GuestStatus::Undecided->value)->count()],
            ],
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
