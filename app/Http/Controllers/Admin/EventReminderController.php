<?php

namespace App\Http\Controllers\Admin;

use App\Jobs\SendReminderJob;
use App\Http\Controllers\Controller;
use App\Models\Event;

class EventReminderController extends Controller
{
    public function store(Event $event)
    {
        $event->reminderSchedules()
            ->where('status', 'pending')
            ->each(fn ($schedule) => SendReminderJob::dispatch($schedule->id));

        return back()->with('success', 'Lembretes pendentes colocados na fila.');
    }
}
