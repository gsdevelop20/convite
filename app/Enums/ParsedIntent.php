<?php

namespace App\Enums;

enum ParsedIntent: string
{
    case Confirmed = 'confirmed';
    case Declined = 'declined';
    case Undecided = 'undecided';
    case WithChildren = 'with_children';
    case WaitingCompanionCount = 'waiting_companion_count';
    case CompanionCount = 'companion_count';
    case Unknown = 'unknown';
    case Ignored = 'ignored';
}
