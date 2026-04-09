<?php

namespace App\Models;

use App\Enums\InvitationDispatchKind;
use App\Enums\InvitationDispatchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class InvitationDispatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'guest_id',
        'kind',
        'message_type',
        'outbound_message',
        'outbound_asset_url',
        'provider',
        'provider_message_id',
        'provider_zaap_id',
        'sent_at',
        'scheduled_for',
        'delivery_status',
        'failure_reason',
        'payload_json',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'payload_json' => 'array',
        'kind' => InvitationDispatchKind::class,
        'delivery_status' => InvitationDispatchStatus::class,
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function getOutboundAssetUrlAttribute(?string $value): ?string
    {
        if (blank($value)) {
            return $value;
        }

        $path = parse_url($value, PHP_URL_PATH);

        if (! is_string($path) || ! str_starts_with($path, '/storage/')) {
            return $value;
        }

        return rtrim(Storage::disk('public')->url(''), '/').'/'.ltrim(substr($path, strlen('/storage/')), '/');
    }
}
