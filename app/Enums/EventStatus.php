<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Finished = 'finished';
    case Cancelled = 'cancelled';
}
