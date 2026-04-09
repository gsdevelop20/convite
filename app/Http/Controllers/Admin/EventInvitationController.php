<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GuestStatus;
use App\Enums\InvitationDispatchKind;
use App\Enums\InvitationDispatchStatus;
use App\Jobs\SendInvitationJob;
use App\Http\Controllers\Controller;
use App\Models\Event;

class EventInvitationController extends Controller
{
    public function store(Event $event)
    {
        $event->guests()
            ->whereIn('current_status', [
                GuestStatus::Pending->value,
                GuestStatus::Undecided->value,
            ])
            ->orderBy('id')
            ->get()
            ->values()
            ->each(function ($guest, int $index) use ($event): void {
                $dispatch = $guest->invitationDispatches()->create([
                    'event_id' => $event->id,
                    'kind' => InvitationDispatchKind::InitialInvite,
                    'message_type' => $event->invitation_asset_type->value,
                    'outbound_asset_url' => $event->invitation_asset_type->value === 'text' ? null : $event->invitation_asset_url,
                    'delivery_status' => InvitationDispatchStatus::Pending,
                ]);

                $batchDelayInMinutes = intdiv($index, 10) * 8;
                $positionInBatch = $index % 10;
                $secondsDelayWithinBatch = $this->secondsDelayWithinBatch($positionInBatch);
                $scheduledFor = now()->addMinutes($batchDelayInMinutes)->addSeconds($secondsDelayWithinBatch);

                $dispatch->forceFill([
                    'scheduled_for' => $scheduledFor,
                ])->save();

                SendInvitationJob::dispatch($dispatch->id)
                    ->delay($scheduledFor);
            });

        return back()->with('success', 'Envio dos convites colocado na fila em lotes de 10, com intervalo de 8 minutos e espaçamento de 10 a 30 segundos entre cada número.');
    }

    protected function secondsDelayWithinBatch(int $positionInBatch): int
    {
        $intervals = [10, 12, 15, 18, 20, 22, 24, 26, 28, 30];

        return collect($intervals)
            ->take($positionInBatch + 1)
            ->sum();
    }
}
