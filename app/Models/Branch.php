<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    /** @use HasFactory<\Database\Factories\BranchFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_user')->withPivot('assigned_at');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function practices(): HasMany
    {
        return $this->hasMany(Practice::class);
    }

    public function fullAddress(): string
    {
        return trim("{$this->address}, {$this->postal_code} {$this->city} ({$this->province})");
    }
}
