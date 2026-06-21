<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PracticeType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'duration_minutes',
        'color',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function practices(): HasMany
    {
        return $this->hasMany(Practice::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'practice_type_user');
    }
}
