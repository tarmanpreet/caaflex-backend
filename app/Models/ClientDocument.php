<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientDocument extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_profile_id',
        'uploaded_by',
        'original_name',
        'disk_path',
        'mime_type',
        'file_size',
        'description',
    ];

    /**
     * Get the client profile that owns this document.
     */
    public function clientProfile(): BelongsTo
    {
        return $this->belongsTo('App\Models\ClientProfile');
    }

    /**
     * Get the user who uploaded this document.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
