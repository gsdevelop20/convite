<?php

namespace App\Support;

use App\Enums\GuestStatus;

class GuestStateTransition
{
    public function __construct(
        public readonly GuestStatus $status,
        public readonly int $companionsCount = 0,
        public readonly ?string $autoReply = null,
        public readonly bool $scheduleReminder = false,
        public readonly bool $cancelReminder = false,
    ) {
    }
}
