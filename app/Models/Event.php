<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\InvitationAssetType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'host_name',
        'description',
        'event_date',
        'start_time',
        'location_name',
        'location_address',
        'invitation_message_template',
        'invitation_asset_type',
        'invitation_asset_url',
        'reminder_days_before',
        'status',
    ];

    protected $casts = [
        'event_date' => 'date',
        'invitation_asset_type' => InvitationAssetType::class,
        'status' => EventStatus::class,
    ];

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    public function invitationDispatches(): HasMany
    {
        return $this->hasMany(InvitationDispatch::class);
    }

    public function reminderSchedules(): HasMany
    {
        return $this->hasMany(ReminderSchedule::class);
    }
}
