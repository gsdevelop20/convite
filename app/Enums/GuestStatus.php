<?php

namespace App\Enums;

enum GuestStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Declined = 'declined';
    case Undecided = 'undecided';
    case ConfirmedWithCompanion = 'confirmed_with_companion';
    case WaitingCompanionCount = 'waiting_companion_count';
}
