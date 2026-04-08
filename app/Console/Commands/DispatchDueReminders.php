<?php

namespace App\Console\Commands;

use App\Enums\ReminderStatus;
use App\Jobs\SendReminderJob;
use App\Models\ReminderSchedule;
use Illuminate\Console\Command;

class DispatchDueReminders extends Command
{
    protected $signature = 'reminders:dispatch-due';

    protected $description = 'Despacha lembretes pendentes que ja estao vencidos.';

    public function handle(): int
    {
        ReminderSchedule::query()
            ->where('status', ReminderStatus::Pending->value)
            ->where('scheduled_for', '<=', now())
            ->orderBy('scheduled_for')
            ->each(function (ReminderSchedule $schedule): void {
                SendReminderJob::dispatch($schedule->id);
            });

        return self::SUCCESS;
    }
}
