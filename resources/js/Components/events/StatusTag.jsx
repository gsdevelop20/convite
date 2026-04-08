import React from 'react';
import { Tag } from 'primereact/tag';

const severityByStatus = {
    draft: 'secondary',
    scheduled: 'info',
    active: 'success',
    finished: 'contrast',
    cancelled: 'danger',
    pending: 'warning',
    confirmed: 'success',
    confirmed_with_companion: 'success',
    declined: 'danger',
    undecided: 'info',
    waiting_companion_count: 'warning',
    sent: 'info',
    delivered: 'success',
    read: 'success',
    failed: 'danger',
};

const labelByStatus = {
    draft: 'Rascunho',
    scheduled: 'Agendado',
    active: 'Ativo',
    finished: 'Finalizado',
    cancelled: 'Cancelado',
    pending: 'Pendente',
    confirmed: 'Confirmado',
    confirmed_with_companion: 'Com acompanhante',
    declined: 'Não vai',
    undecided: 'Ainda não sei',
    waiting_companion_count: 'Aguardando acompanhantes',
    sent: 'Enviado',
    delivered: 'Entregue',
    read: 'Lido',
    failed: 'Falhou',
};

export default function StatusTag({ value }) {
    return <Tag value={labelByStatus[value] ?? value} severity={severityByStatus[value] ?? 'secondary'} rounded />;
}
