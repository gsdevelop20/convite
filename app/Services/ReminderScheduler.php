<?php

namespace App\Services;

use App\Enums\ReminderReason;
use App\Enums\ReminderStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Models\ReminderSchedule;
use Carbon\Carbon;

class ReminderScheduler
{
    public function scheduleUndecidedReminder(Event $event, Guest $guest): ReminderSchedule
    {
        $scheduledFor = Carbon::parse($event->event_date)
            ->subDays($event->reminder_days_before)
            ->setTime(9, 0);

        if ($scheduledFor->isPast()) {
            $scheduledFor = now()->addMinute();
        }

        $reminder = ReminderSchedule::updateOrCreate(
            [
                'event_id' => $event->id,
                'guest_id' => $guest->id,
                'reason' => ReminderReason::UndecidedFollowup->value,
            ],
            [
                'scheduled_for' => $scheduledFor,
                'status' => ReminderStatus::Pending->value,
                'processed_at' => null,
            ],
        );

        $guest->forceFill([
            'reminder_scheduled_at' => $scheduledFor,
        ])->save();

        return $reminder;
    }

    public function cancelPendingReminders(Guest $guest): void
    {
        $guest->reminderSchedules()
            ->where('status', ReminderStatus::Pending->value)
            ->update([
                'status' => ReminderStatus::Cancelled->value,
                'processed_at' => now(),
            ]);

        $guest->forceFill([
            'reminder_scheduled_at' => null,
        ])->save();
    }
}
