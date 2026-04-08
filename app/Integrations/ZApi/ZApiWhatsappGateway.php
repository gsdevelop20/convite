<?php

namespace App\Integrations\ZApi;

use App\Services\ZApiSettingsService;
use App\Services\WhatsappGateway;
use App\Support\SendResult;
use Illuminate\Support\Facades\Http;

class ZApiWhatsappGateway implements WhatsappGateway
{
    public function __construct(private readonly ZApiSettingsService $settings)
    {
    }

    public function sendText(string $phone, string $message, array $options = []): SendResult
    {
        return $this->send('/send-text', [
            'phone' => $phone,
            'message' => $message,
            ...$options,
        ]);
    }

    public function sendButtonList(string $phone, string $message, array $buttons, array $options = []): SendResult
    {
        return $this->send('/send-button-list', [
            'phone' => $phone,
            'message' => $message,
            'buttonList' => [
                'buttons' => array_map(
                    fn (array $button): array => [
                        'id' => (string) $button['id'],
                        'label' => (string) $button['label'],
                    ],
                    $buttons,
                ),
            ],
            ...$options,
        ]);
    }

    public function sendImage(string $phone, string $imageUrl, ?string $caption = null, array $options = []): SendResult
    {
        return $this->send('/send-image', [
            'phone' => $phone,
            'image' => $imageUrl,
            'caption' => $caption,
            ...$options,
        ]);
    }

    public function sendDocument(string $phone, string $documentUrl, string $extension, string $fileName, ?string $caption = null, array $options = []): SendResult
    {
        return $this->send("/send-document/{$extension}", [
            'phone' => $phone,
            'document' => $documentUrl,
            'fileName' => $fileName,
            'caption' => $caption,
            ...$options,
        ]);
    }

    protected function send(string $path, array $payload): SendResult
    {
        $config = $this->settings->getConfig();

        if (blank($config['instance_id'] ?? null) || blank($config['token'] ?? null)) {
            return new SendResult(
                successful: false,
                failureReason: 'Z-API nao configurada.',
                payload: $payload,
            );
        }

        $request = Http::baseUrl(rtrim($config['base_url'], '/'))
            ->acceptJson();

        if (filled($config['client_token'] ?? null)) {
            $request = $request->withHeaders([
                'Client-Token' => $config['client_token'],
            ]);
        }

        $response = $request->post("/instances/{$config['instance_id']}/token/{$config['token']}{$path}", $payload);

        $data = $response->json() ?? [];

        if (! $response->successful()) {
            return new SendResult(
                successful: false,
                failureReason: $data['message'] ?? $response->body(),
                payload: $data,
            );
        }

        return new SendResult(
            successful: true,
            providerMessageId: (string) ($data['messageId'] ?? $data['id'] ?? ''),
            providerZaapId: isset($data['zaapId']) ? (string) $data['zaapId'] : null,
            payload: $data,
        );
    }
}
