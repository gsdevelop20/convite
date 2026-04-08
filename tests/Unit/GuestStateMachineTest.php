<?php

namespace Tests\Unit;

use App\Enums\GuestStatus;
use App\Enums\ParsedIntent;
use App\Services\GuestStateMachine;
use App\Support\ParsedWhatsappMessage;
use Tests\TestCase;

class GuestStateMachineTest extends TestCase
{
    public function test_it_moves_guest_to_waiting_companion_count(): void
    {
        $machine = new GuestStateMachine();

        $transition = $machine->transition(
            GuestStatus::Pending,
            new ParsedWhatsappMessage(ParsedIntent::WaitingCompanionCount, normalizedText: '4'),
        );

        $this->assertSame(GuestStatus::WaitingCompanionCount, $transition->status);
        $this->assertSame('Quantos acompanhantes irao com voce?', $transition->autoReply);
    }

    public function test_it_confirms_guest_with_children(): void
    {
        $machine = new GuestStateMachine();

        $transition = $machine->transition(
            GuestStatus::Pending,
            new ParsedWhatsappMessage(ParsedIntent::WithChildren, normalizedText: 'vou com crianças'),
        );

        $this->assertSame(GuestStatus::Confirmed, $transition->status);
        $this->assertSame(1, $transition->companionsCount);
        $this->assertSame('Presença confirmada com crianças.', $transition->autoReply);
    }

    public function test_it_normalizes_zero_companions_to_confirmed(): void
    {
        $machine = new GuestStateMachine();

        $transition = $machine->transition(
            GuestStatus::WaitingCompanionCount,
            new ParsedWhatsappMessage(ParsedIntent::CompanionCount, companionsCount: 0, normalizedText: '0'),
        );

        $this->assertSame(GuestStatus::Confirmed, $transition->status);
        $this->assertSame(0, $transition->companionsCount);
    }
}
