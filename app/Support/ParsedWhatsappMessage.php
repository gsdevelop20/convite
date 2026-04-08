<?php

namespace App\Support;

use App\Enums\ParsedIntent;

class ParsedWhatsappMessage
{
    public function __construct(
        public readonly ParsedIntent $intent,
        public readonly ?int $companionsCount = null,
        public readonly ?string $normalizedText = null,
    ) {
    }
}
