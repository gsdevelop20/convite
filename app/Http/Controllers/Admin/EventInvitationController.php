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
            ->get()
            ->each(function ($guest) use ($event): void {
                $dispatch = $guest->invitationDispatches()->create([
                    'event_id' => $event->id,
                    'kind' => InvitationDispatchKind::InitialInvite,
                    'message_type' => $event->invitation_asset_type->value,
                    'outbound_asset_url' => $event->invitation_asset_url,
                    'delivery_status' => InvitationDispatchStatus::Pending,
                ]);

                SendInvitationJob::dispatch($dispatch->id);
            });

        return back()->with('success', 'Envio dos convites colocado na fila.');
    }
}
