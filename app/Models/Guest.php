<?php

namespace App\Models;

use App\Enums\GuestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guest extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'phone_e164',
        'normalized_phone',
        'metadata_json',
        'invited_at',
        'reminder_scheduled_at',
        'last_reminder_sent_at',
        'response_channel',
        'current_status',
        'companions_count',
        'last_response_at',
    ];

    protected $casts = [
        'metadata_json' => 'array',
        'invited_at' => 'datetime',
        'reminder_scheduled_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
        'last_response_at' => 'datetime',
        'current_status' => GuestStatus::class,
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function invitationDispatches(): HasMany
    {
        return $this->hasMany(InvitationDispatch::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(GuestResponse::class);
    }

    public function reminderSchedules(): HasMany
    {
        return $this->hasMany(ReminderSchedule::class);
    }
}
