<?php

namespace App\Enums;

enum InvitationDispatchKind: string
{
    case InitialInvite = 'initial_invite';
    case Reminder = 'reminder';
    case Followup = 'followup';
}
