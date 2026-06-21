<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeadlineReminder extends Model
{
    use HasFactory;

    public const TYPE_EMAIL = 'email';
    public const TYPE_IN_APP = 'in_app';

    public const MINUTES_DAY = 1440;
    public const MINUTES_HOUR = 60;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'deadline_id',
        'type',
        'minutes_before',
        'sent',
        'sent_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'minutes_before' => 'integer',
            'sent' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Get the deadline that owns this reminder.
     */
    public function deadline(): BelongsTo
    {
        return $this->belongsTo(PracticeDeadline::class, 'deadline_id');
    }
}
