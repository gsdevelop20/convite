<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Guest;

class InvitationMessageBuilder
{
    public function build(Event $event, Guest $guest, bool $isReminder = false): string
    {
        $message = str($event->invitation_message_template)
            ->replace(':guest_name', $guest->name)
            ->replace(':event_name', $event->name)
            ->replace(':host_name', $event->host_name)
            ->replace(':event_date', $event->event_date->format('d/m/Y'))
            ->replace(':location_name', $event->location_name)
            ->replace(':location_address', $event->location_address)
            ->toString();

        if ($isReminder) {
            $message .= "\n\nLembrete: ainda estamos aguardando sua confirmacao.";
        }

        return $message;
    }
}
