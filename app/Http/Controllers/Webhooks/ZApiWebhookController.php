<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Webhooks\ZApiWebhookRequest;
use App\Jobs\ProcessIncomingWhatsappMessageJob;
use App\Models\WebhookReceipt;
use Illuminate\Http\JsonResponse;

class ZApiWebhookController extends Controller
{
    public function messages(ZApiWebhookRequest $request): JsonResponse
    {
        $payload = $request->all();
        $externalId = $payload['messageId'] ?? null;

        $receipt = WebhookReceipt::firstOrCreate(
            [
                'provider' => 'zapi',
                'external_id' => $externalId,
            ],
            [
                'event_type' => 'message_received',
                'headers_json' => $request->headers->all(),
                'payload_json' => $payload,
                'received_at' => now(),
            ],
        );

        ProcessIncomingWhatsappMessageJob::dispatch($receipt->id);

        return response()->json(['status' => 'accepted']);
    }

    public function messageStatus(ZApiWebhookRequest $request): JsonResponse
    {
        $payload = $request->all();

        WebhookReceipt::updateOrCreate(
            [
                'provider' => 'zapi',
                'external_id' => $payload['messageId'] ?? uniqid('status_', true),
            ],
            [
                'event_type' => 'message_status',
                'headers_json' => $request->headers->all(),
                'payload_json' => $payload,
                'received_at' => now(),
            ],
        );

        return response()->json(['status' => 'accepted']);
    }
}
