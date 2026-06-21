<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeNote extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'practice_id',
        'user_id',
        'body',
    ];

    /**
     * Get the practice that owns this note.
     */
    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    /**
     * Get the user who authored this note.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
