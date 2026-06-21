<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Procedure extends Model
{
    use HasFactory;

    protected $fillable = [
        'procedure_type_id',
        'name',
        'default_notes',
        'deadline_days',
    ];

    protected $casts = [
        'deadline_days' => 'integer',
    ];

    public function practiceType(): BelongsTo
    {
        return $this->belongsTo(PracticeType::class, 'procedure_type_id');
    }

    public function practices(): HasMany
    {
        return $this->hasMany(Practice::class, 'procedure_id');
    }
}
