import React, { useEffect, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Button } from 'primereact/button';
import { Column } from 'primereact/column';
import { DataTable } from 'primereact/datatable';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';
import { InputTextarea } from 'primereact/inputtextarea';
import { SplitButton } from 'primereact/splitbutton';
import Layout from '@/Layouts/layout/layout.jsx';
import StatusTag from '@/Components/events/StatusTag.jsx';
import { InputSwitch } from 'primereact/inputswitch';

const assetOptions = [
    { label: 'Texto', value: 'text' },
    { label: 'Imagem', value: 'image' },
    { label: 'PDF', value: 'pdf' },
];

const statusOptions = [
    { label: 'Rascunho', value: 'draft' },
    { label: 'Agendado', value: 'scheduled' },
    { label: 'Ativo', value: 'active' },
    { label: 'Finalizado', value: 'finished' },
    { label: 'Cancelado', value: 'cancelled' },
];

const initialForm = {
    name: '',
    host_name: '',
    description: '',
    event_date: '',
    start_time: '',
    location_name: '',
    location_latitude: '',
    location_longitude: '',
    location_address: '',
    send_location_pin: false,
    invitation_message_template:
        'Olá :guest_name! Você está convidado para :event_name em :event_date, no local :location_name.',
    invitation_asset_type: 'text',
    invitation_asset_file: null,
    invitation_asset_url: null,
    reminder_days_before: 15,
    status: 'draft',
};

