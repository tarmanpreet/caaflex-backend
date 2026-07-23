<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    private ?Collection $accessibleBranchIdsCache = null;

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

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(UserNotificationPreference::class);
    }

    public function notificationReminderPreferences(): HasMany
    {
        return $this->hasMany(UserNotificationReminderPreference::class);
    }

    public function practiceTypes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(PracticeType::class, 'practice_type_user');
    }

    public function branches(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_user')->withPivot('assigned_at');
    }

    /** @return Collection<int, int> */
    public function accessibleBranchIds(): Collection
    {
        if ($this->accessibleBranchIdsCache !== null) {
            return $this->accessibleBranchIdsCache;
        }

        if ($this->hasRole('superadmin')) {
            return $this->accessibleBranchIdsCache = Branch::query()->pluck('id')->map(fn ($id): int => (int) $id);
        }

        $assignedIds = $this->branches()->pluck('branches.id');

        if ($assignedIds->isEmpty()) {
            $assignedIds = Branch::query()->whereNull('parent_id')->pluck('id');
        }

        return $this->accessibleBranchIdsCache = Branch::descendantIdsFor($assignedIds);
    }

    public function canAccessBranchId(?int $branchId): bool
    {
        return $branchId === null
            ? Branch::query()->doesntExist()
            : $this->accessibleBranchIds()->contains($branchId);
    }

    /**
     * Roles that this user is allowed to assign when creating a new user.
     *
     * - superadmin (has admins.create) can create admin or employee
     * - admin (has users.create only) can create employee only
     */
    public function assignableRoles(): array
    {
        $roles = [];

        if ($this->can('users.create')) {
            $roles[] = 'employee';
        }

        if ($this->can('admins.create')) {
            $roles[] = 'admin';
        }

        return $roles;
    }
}
