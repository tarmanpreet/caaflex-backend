<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    const STATUSES = [
        'da_confermare',
        'confermato',
        'completato',
        'cancellato',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ClientProfile::class, 'client_profile_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function practiceType(): BelongsTo
    {
        return $this->belongsTo(PracticeType::class);
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
