<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcedureDeadlineTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\ProcedureDeadlineTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'procedure_id',
        'title',
        'notes',
        'offset_days',
        'offset_hours',
        'priority',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'offset_days' => 'integer',
            'offset_hours' => 'integer',
            'priority' => 'integer',
            'position' => 'integer',
        ];
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }
}
