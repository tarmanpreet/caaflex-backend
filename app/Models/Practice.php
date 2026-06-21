<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Practice extends Model
{
    use HasFactory;

    const TYPES = [
        '730',
        'ISEE',
        'IMU_TASI',
        'RED_INPS',
        'SUCCESSIONE',
        'BONUS_AGEVOLAZIONI',
        'ALTRO',
    ];

    const STATUSES = [
        'nuova',
        'in_lavorazione',
        'in_attesa_documenti',
        'completata',
        'annullata',
        'sospesa',
    ];

    /**
     * The attributes that are not mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id'];

    protected $fillable = [
        'client_profile_id',
        'procedure_id',
        'type',
        'practice_type_id',
        'status',
        'reference_year',
        'notes',
        'created_by',
        'deadline_at',
        'branch_id',
    ];

    protected function casts(): array
    {
        return [
            'reference_year' => 'integer',
            'deadline_at' => 'datetime',
        ];
    }

    /**
     * Get the client profile that owns this practice.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(ClientProfile::class, 'client_profile_id');
    }

    /**
     * Get the users assigned to this practice.
     */
    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'practice_user')
            ->withPivot('assigned_at');
    }

    /**
     * Get the notes for this practice.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(PracticeNote::class);
    }

    /**
     * Get the documents for this practice.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(PracticeDocument::class);
    }

    /**
     * Get the deadlines for this practice.
     */
    public function deadlines(): HasMany
    {
        return $this->hasMany(PracticeDeadline::class);
    }

    /**
     * Get the status logs for this practice.
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(PracticeStatusLog::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the user who created this practice.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function practiceType(): BelongsTo
    {
        return $this->belongsTo(PracticeType::class);
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class, 'procedure_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
