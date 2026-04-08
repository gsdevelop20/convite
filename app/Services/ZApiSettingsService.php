<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;

class ZApiSettingsService
{
    public function getConfig(): array
    {
        return [
            'instance_id' => $this->get('zapi.instance_id') ?: config('services.zapi.instance_id'),
            'token' => $this->get('zapi.token') ?: config('services.zapi.token'),
            'client_token' => $this->get('zapi.client_token') ?: config('services.zapi.client_token'),
            'base_url' => config('services.zapi.base_url'),
        ];
    }

    public function save(array $data): void
    {
        $this->set('zapi.instance_id', $data['instance_id'] ?? null);
        $this->set('zapi.token', $data['token'] ?? null);
        $this->set('zapi.client_token', $data['client_token'] ?? null);
    }

    public function maskedConfig(): array
    {
        $config = $this->getConfig();

        return [
            'instance_id' => $config['instance_id'],
            'token' => $this->mask($config['token']),
            'client_token' => $this->mask($config['client_token']),
            'connection_status' => [
                'ok' => $this->get('zapi.connection_ok') === '1',
                'message' => $this->get('zapi.connection_message'),
                'tested_at' => $this->get('zapi.connection_tested_at'),
            ],
        ];
    }

    public function testConnection(array $overrides = []): array
    {
        $config = [
            ...$this->getConfig(),
            ...array_filter($overrides, fn ($value) => filled($value)),
        ];

        if (blank($config['instance_id'] ?? null) || blank($config['token'] ?? null)) {
            return $this->storeConnectionResult([
                'ok' => false,
                'message' => 'Preencha INSTANCE_ID e INSTANCE_TOKEN para testar a conexao.',
            ]);
        }

        $request = Http::baseUrl(rtrim($config['base_url'], '/'))->acceptJson();

        if (filled($config['client_token'] ?? null)) {
            $request = $request->withHeaders([
                'Client-Token' => $config['client_token'],
            ]);
        }

        $response = $request->get("/instances/{$config['instance_id']}/token/{$config['token']}/status");
        $data = $response->json() ?? [];

        if (! $response->successful()) {
            return $this->storeConnectionResult([
                'ok' => false,
                'message' => $data['error'] ?? $data['message'] ?? 'Nao foi possivel validar a conexao com a Z-API.',
            ]);
        }

        if (($data['connected'] ?? false) === true) {
            return $this->storeConnectionResult([
                'ok' => true,
                'message' => 'Conexao com a Z-API validada com sucesso. Instancia conectada.',
                'details' => $data,
            ]);
        }

        return $this->storeConnectionResult([
            'ok' => false,
            'message' => $data['error'] ?? 'A instancia respondeu, mas nao esta conectada ao WhatsApp.',
            'details' => $data,
        ]);
    }

    protected function get(string $key): ?string
    {
        return AppSetting::query()->find($key)?->value;
    }

    protected function set(string $key, ?string $value): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }

    protected function mask(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $length = strlen($value);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4).substr($value, -4);
    }

    protected function storeConnectionResult(array $result): array
    {
        $this->set('zapi.connection_ok', ($result['ok'] ?? false) ? '1' : '0');
        $this->set('zapi.connection_message', $result['message'] ?? null);
        $this->set('zapi.connection_tested_at', now()->toDateTimeString());

        return $result;
    }
}
