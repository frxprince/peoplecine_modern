<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Topic extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'legacy_topic_id',
        'room_id',
        'author_id',
        'title',
        'visibility_level',
        'is_pinned',
        'is_locked',
        'view_count',
        'reply_count',
        'legacy_rate',
        'first_post_id',
        'last_post_id',
        'last_posted_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
            'has_posted_image' => 'boolean',
            'last_posted_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class)->orderBy('position_in_topic');
    }

    public function postsWithImages(): HasMany
    {
        return $this->hasMany(Post::class)->whereHas('imageAttachments');
    }

    public function hasPostedImage(): bool
    {
        if (array_key_exists('has_posted_image', $this->attributes)) {
            return (bool) $this->attributes['has_posted_image'];
        }

        return $this->postsWithImages()->exists();
    }

    public function isNewlyPosted(): bool
    {
        $threshold = now()->subDays((int) config('peoplecine.new_post_days', 3));
        $activityAt = $this->last_posted_at ?? $this->created_at;

        return $activityAt instanceof Carbon && $activityAt->greaterThanOrEqualTo($threshold);
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        return $query->whereHas('room', fn (Builder $roomQuery) => $roomQuery->visibleTo($user));
    }
}
