import React from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Button } from 'primereact/button';
import { Card } from 'primereact/card';
import { Column } from 'primereact/column';
import { DataTable } from 'primereact/datatable';
import { InputText } from 'primereact/inputtext';
import { InputTextarea } from 'primereact/inputtextarea';
import Layout from '@/Layouts/layout/layout.jsx';
import StatCard from '@/Components/events/StatCard.jsx';
import StatusTag from '@/Components/events/StatusTag.jsx';

export default function EventShow({ event, summary, guests, dispatches, reminders }) {
    const guestForm = useForm({ name: '', phone: '' });
    const importForm = useForm({ rows: '' });
    const accompaniedBody = (row) => ((row.companions_count ?? 0) > 0 || row.current_status === 'confirmed_with_companion' ? 'Sim' : 'Não');

    const addGuest = (e) => {
        e.preventDefault();
        guestForm.post(route('events.guests.store', event.id), {
            preserveScroll: true,
            onSuccess: () => guestForm.reset(),
        });
    };

    const importGuests = (e) => {
        e.preventDefault();
        importForm.post(route('events.guests.import', event.id), {
            preserveScroll: true,
            onSuccess: () => importForm.reset(),
        });
    };

    return (
        <Layout>
            <Head title={event.name} />

            <div className="grid">
                <div className="col-12">
                    <div className="card">
                        <div className="flex justify-content-between align-items-start gap-4 flex-column xl:flex-row">
                            <div>
                                <div className="flex align-items-center gap-3 mb-3">
                                    <h2 className="m-0">{event.name}</h2>
                                    <StatusTag value={event.status} />
                                </div>
                                <p className="text-700 mt-0 mb-3">{event.description || 'Sem descrição cadastrada.'}</p>
                                <div className="text-600">
                                    {event.host_name} • {event.event_date} {event.start_time || ''} • {event.location_name}
                                </div>
                                <div className="text-600 mt-2">{event.location_address}</div>
                            </div>
                            <div className="flex gap-2 flex-wrap">
                                <Button
                                    label="Enviar convites"
                                    icon="pi pi-send"
                                    onClick={() => router.post(route('events.invitations.send', event.id), {}, { preserveScroll: true })}
                                />
                                <Button
                                    label="Disparar lembretes"
                                    icon="pi pi-bell"
                                    outlined
                                    onClick={() => router.post(route('events.reminders.send', event.id), {}, { preserveScroll: true })}
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <StatCard title="Convidados" value={summary.total_guests} subtitle="Base do evento" icon="users" color="blue" />
                <StatCard title="Confirmados" value={summary.confirmed} subtitle="Com ou sem acompanhante" icon="check" color="green" />
                <StatCard title="Recusados" value={summary.declined} subtitle="Não participarão" icon="times" color="red" />
                <StatCard title="Indecisos" value={summary.undecided} subtitle="Com lembrete pendente" icon="clock" color="orange" />

                <div className="col-12 xl:col-4">
                    <Card title="Adicionar convidado">
                        <form onSubmit={addGuest} className="flex flex-column gap-3">
                            <span className="p-float-label">
                                <InputText id="guest_name" value={guestForm.data.name} onChange={(e) => guestForm.setData('name', e.target.value)} className="w-full" />
                                <label htmlFor="guest_name">Nome</label>
                            </span>
                            <span className="p-float-label">
                                <InputText id="guest_phone" value={guestForm.data.phone} onChange={(e) => guestForm.setData('phone', e.target.value)} className="w-full" />
                                <label htmlFor="guest_phone">Telefone</label>
                            </span>
                            <Button type="submit" label="Salvar convidado" icon="pi pi-user-plus" loading={guestForm.processing} />
                        </form>
                    </Card>

                    <Card title="Importar em lote" subTitle="Uma linha por convidado: Nome;Telefone" className="mt-4">
                        <form onSubmit={importGuests} className="flex flex-column gap-3">
                            <InputTextarea rows={10} value={importForm.data.rows} onChange={(e) => importForm.setData('rows', e.target.value)} />
                            <Button type="submit" label="Importar convidados" icon="pi pi-upload" outlined loading={importForm.processing} />
                        </form>
                    </Card>
                </div>

                <div className="col-12 xl:col-8">
                    <Card title="Convidados">
                        <DataTable value={guests} paginator rows={10} stripedRows>
                            <Column field="id" header="Guest ID" />
                            <Column field="name" header="Nome" />
                            <Column field="phone_e164" header="Telefone" />
                            <Column field="current_status" header="Status" body={(row) => <StatusTag value={row.current_status} />} />
                            <Column header="Com crianças" body={accompaniedBody} />
                            <Column field="invited_at" header="Convite enviado" />
                            <Column field="last_response_at" header="Última resposta" />
                        </DataTable>
                    </Card>

                    <Card title="Histórico de envios" className="mt-4">
                        <DataTable value={dispatches} paginator rows={5} stripedRows>
                            <Column field="guest_name" header="Convidado" />
                            <Column field="kind" header="Tipo" />
                            <Column field="message_type" header="Mídia" />
                            <Column field="delivery_status" header="Status" body={(row) => <StatusTag value={row.delivery_status} />} />
                            <Column field="sent_at" header="Enviado em" />
                        </DataTable>
                    </Card>

                    <Card title="Lembretes agendados" className="mt-4">
                        <DataTable value={reminders} paginator rows={5} stripedRows>
                            <Column field="guest_name" header="Convidado" />
                            <Column field="scheduled_for" header="Agendado para" />
                            <Column field="status" header="Status" body={(row) => <StatusTag value={row.status} />} />
                        </DataTable>
                    </Card>
                </div>
            </div>
        </Layout>
    );
}
