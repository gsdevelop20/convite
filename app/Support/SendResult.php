<?php

namespace App\Support;

class SendResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly ?string $providerMessageId = null,
        public readonly ?string $providerZaapId = null,
        public readonly ?string $failureReason = null,
        public readonly array $payload = [],
    ) {
    }
}
