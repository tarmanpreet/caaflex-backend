<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ScheduledNotificationOccurrence extends Model
{
    /** @use HasFactory<\Database\Factories\ScheduledNotificationOccurrenceFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_DISPATCHED = 'dispatched';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id', 'event_key', 'subject_type', 'subject_id', 'minutes_before',
        'subject_scheduled_at', 'scheduled_for', 'expires_at', 'status',
        'deduplication_key', 'dispatched_at', 'cancelled_at',
    ];

    protected $attributes = ['status' => self::STATUS_PENDING];

    protected function casts(): array
    {
        return [
            'minutes_before' => 'integer',
            'subject_scheduled_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'expires_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
