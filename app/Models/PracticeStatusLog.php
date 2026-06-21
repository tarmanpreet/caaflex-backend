<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeStatusLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'practice_id',
        'user_id',
        'old_status',
        'new_status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the practice that owns this status log.
     */
    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    /**
     * Get the user who made this status change.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
