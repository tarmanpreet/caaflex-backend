<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(ClientProfile::class);
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

    /** @param Collection<int, int>|array<int, int> $branchIds */
    public static function descendantIdsFor(Collection|array $branchIds): Collection
    {
        $requestedIds = collect($branchIds)->map(fn ($id): int => (int) $id)->unique()->values();

        if ($requestedIds->isEmpty()) {
            return collect();
        }

        $childrenByParent = self::query()->get(['id', 'parent_id'])->groupBy('parent_id');
        $visibleIds = $requestedIds;
        $pendingIds = $requestedIds;

        while ($pendingIds->isNotEmpty()) {
            $pendingIds = $pendingIds
                ->flatMap(fn (int $parentId) => $childrenByParent->get($parentId, collect())->pluck('id'))
                ->map(fn ($id): int => (int) $id)
                ->diff($visibleIds)
                ->unique()
                ->values();
            $visibleIds = $visibleIds->merge($pendingIds)->unique()->values();
        }

        return $visibleIds;
    }
}
