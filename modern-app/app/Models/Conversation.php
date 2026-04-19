<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = [
        'legacy_source',
        'subject',
    ];

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['joined_at', 'last_read_at', 'last_read_message_id', 'archived_at', 'deleted_at'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->latest('created_at');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany('created_at');
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('participants', function (Builder $participantQuery) use ($user): void {
            $participantQuery
                ->where('users.id', $user->id)
                ->whereNull('conversation_participants.deleted_at');
        });
    }

    public function scopeArchivedForUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('participants', function (Builder $participantQuery) use ($user): void {
            $participantQuery
                ->where('users.id', $user->id)
                ->whereNull('conversation_participants.deleted_at')
                ->whereNotNull('conversation_participants.archived_at');
        });
    }

    public function scopeInboxForUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('participants', function (Builder $participantQuery) use ($user): void {
            $participantQuery
                ->where('users.id', $user->id)
                ->whereNull('conversation_participants.deleted_at')
                ->whereNull('conversation_participants.archived_at');
        });
    }

    public function subjectLineFor(User $viewer): string
    {
        $subject = trim((string) ($this->subject ?? ''));

        if ($subject !== '') {
            return $subject;
        }

        $otherNames = $this->participants
            ->reject(fn (User $participant): bool => (int) $participant->id === (int) $viewer->id)
            ->map(fn (User $participant): string => $participant->displayName())
            ->values();

        if ($otherNames->isNotEmpty()) {
            return 'Message with '.$otherNames->join(', ');
        }

        return 'Private message';
    }

    public function hasUnreadMessagesFor(User $viewer): bool
    {
        $pivot = $this->participants
            ->firstWhere('id', $viewer->id)
            ?->pivot;

        $lastReadMessageId = (int) ($pivot?->last_read_message_id ?? 0);

        return $this->messages()
            ->where('sender_id', '!=', $viewer->id)
            ->where('id', '>', $lastReadMessageId)
            ->when($this->otherParticipantFor($viewer) !== null, function (Builder $query) use ($viewer): void {
                $otherParticipant = $this->otherParticipantFor($viewer);

                if ($viewer->hasMutedUser($otherParticipant)) {
                    $query->whereRaw('1 = 0');
                }
            })
            ->exists();
    }

    public function participantFor(User $viewer): ?User
    {
        return $this->participants->firstWhere('id', $viewer->id);
    }

    public function otherParticipantFor(User $viewer): ?User
    {
        return $this->participants
            ->first(fn (User $participant): bool => (int) $participant->id !== (int) $viewer->id);
    }
}
