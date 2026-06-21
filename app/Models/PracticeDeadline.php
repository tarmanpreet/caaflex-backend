<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PracticeDeadline extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PRIORITY_URGENT = 1;
    public const PRIORITY_HIGH = 2;
    public const PRIORITY_MEDIUM = 3;
    public const PRIORITY_LOW = 4;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'practice_id',
        'user_id',
        'title',
        'notes',
        'deadline_at',
        'status',
        'priority',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'deadline_at' => 'datetime',
            'priority' => 'integer',
            'status' => 'string',
        ];
    }

    /**
     * Get the practice that owns this deadline.
     */
    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    /**
     * Get the user assigned to this deadline.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the reminders for this deadline.
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(DeadlineReminder::class, 'deadline_id');
    }

    /**
     * Get the user who created this deadline.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include upcoming deadlines.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('deadline_at', '>', now())
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    /**
     * Scope a query to only include overdue deadlines.
     */
    public function scopeOverdue($query)
    {
        return $query->where('deadline_at', '<', now())
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    /**
     * Scope a query to only include deadlines for a specific practice.
     */
    public function scopeForPractice($query, int $practiceId)
    {
        return $query->where('practice_id', $practiceId);
    }

    /**
     * Scope a query to only include deadlines assigned to a specific user.
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if the deadline is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->deadline_at < now()
            && ! in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }
}
