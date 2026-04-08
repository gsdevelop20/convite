<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\UpdateZApiSettingsRequest;
use App\Services\ZApiSettingsService;
use Inertia\Inertia;
use Inertia\Response;

class ZApiSettingsController extends Controller
{
    public function __construct(private readonly ZApiSettingsService $settings)
    {
    }

    public function edit(): Response
    {
        return Inertia::render('Settings/ZApi', [
            'settings' => $this->settings->maskedConfig(),
        ]);
    }

    public function update(UpdateZApiSettingsRequest $request)
    {
        $payload = $this->payloadWithStoredToken($request->validated());
        $this->settings->save($payload);

        return back()
            ->with('success', 'Configuracao da Z-API salva com sucesso.');
    }

    public function test(UpdateZApiSettingsRequest $request)
    {
        $result = $this->settings->testConnection($this->payloadWithStoredToken($request->validated()));

        return back()->with('zapi_test', $result);
    }

    protected function payloadWithStoredToken(array $data): array
    {
        $current = $this->settings->getConfig();

        return [
            'instance_id' => $data['instance_id'] ?? $current['instance_id'] ?? null,
            'token' => filled($data['token'] ?? null) ? $data['token'] : ($current['token'] ?? null),
            'client_token' => filled($data['client_token'] ?? null) ? $data['client_token'] : ($current['client_token'] ?? null),
        ];
    }
}
