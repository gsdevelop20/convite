<?php

namespace App\Services;

use App\Enums\GuestStatus;
use App\Enums\ParsedIntent;
use App\Support\GuestStateTransition;
use App\Support\ParsedWhatsappMessage;

class GuestStateMachine
{
    public function transition(GuestStatus $currentStatus, ParsedWhatsappMessage $message): GuestStateTransition
    {
        return match ($message->intent) {
            ParsedIntent::Confirmed => new GuestStateTransition(
                status: GuestStatus::Confirmed,
                autoReply: 'Presenca confirmada.',
                cancelReminder: true,
            ),
            ParsedIntent::Declined => new GuestStateTransition(
                status: GuestStatus::Declined,
                autoReply: 'Tudo bem. Obrigado por responder.',
                cancelReminder: true,
            ),
            ParsedIntent::Undecided => new GuestStateTransition(
                status: GuestStatus::Undecided,
                autoReply: 'Vou te lembrar novamente 15 dias antes do evento.',
                scheduleReminder: true,
            ),
            ParsedIntent::WaitingCompanionCount => new GuestStateTransition(
                status: GuestStatus::WaitingCompanionCount,
                autoReply: 'Quantos acompanhantes irao com voce?',
            ),
            ParsedIntent::CompanionCount => $this->resolveCompanionCount($message->companionsCount ?? 0),
            default => new GuestStateTransition(
                status: $currentStatus,
                autoReply: 'Nao entendi sua resposta. Responda com 1, 2, 3 ou 4.',
            ),
        };
    }

    protected function resolveCompanionCount(int $companionsCount): GuestStateTransition
    {
        if ($companionsCount <= 0) {
            return new GuestStateTransition(
                status: GuestStatus::Confirmed,
                companionsCount: 0,
                autoReply: 'Presenca confirmada.',
                cancelReminder: true,
            );
        }

        return new GuestStateTransition(
            status: GuestStatus::ConfirmedWithCompanion,
            companionsCount: $companionsCount,
            autoReply: "Presenca confirmada com {$companionsCount} acompanhante(s).",
            cancelReminder: true,
        );
    }
}
