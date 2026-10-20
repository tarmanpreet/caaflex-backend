<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAbsence extends Model
{
    use HasFactory;

    public const TYPES = ['ferie' => 'Ferie', 'permesso' => 'Permesso retribuito', 'permesso_non_retribuito' => 'Permesso non retribuito', 'malattia' => 'Malattia', 'visita_medica' => 'Visita medica', 'congedo' => 'Congedo', 'altro' => 'Altra assenza'];

    protected $fillable = ['user_id', 'type', 'starts_at', 'ends_at', 'all_day', 'status', 'note', 'certificate_number', 'reviewed_by', 'reviewed_at', 'review_note', 'cancelled_at'];

    protected $attributes = ['all_day' => true, 'status' => 'pending'];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'all_day' => 'boolean', 'note' => 'encrypted', 'certificate_number' => 'encrypted', 'review_note' => 'encrypted', 'reviewed_at' => 'immutable_datetime', 'cancelled_at' => 'immutable_datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
