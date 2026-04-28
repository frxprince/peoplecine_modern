<?php

namespace App\Models;

use App\Support\LegacyHtmlFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Post extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'topic_id',
        'author_id',
        'legacy_source_table',
        'legacy_source_id',
        'position_in_topic',
        'body_html',
        'ip_address',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'has_image_attachment' => 'boolean',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'attachable_id')
            ->where('attachable_type', 'post')
            ->orderBy('slot_no');
    }

    public function imageAttachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'attachable_id')
            ->where('attachable_type', 'post')
            ->imageFiles()
            ->orderBy('slot_no');
    }

    public function changeLogs(): HasMany
    {
        return $this->hasMany(PostChangeLog::class)->latest();
    }

    public function hasPostedImage(): bool
    {
        if (array_key_exists('has_image_attachment', $this->attributes)) {
            return (bool) $this->attributes['has_image_attachment'];
        }

        if ($this->relationLoaded('attachments')) {
            /** @var Collection<int, Attachment> $attachments */
            $attachments = $this->attachments;

            return $attachments->contains(fn (Attachment $attachment) => $attachment->isImage());
        }

        return $this->imageAttachments()->exists();
    }

    public function renderedBodyHtml(): string
    {
        return LegacyHtmlFormatter::linkify($this->body_html);
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null
            && $this->author_id !== null
            && (int) $this->author_id === (int) $user->getKey();
    }

    public function isEditableBy(?User $user): bool
    {
        return $user !== null
            && ($user->canAccessAdminPanel() || $this->isOwnedBy($user));
    }

    public function isTopicStarter(): bool
    {
        return (int) $this->position_in_topic === 1;
    }

    public function wasEdited(): bool
    {
        if ($this->relationLoaded('changeLogs')) {
            /** @var Collection<int, PostChangeLog> $changeLogs */
            $changeLogs = $this->changeLogs;

            return $changeLogs->isNotEmpty();
        }

        return $this->changeLogs()->exists();
    }
}
