<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeDocument extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'practice_id',
        'uploaded_by',
        'original_name',
        'disk_path',
        'mime_type',
        'file_size',
        'description',
    ];

    /**
     * Get the practice that owns this document.
     */
    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    /**
     * Get the user who uploaded this document.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
