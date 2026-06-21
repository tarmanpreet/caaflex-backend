<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use HasRoles;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $guard_name = 'web';

    /**
     * The attributes that are not mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the client profile associated with this user.
     */
    public function clientProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ClientProfile::class);
    }

    /**
     * Get the practices assigned to this user.
     */
    public function assignedPractices(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Practice::class, 'practice_user')->withPivot('assigned_at');
    }

    /**
     * Get the deadlines assigned to this user.
     */
    public function assignedDeadlines(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PracticeDeadline::class, 'user_id');
    }

    /**
     * Get the practices created by this user.
     */
    public function createdPractices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Practice::class, 'created_by');
    }

    public function availabilities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserAvailability::class);
    }

    public function assignedAppointments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Appointment::class, 'assigned_user_id');
    }

    public function practiceTypes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(PracticeType::class, 'practice_type_user');
    }

    public function branches(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_user')->withPivot('assigned_at');
    }
}
