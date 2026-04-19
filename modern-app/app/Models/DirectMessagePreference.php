<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectMessagePreference extends Model
{
    protected $fillable = [
        'user_id',
        'target_user_id',
        'is_blocked',
        'is_muted',
    ];

    protected function casts(): array
    {
        return [
            'is_blocked' => 'boolean',
            'is_muted' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
