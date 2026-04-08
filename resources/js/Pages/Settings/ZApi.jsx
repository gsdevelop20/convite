import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from 'primereact/button';
import { Card } from 'primereact/card';
import { InputText } from 'primereact/inputtext';
import Layout from '@/Layouts/layout/layout.jsx';

export default function ZApiSettings({ settings, flash }) {
    const form = useForm({
        instance_id: settings.instance_id ?? '',
        token: '',
        client_token: '',
    });

    const submit = (event) => {
        event.preventDefault();

        form.put(route('settings.zapi.update'), {
            preserveScroll: true,
        });
    };

    const testConnection = () => {
        form.post(route('settings.zapi.test'), {
            preserveScroll: true,
        });
    };

    return (
        <Layout>
            <Head title="Configuração Z-API" />

            <div className="grid">
                <div className="col-12 xl:col-8">
                    <Card title="Configuração Z-API" subTitle="Informe a credencial da sua instância para habilitar os envios pelo WhatsApp.">
                        {flash?.zapi_test ? (
                            <div className={`p-3 border-round mb-4 ${flash.zapi_test.ok ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'}`}>
                                {flash.zapi_test.message}
                            </div>
                        ) : null}

                        <form onSubmit={submit} className="grid formgrid p-fluid">
                            <div className="col-12">
                                <label htmlFor="instance_id" className="block mb-2">INSTANCE_ID</label>
                                <InputText id="instance_id" value={form.data.instance_id} onChange={(e) => form.setData('instance_id', e.target.value)} />
                                {form.errors.instance_id ? <small className="p-error">{form.errors.instance_id}</small> : null}
                            </div>
                            <div className="col-12">
                                <label htmlFor="token" className="block mb-2">INSTANCE_TOKEN</label>
                                <InputText id="token" value={form.data.token} onChange={(e) => form.setData('token', e.target.value)} placeholder={settings.token ? 'Deixe em branco para manter o token atual' : ''} />
                                {settings.token ? <small className="text-600 block mt-2">Token atual salvo: {settings.token}</small> : null}
                                {form.errors.token ? <small className="p-error block">{form.errors.token}</small> : null}
                            </div>
                            <div className="col-12">
                                <label htmlFor="client_token" className="block mb-2">CLIENT_TOKEN</label>
                                <InputText
                                    id="client_token"
                                    value={form.data.client_token}
                                    onChange={(e) => form.setData('client_token', e.target.value)}
                                    placeholder={settings.client_token ? 'Deixe em branco para manter o Client-Token atual' : ''}
                                />
                                {settings.client_token ? <small className="text-600 block mt-2">Client-Token atual salvo: {settings.client_token}</small> : null}
                                {form.errors.client_token ? <small className="p-error block">{form.errors.client_token}</small> : null}
                            </div>
                            <div className="col-12">
                                <div className="p-3 border-round surface-100 text-700 line-height-3">
                                    Informe `INSTANCE_ID`, `INSTANCE_TOKEN` e, se a sua instância exigir, também o `CLIENT_TOKEN`.
                                </div>
                            </div>
                            <div className="col-12 flex justify-content-end gap-2">
                                <Button type="button" label="Testar conexão" icon="pi pi-wifi" outlined onClick={testConnection} loading={form.processing} />
                                <Button type="submit" label="Salvar configuração" icon="pi pi-save" loading={form.processing} />
                            </div>
                        </form>
                    </Card>
                </div>
            </div>
        </Layout>
    );
}
