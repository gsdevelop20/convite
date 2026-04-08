<?php

namespace App\Services;

use App\Enums\GuestStatus;
use App\Enums\ParsedIntent;
use App\Support\ParsedWhatsappMessage;

class WhatsappResponseParser
{
    public function extractButtonContext(array $payload): ?array
    {
        $buttonId = $payload['buttonsResponseMessage']['buttonId'] ?? null;

        if (! is_string($buttonId) || $buttonId === '') {
            return null;
        }

        if (preg_match('/^evt:(\d+):guest:(\d+):(confirmed|declined|undecided|with_companion|with_children)$/', $buttonId, $matches) === 1) {
            return [
                'event_id' => (int) $matches[1],
                'guest_id' => (int) $matches[2],
                'action' => $matches[3],
            ];
        }

        if (preg_match('/^evt:(\d+):guest:(\d+):companions:(\d{1,2})$/', $buttonId, $matches) === 1) {
            return [
                'event_id' => (int) $matches[1],
                'guest_id' => (int) $matches[2],
                'action' => 'companions',
                'companions_count' => (int) $matches[3],
            ];
        }

        return null;
    }

    public function parsePayload(array $payload, GuestStatus $currentStatus): ParsedWhatsappMessage
    {
        $buttonId = $payload['buttonsResponseMessage']['buttonId'] ?? null;

        if (is_string($buttonId) && $buttonId !== '') {
            $buttonContext = $this->extractButtonContext($payload);
            $buttonAction = $buttonContext['action'] ?? $buttonId;

            return match ($buttonAction) {
                'confirmed' => new ParsedWhatsappMessage(ParsedIntent::Confirmed, normalizedText: $buttonId),
                'declined' => new ParsedWhatsappMessage(ParsedIntent::Declined, normalizedText: $buttonId),
                'undecided' => new ParsedWhatsappMessage(ParsedIntent::Undecided, normalizedText: $buttonId),
                'with_children' => new ParsedWhatsappMessage(ParsedIntent::WithChildren, normalizedText: $buttonId),
                'with_companion' => new ParsedWhatsappMessage(ParsedIntent::WaitingCompanionCount, normalizedText: $buttonId),
                'companions' => new ParsedWhatsappMessage(
                    ParsedIntent::CompanionCount,
                    companionsCount: $buttonContext['companions_count'] ?? 0,
                    normalizedText: $buttonId,
                ),
                default => $this->parse(
                    $payload['buttonsResponseMessage']['message'] ?? $payload['text']['message'] ?? $payload['message'] ?? $payload['body'] ?? '',
                    $currentStatus,
                ),
            };
        }

        return $this->parse(
            $payload['text']['message'] ?? $payload['message'] ?? $payload['body'] ?? '',
            $currentStatus,
        );
    }

    public function parse(?string $message, GuestStatus $currentStatus): ParsedWhatsappMessage
    {
        $normalized = trim(mb_strtolower((string) $message));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';

        if ($normalized === '') {
            return new ParsedWhatsappMessage(ParsedIntent::Unknown, normalizedText: $normalized);
        }

        if ($currentStatus === GuestStatus::WaitingCompanionCount && preg_match('/^\d+$/', $normalized)) {
            return new ParsedWhatsappMessage(
                ParsedIntent::CompanionCount,
                companionsCount: (int) $normalized,
                normalizedText: $normalized,
            );
        }

        return match (true) {
            in_array($normalized, ['1', '1.', '1 - vou', 'vou'], true) => new ParsedWhatsappMessage(ParsedIntent::Confirmed, normalizedText: $normalized),
            in_array($normalized, ['2', '2.', '2 - nao vou', 'não vou', 'nao vou'], true) => new ParsedWhatsappMessage(ParsedIntent::Declined, normalizedText: $normalized),
            in_array($normalized, ['3', '3.', '3 - ainda nao sei', '3 - ainda não sei', 'ainda nao sei', 'ainda não sei'], true) => new ParsedWhatsappMessage(ParsedIntent::Undecided, normalizedText: $normalized),
            in_array($normalized, ['vou com crianças', 'vou com criancas', 'com crianças', 'com criancas'], true) => new ParsedWhatsappMessage(ParsedIntent::WithChildren, normalizedText: $normalized),
            in_array($normalized, ['4', '4.', '4 - vou com acompanhante', 'vou com acompanhante'], true) => new ParsedWhatsappMessage(ParsedIntent::WaitingCompanionCount, normalizedText: $normalized),
            default => new ParsedWhatsappMessage(ParsedIntent::Unknown, normalizedText: $normalized),
        };
    }
}
