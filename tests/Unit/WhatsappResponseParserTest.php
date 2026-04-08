<?php

namespace Tests\Unit;

use App\Enums\GuestStatus;
use App\Enums\ParsedIntent;
use App\Services\WhatsappResponseParser;
use Tests\TestCase;

class WhatsappResponseParserTest extends TestCase
{
    public function test_it_parses_button_response_ids(): void
    {
        $parser = new WhatsappResponseParser();

        $parsed = $parser->parsePayload([
            'buttonsResponseMessage' => [
                'buttonId' => 'evt:15:guest:88:with_companion',
                'message' => 'Vou com acompanhante',
            ],
        ], GuestStatus::Pending);

        $this->assertSame(ParsedIntent::WaitingCompanionCount, $parsed->intent);
        $this->assertSame('evt:15:guest:88:with_companion', $parsed->normalizedText);
        $this->assertSame([
            'event_id' => 15,
            'guest_id' => 88,
            'action' => 'with_companion',
        ], $parser->extractButtonContext([
            'buttonsResponseMessage' => [
                'buttonId' => 'evt:15:guest:88:with_companion',
            ],
        ]));
    }

    public function test_it_parses_companion_count_button_response_ids(): void
    {
        $parser = new WhatsappResponseParser();

        $parsed = $parser->parsePayload([
            'buttonsResponseMessage' => [
                'buttonId' => 'evt:15:guest:88:companions:4',
                'message' => '4',
            ],
        ], GuestStatus::WaitingCompanionCount);

        $this->assertSame(ParsedIntent::CompanionCount, $parsed->intent);
        $this->assertSame(4, $parsed->companionsCount);
        $this->assertSame([
            'event_id' => 15,
            'guest_id' => 88,
            'action' => 'companions',
            'companions_count' => 4,
        ], $parser->extractButtonContext([
            'buttonsResponseMessage' => [
                'buttonId' => 'evt:15:guest:88:companions:4',
            ],
        ]));
    }

    public function test_it_keeps_companion_count_parsing_for_plain_text(): void
    {
        $parser = new WhatsappResponseParser();

        $parsed = $parser->parsePayload([
            'text' => [
                'message' => '2',
            ],
        ], GuestStatus::WaitingCompanionCount);

        $this->assertSame(ParsedIntent::CompanionCount, $parsed->intent);
        $this->assertSame(2, $parsed->companionsCount);
    }
}
