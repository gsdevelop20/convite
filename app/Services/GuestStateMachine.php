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
                autoReply: 'Presença confirmada.',
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
            ParsedIntent::WithChildren => new GuestStateTransition(
                status: GuestStatus::Confirmed,
                companionsCount: 1,
                autoReply: 'Presença confirmada com crianças.',
                cancelReminder: true,
            ),
            ParsedIntent::WaitingCompanionCount => new GuestStateTransition(
                status: GuestStatus::WaitingCompanionCount,
                autoReply: 'Quantos acompanhantes irao com voce?',
            ),
            ParsedIntent::CompanionCount => $this->resolveCompanionCount($message->companionsCount ?? 0),
            default => new GuestStateTransition(
                status: $currentStatus,
                autoReply: 'Não entendi sua resposta. Use um dos botões da mensagem.',
            ),
        };
    }

    protected function resolveCompanionCount(int $companionsCount): GuestStateTransition
    {
        if ($companionsCount <= 0) {
            return new GuestStateTransition(
                status: GuestStatus::Confirmed,
                companionsCount: 0,
                autoReply: 'Presença confirmada.',
                cancelReminder: true,
            );
        }

        return new GuestStateTransition(
            status: GuestStatus::ConfirmedWithCompanion,
            companionsCount: $companionsCount,
            autoReply: "Presença confirmada com {$companionsCount} acompanhante(s).",
            cancelReminder: true,
        );
    }
}
