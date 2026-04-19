<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'display_name',
        'phone',
        'company_name',
        'company_phone',
        'province',
        'postal_code',
        'address',
        'hide_address',
        'biography',
        'interests',
        'avatar_path',
    ];

    protected function casts(): array
    {
        return [
            'interests' => 'array',
            'hide_address' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function normalizedAvatarPath(): ?string
    {
        $path = trim((string) $this->avatar_path);

        if ($path === '') {
            return null;
        }

        $normalized = str_replace('\\', '/', $path);

        if (! str_contains($normalized, '/')) {
            return 'icons/'.$normalized;
        }

        return ltrim($normalized, '/');
    }

    public function avatarUrl(): ?string
    {
        $path = $this->normalizedAvatarPath();

        if ($path === null || $this->user_id === null) {
            return null;
        }

        return route('avatars.show', $this->user_id);
    }

    public function addressVisibleTo(?User $viewer): bool
    {
        if (! $this->hide_address) {
            return true;
        }

        return $viewer !== null
            && ($viewer->isAdmin() || (int) $viewer->getKey() === (int) $this->user_id);
    }
}
