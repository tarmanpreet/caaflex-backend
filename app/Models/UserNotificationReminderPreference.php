<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationReminderPreference extends Model
{
    /** @use HasFactory<\Database\Factories\UserNotificationReminderPreferenceFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'section', 'minutes_before'];

    protected function casts(): array
    {
        return ['minutes_before' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
