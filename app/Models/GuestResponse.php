<?php

namespace App\Models;

use App\Enums\ParsedIntent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'guest_id',
        'inbound_message_id',
        'raw_message',
        'parsed_intent',
        'companions_count',
        'source_payload_json',
        'received_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'source_payload_json' => 'array',
        'parsed_intent' => ParsedIntent::class,
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
