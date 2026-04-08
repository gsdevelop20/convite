import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from 'primereact/button';
import Layout from '@/Layouts/layout/layout.jsx';
import StatCard from '@/Components/events/StatCard.jsx';
import StatusTag from '@/Components/events/StatusTag.jsx';

export default function Dashboard({ stats, rsvpByEvent, recentEvents }) {
    return (
        <Layout>
            <Head title="Dashboard" />

            <div className="grid">
                <StatCard title="Eventos" value={stats.events} subtitle="Base total cadastrada" icon="calendar" color="blue" />
                <StatCard title="Convidados" value={stats.guests} subtitle="Todos os eventos" icon="users" color="orange" />
                <StatCard title="Confirmados" value={stats.confirmed} subtitle="Inclui acompanhantes" icon="check-circle" color="green" />
                <StatCard title="Lembretes" value={stats.pendingReminders} subtitle="Pendentes para envio" icon="send" color="purple" />

                <div className="col-12 xl:col-5">
                    <div className="card h-full">
                        <div className="flex flex-column md:flex-row md:justify-content-between md:align-items-center gap-3 mb-4">
                            <div className="min-w-0">
                                <h3 className="m-0 text-xl line-height-2">RSVP por evento</h3>
                                <p className="text-600 text-sm mt-2 mb-0 line-height-3">Situação atual de confirmação em cada festa.</p>
                            </div>
                            <Link href={route('events.index')} className="inline-flex flex-shrink-0">
                                <Button label="Ver eventos" icon="pi pi-arrow-right" outlined className="white-space-nowrap" />
                            </Link>
                        </div>

                        <div className="surface-border border-1 border-round overflow-hidden">
                            {rsvpByEvent.length === 0 ? (
                                <div className="p-4 text-600">Nenhum evento cadastrado ainda.</div>
                            ) : (
                                rsvpByEvent.map((event) => (
                                    <Link
                                        key={event.id}
                                        href={route('events.show', event.id)}
                                        className="block p-4 border-bottom-1 surface-border text-color no-underline"
                                    >
                                        <div className="flex flex-column md:flex-row md:justify-content-between md:align-items-start gap-3">
                                            <div className="min-w-0">
                                                <div className="font-semibold text-900 text-sm md:text-base line-height-3" style={{ overflowWrap: 'anywhere' }}>{event.name}</div>
                                                <div className="text-600 text-sm mt-1 line-height-3">
                                                    {event.event_date} • {event.guests_count} convidados
                                                </div>
                                            </div>
                                            <div className="md:text-right">
                                                <div className="text-green-600 font-semibold text-sm md:text-base line-height-3">{event.confirmed_count} confirmados</div>
                                                <div className="text-600 text-sm line-height-3">{event.pending_count} pendentes</div>
                                            </div>
                                        </div>

                                        <div className="grid mt-2">
                                            <div className="col-6">
                                                <div className="surface-100 border-round p-2">
                                                    <div className="text-500 text-sm line-height-3">Pendentes</div>
                                                    <div className="font-semibold text-sm md:text-base mt-1 line-height-3">{event.pending_count}</div>
                                                </div>
                                            </div>
                                            <div className="col-6">
                                                <div className="surface-100 border-round p-2">
                                                    <div className="text-500 text-sm line-height-3">Confirmados</div>
                                                    <div className="font-semibold text-sm md:text-base mt-1 line-height-3">{event.confirmed_count}</div>
                                                </div>
                                            </div>
                                            <div className="col-6">
                                                <div className="surface-100 border-round p-2">
                                                    <div className="text-500 text-sm line-height-3">Não vão</div>
                                                    <div className="font-semibold text-sm md:text-base mt-1 line-height-3">{event.declined_count}</div>
                                                </div>
                                            </div>
                                            <div className="col-6">
                                                <div className="surface-100 border-round p-2">
                                                    <div className="text-500 text-sm line-height-3">Ainda não sei</div>
                                                    <div className="font-semibold text-sm md:text-base mt-1 line-height-3">{event.undecided_count}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </Link>
                                ))
                            )}
                        </div>
                    </div>
                </div>

                <div className="col-12 xl:col-7">
                    <div className="card h-full">
                        <div className="flex flex-column md:flex-row md:justify-content-between md:align-items-center gap-3 mb-4">
                            <div className="min-w-0">
                                <h3 className="m-0 text-xl line-height-2">Eventos recentes</h3>
                                <p className="text-600 text-sm mt-2 mb-0 line-height-3">Acompanhe rapidamente o andamento de cada festa.</p>
                            </div>
                            <Link href={route('events.index')} className="inline-flex flex-shrink-0">
                                <Button label="Novo evento" icon="pi pi-plus" className="white-space-nowrap" />
                            </Link>
                        </div>

                        <div className="surface-border border-1 border-round overflow-hidden">
                            {recentEvents.length === 0 ? (
                                <div className="p-4 text-600">Nenhum evento cadastrado ainda.</div>
                            ) : (
                                recentEvents.map((event) => (
                                    <Link
                                        key={event.id}
                                        href={route('events.show', event.id)}
                                        className="flex flex-column md:flex-row md:align-items-center md:justify-content-between gap-3 p-4 border-bottom-1 surface-border text-color no-underline"
                                    >
                                        <div className="min-w-0">
                                            <div className="font-semibold text-900 text-sm md:text-base line-height-3" style={{ overflowWrap: 'anywhere' }}>{event.name}</div>
                                            <div className="text-600 text-sm mt-1 line-height-3">
                                                {event.host_name} • {event.event_date} • {event.guests_count} convidados
                                            </div>
                                        </div>
                                        <StatusTag value={event.status} />
                                    </Link>
                                ))
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </Layout>
    );
}
