import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Chart } from 'primereact/chart';
import { Button } from 'primereact/button';
import Layout from '@/Layouts/layout/layout.jsx';
import StatCard from '@/Components/events/StatCard.jsx';
import StatusTag from '@/Components/events/StatusTag.jsx';

export default function Dashboard({ stats, guestStatusBreakdown, recentEvents }) {
    const chartData = {
        labels: guestStatusBreakdown.map((item) => item.label),
        datasets: [
            {
                data: guestStatusBreakdown.map((item) => item.value),
                backgroundColor: ['#0ea5e9', '#22c55e', '#14b8a6', '#ef4444', '#f59e0b'],
                hoverBackgroundColor: ['#38bdf8', '#4ade80', '#2dd4bf', '#f87171', '#fbbf24'],
            },
        ],
    };

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
                        <div className="flex justify-content-between align-items-center mb-4">
                            <div>
                                <h3 className="m-0">RSVP geral</h3>
                                <p className="text-600 mt-2 mb-0">Distribuição atual dos convidados.</p>
                            </div>
                            <Link href={route('events.index')}>
                                <Button label="Ver eventos" icon="pi pi-arrow-right" outlined />
                            </Link>
                        </div>
                        <Chart type="doughnut" data={chartData} />
                    </div>
                </div>

                <div className="col-12 xl:col-7">
                    <div className="card h-full">
                        <div className="flex justify-content-between align-items-center mb-4">
                            <div>
                                <h3 className="m-0">Eventos recentes</h3>
                                <p className="text-600 mt-2 mb-0">Acompanhe rapidamente o andamento de cada festa.</p>
                            </div>
                            <Link href={route('events.index')}>
                                <Button label="Novo evento" icon="pi pi-plus" />
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
                                        className="flex align-items-center justify-content-between p-4 border-bottom-1 surface-border text-color no-underline"
                                    >
                                        <div>
                                            <div className="font-semibold text-900">{event.name}</div>
                                            <div className="text-600 mt-1">
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
