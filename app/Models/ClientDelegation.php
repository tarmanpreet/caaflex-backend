<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientDelegation extends Model
{
    use HasFactory;

    public const SCOPES = ['view', 'documents', 'upload', 'message', 'book'];

    protected $fillable = ['client_profile_id', 'delegate_user_id', 'granted_by', 'relationship', 'scopes', 'accepted_at', 'expires_at', 'revoked_at'];

    protected function casts(): array
    {
        return ['scopes' => 'array', 'accepted_at' => 'datetime', 'expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ClientProfile::class, 'client_profile_id');
    }

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegate_user_id');
    }

    public static function activeFor(User $user, string $scope = 'view'): Builder
    {
        return static::query()->where('delegate_user_id', $user->id)->whereNotNull('accepted_at')->whereNull('revoked_at')->where('expires_at', '>', now())->whereJsonContains('scopes', $scope);
    }

    public function status(): string
    {
        return $this->revoked_at ? 'revoked' : ($this->expires_at->isPast() ? 'expired' : ($this->accepted_at ? 'active' : 'pending'));
    }
}
