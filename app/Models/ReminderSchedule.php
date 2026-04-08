<?php

namespace App\Models;

use App\Enums\ReminderReason;
use App\Enums\ReminderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'guest_id',
        'scheduled_for',
        'status',
        'reason',
        'processed_at',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'processed_at' => 'datetime',
        'status' => ReminderStatus::class,
        'reason' => ReminderReason::class,
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
