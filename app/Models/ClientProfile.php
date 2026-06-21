<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientProfile extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    /**
     * Get the user that owns this client profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the documents for this client profile.
     */
    public function documents(): HasMany
    {
        return $this->hasMany('App\Models\ClientDocument');
    }

    /**
     * Get the practices for this client profile.
     */
    public function practices(): HasMany
    {
        return $this->hasMany(Practice::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'client_profile_id');
    }
}
