<?php

namespace App\Models;

use App\Enums\InvitationDispatchKind;
use App\Enums\InvitationDispatchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'delivery_status',
        'failure_reason',
        'payload_json',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
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
}
