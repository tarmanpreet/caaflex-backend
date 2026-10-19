<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeMessage extends Model
{
    use HasFactory;

    protected $fillable = ['practice_id', 'user_id', 'body', 'visibility', 'document_id'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(PracticeDocument::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }
}
