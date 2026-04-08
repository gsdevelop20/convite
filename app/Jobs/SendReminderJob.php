<?php

namespace App\Jobs;

use App\Enums\InvitationDispatchKind;
use App\Enums\InvitationDispatchStatus;
use App\Enums\ReminderStatus;
use App\Models\InvitationDispatch;
use App\Models\ReminderSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendReminderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $reminderId)
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $reminder = ReminderSchedule::with(['event', 'guest'])->findOrFail($this->reminderId);

        $dispatch = InvitationDispatch::create([
            'event_id' => $reminder->event_id,
            'guest_id' => $reminder->guest_id,
            'kind' => InvitationDispatchKind::Reminder,
            'message_type' => $reminder->event->invitation_asset_type->value,
            'outbound_asset_url' => $reminder->event->invitation_asset_url,
            'delivery_status' => InvitationDispatchStatus::Pending,
        ]);

        SendInvitationJob::dispatch($dispatch->id);

        $reminder->forceFill([
            'status' => ReminderStatus::Sent,
            'processed_at' => now(),
        ])->save();
    }
}
