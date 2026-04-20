<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrivateMessageController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $folder = (string) $request->query('folder', 'inbox');

        $baseQuery = Conversation::query();

        if ($folder === 'archived') {
            $baseQuery->archivedForUser($user);
        } else {
            $folder = 'inbox';
            $baseQuery->inboxForUser($user);
        }

        $conversations = $baseQuery
            ->with([
                'participants.profile',
                'latestMessage.sender.profile',
            ])
            ->withCount('messages')
            ->withMax('messages as latest_message_at', 'created_at')
            ->orderByDesc('latest_message_at')
            ->paginate(20)
            ->withQueryString()
            ->through(function (Conversation $conversation) use ($user): Conversation {
                $conversation->setAttribute('is_unread_for_viewer', $conversation->hasUnreadMessagesFor($user));
                $conversation->setAttribute('is_muted_for_viewer', $user->hasMutedUser($conversation->otherParticipantFor($user)));

                return $conversation;
            });

        return view('messages.index', [
            'title' => 'Private Messages',
            'conversations' => $conversations,
            'user' => $user,
            'activeFolder' => $folder,
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $recipient = null;

        if ($request->filled('to')) {
            $recipient = User::query()
                ->with('profile')
                ->whereKey($request->integer('to'))
                ->first();
        }

        return view('messages.create', [
            'title' => 'New Private Message',
            'user' => $user,
            'recipient' => $recipient,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $sender = $request->user();

        if (! $sender->canUsePrivateMessages()) {
            abort(403);
        }

        $validated = $request->validate([
            'recipient' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        $recipient = $this->resolveRecipient($validated['recipient']);

        if ($recipient === null) {
            throw ValidationException::withMessages([
                'recipient' => 'That member could not be found.',
            ]);
        }

        if ((int) $recipient->id === (int) $sender->id) {
            throw ValidationException::withMessages([
                'recipient' => 'You cannot send a private message to yourself.',
            ]);
        }

        if (! $recipient->canUsePrivateMessages()) {
            throw ValidationException::withMessages([
                'recipient' => 'That member cannot receive private messages right now.',
            ]);
        }

        $this->ensureMessagingAllowed($sender, $recipient);

        $conversation = DB::transaction(function () use ($sender, $recipient, $validated): Conversation {
            $now = now();

            $conversation = Conversation::query()->create([
                'subject' => $this->nullableString($validated['subject'] ?? null),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $message = $conversation->messages()->create([
                'sender_id' => $sender->id,
                'body_html' => trim((string) $validated['message']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $conversation->participants()->attach([
                $sender->id => [
                    'joined_at' => $now,
                    'last_read_at' => $now,
                    'last_read_message_id' => $message->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $recipient->id => [
                    'joined_at' => $now,
                    'last_read_at' => null,
                    'last_read_message_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);

            return $conversation;
        });

        return redirect()
            ->route('messages.show', $conversation)
            ->with('status', 'Private message sent.');
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $user = $request->user();
        $this->authorizeConversation($conversation, $user);

        $conversation->load([
            'participants.profile',
            'messages.sender.profile',
        ]);

        $lastVisibleMessageId = (int) ($conversation->messages->max('id') ?? 0);

        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
            'last_read_message_id' => $lastVisibleMessageId > 0 ? $lastVisibleMessageId : null,
            'updated_at' => now(),
        ]);

        $otherParticipants = $conversation->participants
            ->reject(fn (User $participant): bool => (int) $participant->id === (int) $user->id)
            ->values();

        $primaryOtherParticipant = $conversation->otherParticipantFor($user);
        $isBlocked = $user->hasBlockedUser($primaryOtherParticipant);
        $isMuted = $user->hasMutedUser($primaryOtherParticipant);
        $isBlockedByParticipant = $primaryOtherParticipant?->hasBlockedUser($user) ?? false;
        $viewerParticipant = $conversation->participantFor($user);
        $isArchived = $viewerParticipant?->pivot?->archived_at !== null;
        $canReply = $primaryOtherParticipant !== null
            && $user->canUsePrivateMessages()
            && $primaryOtherParticipant->canUsePrivateMessages()
            && ! $isBlocked
            && ! $isBlockedByParticipant;

        return view('messages.show', [
            'title' => $conversation->subjectLineFor($user),
            'conversation' => $conversation,
            'user' => $user,
            'otherParticipants' => $otherParticipants,
            'primaryOtherParticipant' => $primaryOtherParticipant,
            'isBlocked' => $isBlocked,
            'isMuted' => $isMuted,
            'isBlockedByParticipant' => $isBlockedByParticipant,
            'isArchived' => $isArchived,
            'canReply' => $canReply,
        ]);
    }

    public function reply(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeConversation($conversation, $user);

        if (! $user->canUsePrivateMessages()) {
            abort(403);
        }

        $conversation->loadMissing('participants.profile');
        $otherParticipant = $conversation->otherParticipantFor($user);

        if ($otherParticipant === null) {
            abort(422);
        }

        $this->ensureMessagingAllowed($user, $otherParticipant);

        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $now = now();

        DB::transaction(function () use ($conversation, $user, $validated, $now): void {
            $message = $conversation->messages()->create([
                'sender_id' => $user->id,
                'body_html' => trim((string) $validated['message']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('conversation_participants')
                ->where('conversation_id', $conversation->id)
                ->whereNull('deleted_at')
                ->update([
                    'archived_at' => null,
                    'updated_at' => $now,
                ]);

            DB::table('conversation_participants')
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $user->id)
                ->update([
                    'last_read_at' => $now,
                    'last_read_message_id' => $message->id,
                    'updated_at' => $now,
                ]);

            $conversation->forceFill([
                'updated_at' => $now,
            ])->save();
        });

        return redirect()
            ->route('messages.show', $conversation)
            ->with('status', 'Reply sent.');
    }

    public function archive(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeConversation($conversation, $user);

        $conversation->participants()->updateExistingPivot($user->id, [
            'archived_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('messages.index')
            ->with('status', 'Conversation archived.');
    }

    public function unarchive(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeConversation($conversation, $user);

        $conversation->participants()->updateExistingPivot($user->id, [
            'archived_at' => null,
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('messages.index', ['folder' => 'archived'])
            ->with('status', 'Conversation moved back to inbox.');
    }

    public function destroy(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeConversation($conversation, $user);

        $conversation->participants()->updateExistingPivot($user->id, [
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('messages.index')
            ->with('status', 'Conversation removed from your messages.');
    }

    public function destroyMany(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'conversation_ids' => ['required', 'array', 'min:1'],
            'conversation_ids.*' => ['integer'],
            'folder' => ['nullable', 'string', 'in:inbox,archived'],
        ]);

        $conversationIds = collect($validated['conversation_ids'])
            ->map(fn (mixed $value): int => (int) $value)
            ->filter(fn (int $value): bool => $value > 0)
            ->unique()
            ->values();

        abort_if($conversationIds->isEmpty(), 422);

        $authorizedIds = Conversation::query()
            ->whereIn('id', $conversationIds)
            ->whereHas('participants', function (Builder $query) use ($user): void {
                $query->where('users.id', $user->id)
                    ->whereNull('conversation_participants.deleted_at');
            })
            ->pluck('id');

        abort_unless($authorizedIds->count() === $conversationIds->count(), 403);

        DB::table('conversation_participants')
            ->where('user_id', $user->id)
            ->whereIn('conversation_id', $authorizedIds)
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        $folder = $validated['folder'] ?? 'inbox';
        $redirectParameters = $folder === 'archived' ? ['folder' => 'archived'] : [];

        return redirect()
            ->route('messages.index', $redirectParameters)
            ->with('status', 'Selected conversations removed from your messages.');
    }

    private function authorizeConversation(Conversation $conversation, User $user): void
    {
        $isParticipant = $conversation->participants()
            ->where('users.id', $user->id)
            ->whereNull('conversation_participants.deleted_at')
            ->exists();

        abort_unless($isParticipant, 403);
    }

    private function ensureMessagingAllowed(User $sender, User $recipient): void
    {
        if ($sender->hasBlockedUser($recipient)) {
            throw ValidationException::withMessages([
                'recipient' => 'You have blocked this member. Unblock them first to send a message.',
            ]);
        }

        if ($recipient->hasBlockedUser($sender)) {
            throw ValidationException::withMessages([
                'recipient' => 'This member is not accepting private messages from you.',
            ]);
        }
    }

    private function resolveRecipient(string $login): ?User
    {
        $normalized = Str::lower(trim($login));

        return User::query()
            ->with('profile')
            ->where(function (Builder $query) use ($normalized): void {
                $query->whereRaw('LOWER(username) = ?', [$normalized])
                    ->orWhereRaw('LOWER(email) = ?', [$normalized]);
            })
            ->first();
    }

    private function nullableString(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