export default function EventsIndex({ events }) {
    const [dialogVisible, setDialogVisible] = useState(false);
    const [guestDialogVisible, setGuestDialogVisible] = useState(false);
    const [editingEvent, setEditingEvent] = useState(null);
    const [selectedEventId, setSelectedEventId] = useState(null);
    const [editingGuest, setEditingGuest] = useState(null);
    const form = useForm(initialForm);
    const guestForm = useForm({ name: '', phone: '' });
    const importForm = useForm({ rows: '' });

    const selectedEvent = useMemo(
        () => events.find((event) => event.id === selectedEventId) ?? null,
        [events, selectedEventId],
    );

    const title = useMemo(
        () => (editingEvent ? 'Editar evento' : 'Criar evento'),
        [editingEvent],
    );

    const openCreate = () => {
        setEditingEvent(null);
        form.reset();
        form.clearErrors();
        form.setData(() => ({ ...initialForm }));
        setDialogVisible(true);
    };

    const openEdit = (event) => {
        setEditingEvent(event);
        form.clearErrors();
        form.setData(() => ({
            ...initialForm,
            ...event,
            invitation_asset_type: event.invitation_asset_type ?? 'text',
            invitation_asset_file: null,
        }));
        setDialogVisible(true);
    };

    const closeDialog = () => {
        setDialogVisible(false);
        setEditingEvent(null);
        form.reset();
        form.clearErrors();
    };

    const openGuests = (event) => {
        setSelectedEventId(event.id);
        setEditingGuest(null);
        guestForm.reset();
        importForm.reset();
        guestForm.clearErrors();
        importForm.clearErrors();
        setGuestDialogVisible(true);
    };

    const closeGuestsDialog = () => {
        setGuestDialogVisible(false);
        setSelectedEventId(null);
        setEditingGuest(null);
        guestForm.reset();
        importForm.reset();
        guestForm.clearErrors();
        importForm.clearErrors();
    };

    const submit = () => {
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                form.transform((data) => data);
                closeDialog();
            },
            onError: () => setDialogVisible(true),
        };

        if (editingEvent) {
            form.transform((data) => ({
                ...data,
                _method: 'patch',
            }));

            form.post(route('events.update', editingEvent.id), {
                ...options,
                forceFormData: true,
            });

            return;
        }

        form.transform((data) => data);

        form.post(route('events.store'), {
            ...options,
            forceFormData: true,
        });
    };

    const queueInvitations = (event) => {
        if (!window.confirm(`Adicionar as mensagens do evento "${event.name}" na fila de envio?`)) {
            return;
        }

        router.post(route('events.invitations.send', event.id), {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleAssetTypeChange = (value) => {
        form.clearErrors('invitation_asset_type', 'invitation_asset_file');
        form.setData((data) => ({
            ...data,
            invitation_asset_type: value,
            invitation_asset_file: value === 'text' ? null : data.invitation_asset_file,
            invitation_asset_url: value === 'text' ? null : data.invitation_asset_url,
        }));
    };

    const actionsBody = (row) => (
        <SplitButton
            label="Convidados"
            icon="pi pi-users"
            size="small"
            onClick={() => openGuests(row)}
            model={[
                {
                    label: 'Editar',
                    icon: 'pi pi-pencil',
                    command: () => openEdit(row),
                },
                {
                    label: 'Acompanhar fila',
                    icon: 'pi pi-list',
                    command: () => router.visit(route('events.queue', row.id)),
                },
                {
                    label: 'Fila de envio',
                    icon: 'pi pi-send',
                    command: () => queueInvitations(row),
                    disabled: row.guests_count === 0,
                },
            ]}
            outlined
            className="w-full white-space-nowrap"
        />
    );

    const assetBody = (row) => {
        const labels = {
            text: 'Texto',
            image: 'Imagem',
            pdf: 'PDF',
        };

        return labels[row.invitation_asset_type] ?? row.invitation_asset_type;
    };

    const accompaniedBody = (row) => ((row.companions_count ?? 0) > 0 || row.current_status === 'confirmed_with_companion' ? 'Sim' : 'Não');

    const submitGuest = (event) => {
        event.preventDefault();

        if (!selectedEvent) {
            return;
        }

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setEditingGuest(null);
                guestForm.reset();
                router.reload({
                    only: ['events'],
                    preserveState: true,
                    preserveScroll: true,
                });
            },
        };

        if (editingGuest) {
            guestForm.patch(route('events.guests.update', [selectedEvent.id, editingGuest.id]), options);

            return;
        }

        guestForm.post(route('events.guests.store', selectedEvent.id), options);
    };

    const submitImport = (event) => {
        event.preventDefault();

        if (!selectedEvent) {
            return;
        }

        importForm.post(route('events.guests.import', selectedEvent.id), {
            preserveScroll: true,
            onSuccess: () => {
                importForm.reset();
                router.reload({
                    only: ['events'],
                    preserveState: true,
                    preserveScroll: true,
                });
            },
        });
    };

    const openGuestEdit = (guest) => {
        setEditingGuest(guest);
        guestForm.clearErrors();
        guestForm.setData({
            name: guest.name,
            phone: guest.phone_e164.replace(/^\+55/, ''),
        });
    };

    const cancelGuestEdit = () => {
        setEditingGuest(null);
        guestForm.reset();
        guestForm.clearErrors();
    };

    const deleteGuest = (guest) => {
        if (!selectedEvent || !window.confirm(`Deseja apagar o convidado ${guest.name}?`)) {
            return;
        }

        router.delete(route('events.guests.destroy', [selectedEvent.id, guest.id]), {
            preserveScroll: true,
            onSuccess: () => {
                if (editingGuest?.id === guest.id) {
                    cancelGuestEdit();
                }
                router.reload({
                    only: ['events'],
                    preserveState: true,
                    preserveScroll: true,
                });
            },
        });
    };

    const sendGuestNow = (guest) => {
        if (!selectedEvent || !window.confirm(`Enviar o convite agora para ${guest.name}?`)) {
            return;
        }

        router.post(route('events.guests.send-now', [selectedEvent.id, guest.id]), {}, {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({
                    only: ['events'],
                    preserveState: true,
                    preserveScroll: true,
                });
            },
        });
    };

    useEffect(() => {
        if (!guestDialogVisible || !selectedEventId) {
            return undefined;
        }

        const intervalId = window.setInterval(() => {
            router.reload({
                only: ['events'],
                preserveState: true,
                preserveScroll: true,
            });
        }, 10000);

        return () => window.clearInterval(intervalId);
    }, [guestDialogVisible, selectedEventId]);

    return (
        <Layout>
            <Head title="Eventos" />

            <div className="card">
                <div className="flex justify-content-between align-items-center mb-4 gap-3 flex-column sm:flex-row">
                    <div>
                        <h2 className="m-0">Eventos</h2>
                        <p className="text-600 mt-2 mb-0">Gerencie os eventos, convites e configurações de RSVP.</p>
                    </div>
                    <Button label="Criar evento" icon="pi pi-plus" onClick={openCreate} />
                </div>

                <DataTable value={events} paginator rows={10} stripedRows emptyMessage="Nenhum evento cadastrado ainda.">
                    <Column field="id" header="Event ID" />
                    <Column field="name" header="Evento" />
                    <Column field="host_name" header="Anfitrião" />
                    <Column field="event_date" header="Data" />
                    <Column field="location_name" header="Local" />
                    <Column field="invitation_asset_type" header="Convite" body={assetBody} />
                    <Column field="guests_count" header="Convidados" />
                    <Column field="status" header="Status" body={(row) => <StatusTag value={row.status} />} />
                    <Column
                        body={actionsBody}
                        header="Ações"
                        bodyStyle={{ minWidth: '12rem', verticalAlign: 'top' }}
                        headerStyle={{ minWidth: '12rem' }}
                    />
                </DataTable>
            </div>

            <Dialog
                header={title}
                visible={dialogVisible}
                style={{ width: 'min(900px, 95vw)' }}
                onHide={closeDialog}
                modal
            >
                <form
                    encType="multipart/form-data"
                    onSubmit={(event) => {
                        event.preventDefault();
                        submit();
                    }}
                    className="grid formgrid p-fluid"
                >
                    {Object.keys(form.errors).length > 0 ? (
                        <div className="col-12">
                            <div className="p-3 border-round bg-red-50 text-red-700">
                                Revise os campos obrigatórios e tente novamente.
                            </div>
                        </div>
                    ) : null}
                    <div className="col-12">
                        <label htmlFor="name" className="block mb-2">Nome do evento</label>
                        <InputText id="name" value={form.data.name}
                                   onChange={(e) => form.setData('name', e.target.value)}/>
                        {form.errors.name ? <small className="p-error">{form.errors.name}</small> : null}
                    </div>
                    <div className="col-12 md:col-6">
                        <label htmlFor="host_name" className="block mb-2">Anfitrião</label>
                        <InputText id="host_name" value={form.data.host_name}
                                   onChange={(e) => form.setData('host_name', e.target.value)}/>
                        {form.errors.host_name ? <small className="p-error">{form.errors.host_name}</small> : null}
                    </div>
                    <div className="col-12 md:col-6">
                        <label htmlFor="event_date" className="block mb-2">Data</label>
                        <InputText id="event_date" type="date" value={form.data.event_date}
                                   onChange={(e) => form.setData('event_date', e.target.value)}/>
                        {form.errors.event_date ? <small className="p-error">{form.errors.event_date}</small> : null}
                    </div>
                    <div className="col-12 md:col-6">
                        <label htmlFor="start_time" className="block mb-2">Hora</label>
                        <InputText id="start_time" type="time" value={form.data.start_time || ''}
                                   onChange={(e) => form.setData('start_time', e.target.value)}/>
                        {form.errors.start_time ? <small className="p-error">{form.errors.start_time}</small> : null}
                    </div>
                    <div className="col-12 md:col-6">
                        <label htmlFor="reminder_days_before" className="block mb-2">Dias para lembrete</label>
                        <InputText
                            id="reminder_days_before"
                            type="number"
                            value={form.data.reminder_days_before}
                            onChange={(e) => form.setData('reminder_days_before', Number(e.target.value))}
                        />
                        {form.errors.reminder_days_before ?
                            <small className="p-error">{form.errors.reminder_days_before}</small> : null}
                    </div>
                    <div className="col-12">
                        <label htmlFor="location_name" className="block mb-2">Local</label>
                        <InputText id="location_name" value={form.data.location_name}
                                   onChange={(e) => form.setData('location_name', e.target.value)}/>
                        {form.errors.location_name ?
                            <small className="p-error">{form.errors.location_name}</small> : null}
                    </div>
                    <div className="col-12 md:col-6">
                        <label htmlFor="location_latitude" className="block mb-2">Latitude</label>
                        <InputText
                            id="location_latitude"
                            value={form.data.location_latitude ?? ''}
                            onChange={(e) => form.setData('location_latitude', e.target.value)}
                            placeholder="-15.793889"
                        />
                        {form.errors.location_latitude ?
                            <small className="p-error">{form.errors.location_latitude}</small> : null}
                    </div>
                    <div className="col-12 md:col-6">
                        <label htmlFor="location_longitude" className="block mb-2">Longitude</label>
                        <InputText
                            id="location_longitude"
                            value={form.data.location_longitude ?? ''}
                            onChange={(e) => form.setData('location_longitude', e.target.value)}
                            placeholder="-47.882778"
                        />
                        {form.errors.location_longitude ?
                            <small className="p-error">{form.errors.location_longitude}</small> : null}
                    </div>
                    <div className={"col-12  md:col-6"}>
                        <label htmlFor="send_location_pin" className="block mb-2">Enviar localização com pin no mapa
                            (requer latitude e longitude)</label>
                        <InputSwitch checked={form.data.send_location_pin}
                                     onChange={(e) => form.setData('send_location_pin', e.target.value)}
                                     id="send_location_pin"/>
                    </div>
                    <div className="col-12">
                        <label htmlFor="location_address" className="block mb-2">Endereço</label>
                        <InputText id="location_address" value={form.data.location_address}
                                   onChange={(e) => form.setData('location_address', e.target.value)}/>
                        {form.errors.location_address ?
                            <small className="p-error">{form.errors.location_address}</small> : null}
                    </div>
                    <div className="col-12 md:col-6">
                        <label htmlFor="invitation_asset_type" className="block mb-2">Tipo do convite</label>
                        <Dropdown
                            id="invitation_asset_type"
                            value={form.data.invitation_asset_type}
                            options={assetOptions}
                            onChange={(e) => handleAssetTypeChange(e.value)}
                        />
                        {form.errors.invitation_asset_type ?
                            <small className="p-error">{form.errors.invitation_asset_type}</small> : null}
                    </div>
                    <div className="col-12 md:col-6">
                        <label htmlFor="status" className="block mb-2">Status</label>
                        <Dropdown id="status" value={form.data.status} options={statusOptions}
                                  onChange={(e) => form.setData('status', e.value)}/>
                        {form.errors.status ? <small className="p-error">{form.errors.status}</small> : null}
                    </div>
                    <div className="col-12">
                        <div className="p-3 border-round bg-blue-50 text-blue-700 line-height-3">
                            O convite inicial já será enviado com os botões de resposta <strong>Vou</strong>, <strong>Não
                            vou</strong>, <strong>Ainda não sei</strong> e <strong>Vou com crianças</strong>.
                            {form.data.invitation_asset_type === 'text'
                                ? ' No convite em texto, os botões seguem na mesma mensagem.'
                                : ' No convite com imagem ou PDF, o arquivo vai primeiro e os botões são enviados logo depois.'}
                        </div>
                    </div>
                    {form.data.invitation_asset_type !== 'text' ? (
                        <div className="col-12">
                            <label htmlFor="invitation_asset_file" className="block mb-2">Arquivo do convite</label>
                            <input
                                id="invitation_asset_file"
                                type="file"
                                accept={form.data.invitation_asset_type === 'pdf' ? 'application/pdf' : 'image/*'}
                                className="block w-full p-3 border-1 surface-border border-round"
                                onChange={(e) => form.setData('invitation_asset_file', e.target.files?.[0] ?? null)}
                            />
                            {form.data.invitation_asset_file ? (
                                <small className="text-600 block mt-2">
                                    Arquivo selecionado: {form.data.invitation_asset_file.name}
                                </small>
                            ) : null}
                            {editingEvent && form.data.invitation_asset_url ? (
                                <div className="mt-3">
                                    <small className="text-600 block mb-2">Arquivo atual:</small>
                                    {form.data.invitation_asset_type === 'image' ? (
                                        <>
                                            <img
                                                src={form.data.invitation_asset_url}
                                                alt="Convite atual"
                                                className="block border-round border-1 surface-border"
                                                style={{maxWidth: '100%', maxHeight: '240px', objectFit: 'contain'}}
                                            />
                                            <a
                                                href={form.data.invitation_asset_url}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="inline-flex align-items-center gap-2 text-primary no-underline mt-2"
                                            >
                                                <i className="pi pi-image"/>
                                                <span>Abrir imagem atual</span>
                                            </a>
                                        </>
                                    ) : (
                                        <a
                                            href={form.data.invitation_asset_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex align-items-center gap-2 text-primary no-underline"
                                        >
                                            <i className="pi pi-file-pdf"/>
                                            <span>Abrir PDF atual</span>
                                        </a>
                                    )}
                                    <small className="text-600 block mt-2 break-all">
                                        URL atual: {form.data.invitation_asset_url}
                                    </small>
                                    <small className="text-600 block mt-2">Se não enviar novo arquivo, o atual será
                                        mantido.</small>
                                </div>
                            ) : null}
                            {form.errors.invitation_asset_file ? <small
                                className="p-error block mt-2">{form.errors.invitation_asset_file}</small> : null}
                        </div>
                    ) : null}
                    <div className="col-12">
                        <label htmlFor="description" className="block mb-2">Descrição</label>
                        <InputTextarea id="description" rows={3} value={form.data.description || ''}
                                       onChange={(e) => form.setData('description', e.target.value)}/>
                        {form.errors.description ? <small className="p-error">{form.errors.description}</small> : null}
                    </div>
                    <div className="col-12">
                        <label htmlFor="invitation_message_template" className="block mb-2">Template da mensagem</label>
                        <InputTextarea
                            id="invitation_message_template"
                            rows={6}
                            value={form.data.invitation_message_template}
                            onChange={(e) => form.setData('invitation_message_template', e.target.value)}
                        />
                        {form.errors.invitation_message_template ?
                            <small className="p-error">{form.errors.invitation_message_template}</small> : null}
                        <small className="text-600 block mt-2">
                            Variáveis disponíveis: :guest_name, :event_name, :host_name, :event_date, :location_name e
                            :location_address.
                        </small>
                    </div>
                    <div className="col-12 flex justify-content-end gap-2 mt-3">
                        <Button type="button" label="Cancelar" outlined onClick={closeDialog}/>
                        <Button
                            type="button"
                            label={editingEvent ? 'Salvar alterações' : 'Criar evento'}
                            icon="pi pi-save"
                            loading={form.processing}
                            onClick={submit}
                        />
                    </div>
                </form>
            </Dialog>

            <Dialog
                header={selectedEvent ? `Convidados - ${selectedEvent.name}` : 'Convidados'}
                visible={guestDialogVisible}
                style={{width: 'min(1100px, 96vw)'}}
                onHide={closeGuestsDialog}
                modal
            >
                {selectedEvent ? (
                    <div className="grid">
                        <div className="col-12 xl:col-4">
                            <div className="surface-card border-1 surface-border border-round p-4">
                                <h3 className="mt-0 mb-3">{editingGuest ? 'Editar convidado' : 'Adicionar convidado'}</h3>
                                <form onSubmit={submitGuest} className="flex flex-column gap-3">
                                    <div>
                                        <label htmlFor="guest_name" className="block mb-2">Nome</label>
                                        <InputText id="guest_name" value={guestForm.data.name} onChange={(e) => guestForm.setData('name', e.target.value)} className="w-full" />
                                        {guestForm.errors.name ? <small className="p-error">{guestForm.errors.name}</small> : null}
                                    </div>
                                    <div>
                                        <label htmlFor="guest_phone" className="block mb-2">Telefone</label>
                                        <InputText
                                            id="guest_phone"
                                            value={guestForm.data.phone}
                                            onChange={(e) => guestForm.setData('phone', e.target.value.replace(/\D/g, '').slice(0, 11))}
                                            className="w-full"
                                            keyfilter="int"
                                            maxLength={11}
                                            placeholder="61995706650"
                                        />
                                        <small className="text-600 block mt-2">Informe apenas números, no padrão 61995706650.</small>
                                        {guestForm.errors.phone ? <small className="p-error">{guestForm.errors.phone}</small> : null}
                                    </div>
                                    <div className="flex gap-2">
                                        {editingGuest ? <Button type="button" label="Cancelar" outlined onClick={cancelGuestEdit} /> : null}
                                        <Button type="submit" label={editingGuest ? 'Salvar alterações' : 'Salvar convidado'} icon={editingGuest ? 'pi pi-save' : 'pi pi-user-plus'} loading={guestForm.processing} />
                                    </div>
                                </form>
                            </div>

                            <div className="surface-card border-1 surface-border border-round p-4 mt-4">
                                <h3 className="mt-0 mb-3">Importar em lote</h3>
                                <p className="text-600 mt-0 mb-3">Uma linha por convidado: `Nome;61995706650`</p>
                                <form onSubmit={submitImport} className="flex flex-column gap-3">
                                    <InputTextarea rows={8} value={importForm.data.rows} onChange={(e) => importForm.setData('rows', e.target.value)} />
                                    {importForm.errors.rows ? <small className="p-error">{importForm.errors.rows}</small> : null}
                                    <Button type="submit" label="Importar convidados" icon="pi pi-upload" outlined loading={importForm.processing} />
                                </form>
                            </div>
                        </div>

                        <div className="col-12 xl:col-8">
                            <div className="surface-card border-1 surface-border border-round p-4">
                                <div className="flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h3 className="mt-0 mb-1">Lista de convidados</h3>
                                        <p className="text-600 m-0">{selectedEvent.guests_count} convidado(s) cadastrados</p>
                                    </div>
                                </div>

                                <DataTable value={selectedEvent.guests} paginator rows={8} stripedRows emptyMessage="Nenhum convidado cadastrado ainda.">
                                    <Column field="id" header="Guest ID" />
                                    <Column field="name" header="Nome" />
                                    <Column field="phone_e164" header="Telefone" />
                                    <Column field="current_status" header="Status" body={(row) => <StatusTag value={row.current_status} />} />
                                    <Column header="Com crianças" body={accompaniedBody} />
                                    <Column
                                        header="Ações"
                                        body={(row) => (
                                            <div className="flex gap-2">
                                                <Button type="button" icon="pi pi-send" rounded text severity="success" onClick={() => sendGuestNow(row)} />
                                                <Button type="button" icon="pi pi-pencil" rounded text onClick={() => openGuestEdit(row)} />
                                                <Button type="button" icon="pi pi-trash" rounded text severity="danger" onClick={() => deleteGuest(row)} />
                                            </div>
                                        )}
                                    />
                                </DataTable>
                            </div>
                        </div>
                    </div>
                ) : null}
            </Dialog>
        </Layout>
    );
}
