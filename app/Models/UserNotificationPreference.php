<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    /** @use HasFactory<\Database\Factories\UserNotificationPreferenceFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'section', 'enabled', 'mail_enabled', 'realtime_enabled', 'reminders_configured'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'mail_enabled' => 'boolean',
            'realtime_enabled' => 'boolean',
            'reminders_configured' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
