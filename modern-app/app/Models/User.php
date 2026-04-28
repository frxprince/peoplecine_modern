<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'legacy_memberx_id',
        'username',
        'email',
        'password',
        'password_reset_required',
        'role',
        'account_status',
        'legacy_level',
        'legacy_authorize',
        'visit_count',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
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
            'password_reset_required' => 'boolean',
            'legacy_level' => 'integer',
            'visit_count' => 'integer',
            'password' => 'hashed',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class, 'author_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    public function bookmarks(): BelongsToMany
    {
        return $this->belongsToMany(Topic::class, 'bookmarks')
            ->withTimestamps();
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['joined_at', 'last_read_at', 'last_read_message_id', 'archived_at', 'deleted_at'])
            ->withTimestamps();
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function directMessagePreferences(): HasMany
    {
        return $this->hasMany(DirectMessagePreference::class);
    }

    public function directedMessagePreferences(): HasMany
    {
        return $this->hasMany(DirectMessagePreference::class, 'target_user_id');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'author_id');
    }

    public function displayName(): string
    {
        return $this->profile?->display_name
            ?? $this->profile?->first_name
            ?? $this->username;
    }

    public function avatarUrl(): ?string
    {
        return $this->profile?->avatarUrl();
    }

    public function memberLevel(): int
    {
        return max(0, (int) ($this->legacy_level ?? 0));
    }

    public function memberLevelLabel(): string
    {
        return match (true) {
            $this->isProgrammer() => __('Level 10 Programmer'),
            $this->isAdmin() => __('Admin'),
            $this->memberLevel() >= 4 => __('Level 4 VIP'),
            $this->memberLevel() === 3 => __('Level 3 Topic Starter'),
            $this->memberLevel() === 2 => __('Level 2 Reply Member'),
            $this->memberLevel() === 1 => __('Level 1 Reply Member'),
            default => __('Level 0 Read Only'),
        };
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin'
            || $this->legacy_authorize === 'Admin'
            || $this->memberLevel() === 9;
    }

    public function isProgrammer(): bool
    {
        return $this->legacy_authorize === 'Programmer'
            || $this->memberLevel() === 10;
    }

    public function canAccessAdminPanel(): bool
    {
        return $this->isAdmin() || $this->isProgrammer();
    }

    public function isBanned(): bool
    {
        return $this->account_status === 'banned';
    }

    public function isDisabled(): bool
    {
        return $this->account_status === 'disabled';
    }

    public function canAuthenticate(): bool
    {
        return ! $this->isBanned() && ! $this->isDisabled();
    }

    public function requiresPasswordReset(): bool
    {
        return (bool) $this->password_reset_required;
    }

    public function canUsePrivateMessages(): bool
    {
        return $this->canAuthenticate();
    }

    public function hasBlockedUser(?User $other): bool
    {
        if ($other === null) {
            return false;
        }

        return $this->directMessagePreferences()
            ->where('target_user_id', $other->id)
            ->where('is_blocked', true)
            ->exists();
    }

    public function hasMutedUser(?User $other): bool
    {
        if ($other === null) {
            return false;
        }

        return $this->directMessagePreferences()
            ->where('target_user_id', $other->id)
            ->where('is_muted', true)
            ->exists();
    }

    public function canReply(): bool
    {
        return $this->canAccessAdminPanel() || $this->memberLevel() >= 1;
    }

    public function canCreateTopic(): bool
    {
        return $this->canAccessAdminPanel() || $this->memberLevel() >= 3;
    }

    public function canUploadImages(): bool
    {
        return $this->canAccessAdminPanel() || $this->memberLevel() >= 3;
    }

    public function canAccessVipRoom(): bool
    {
        return $this->canAccessAdminPanel() || $this->memberLevel() >= 4;
    }

    public function canViewMemberProfiles(): bool
    {
        return $this->canAccessAdminPanel() || $this->memberLevel() >= 3;
    }

    public function effectiveRoomAccessLevel(): int
    {
        return $this->canAccessAdminPanel()
            ? PHP_INT_MAX
            : $this->memberLevel();
    }
}
