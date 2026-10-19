<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PracticeDocumentRequest extends Model
{
    use HasFactory;

    protected $fillable = ['practice_id', 'requested_by', 'title', 'instructions', 'due_on', 'status', 'review_reason'];

    protected function casts(): array
    {
        return ['due_on' => 'date'];
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PracticeDocument::class, 'document_request_id');
    }
}
