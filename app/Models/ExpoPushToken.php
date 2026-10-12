<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpoPushToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'token_hash',
        'device_id',
        'auth_session_id',
        'platform',
        'device_name',
        'app_version',
        'last_registered_at',
    ];

    protected $hidden = [
        'token',
        'token_hash',
        'auth_session_id',
    ];

    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
            'last_registered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
