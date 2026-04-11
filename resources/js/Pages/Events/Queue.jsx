import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from 'primereact/button';
import { Card } from 'primereact/card';
import { Column } from 'primereact/column';
import { DataTable } from 'primereact/datatable';
import Layout from '@/Layouts/layout/layout.jsx';
import StatCard from '@/Components/events/StatCard.jsx';
import StatusTag from '@/Components/events/StatusTag.jsx';

const kindLabels = {
    initial_invite: 'Convite inicial',
    reminder: 'Lembrete',
    followup: 'Retorno automático',
};

const messageTypeLabels = {
    text: 'Texto',
    image: 'Imagem',
    pdf: 'PDF',
    document: 'Documento',
};

export default function EventQueue({ event, summary, dispatches }) {
    const safeEvent = event ?? {};
    const safeSummary = summary ?? { total: 0, pending: 0, sent: 0, failed: 0 };
    const safeDispatches = Array.isArray(dispatches) ? dispatches : [];

    return (
        <Layout>
            <Head title={`Fila - ${safeEvent.name ?? 'Evento'}`} />

            <div className="flex justify-content-between align-items-start mb-4 gap-3 flex-column md:flex-row">
                <div>
                    <h2 className="m-0">Fila de envio</h2>
                    <p className="text-600 mt-2 mb-0">
                        {safeEvent.name ?? 'Evento'} {safeEvent.event_date ? `• ${safeEvent.event_date}` : ''} {safeEvent.location_name ? `• ${safeEvent.location_name}` : ''}
                    </p>
                </div>
                <Link href={route('events.index')}>
                    <Button label="Voltar para eventos" icon="pi pi-arrow-left" outlined />
                </Link>
            </div>

            <div className="grid">
                <StatCard title="Total" value={safeSummary.total} subtitle="Mensagens registradas" icon="inbox" color="blue" />
                <StatCard title="Pendentes" value={safeSummary.pending} subtitle="Aguardando processamento" icon="clock" color="orange" />
                <StatCard title="Enviadas" value={safeSummary.sent} subtitle="Retorno de envio recebido" icon="send" color="green" />
                <StatCard title="Falhas" value={safeSummary.failed} subtitle="Precisam de revisão" icon="times-circle" color="red" />

                <div className="col-12">
                    <Card title="Histórico da fila">
                        <DataTable value={safeDispatches} paginator rows={15} stripedRows emptyMessage="Nenhuma mensagem na fila para este evento." sortField="created_at" sortOrder={-1}>
                            <Column field="guest_name" header="Convidado" />
                            <Column field="kind" header="Tipo" body={(row) => kindLabels[row.kind] ?? row.kind} />
                            <Column field="message_type" header="Mídia" body={(row) => messageTypeLabels[row.message_type] ?? row.message_type} />
                            <Column field="delivery_status" header="Status" body={(row) => <StatusTag value={row.delivery_status} />} />
                            <Column field="provider_message_id" header="Provider ID" />
                            <Column field="scheduled_for" header="Previsto para" />
                            <Column field="created_at" header="Criado em" />
                            <Column field="sent_at" header="Enviado em" />
                            <Column field="failure_reason" header="Falha" body={(row) => row.failure_reason || '-'} />
                        </DataTable>
                    </Card>
                </div>
            </div>
        </Layout>
    );
}
