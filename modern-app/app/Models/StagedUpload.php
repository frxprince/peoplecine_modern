<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StagedUpload extends Model
{
    protected $fillable = [
        'token',
        'user_id',
        'purpose',
        'original_filename',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'staged_path',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'claimed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isClaimed(): bool
    {
        return $this->claimed_at !== null;
    }
}
