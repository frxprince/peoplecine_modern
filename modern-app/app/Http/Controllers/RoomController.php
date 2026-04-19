<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Post;
use App\Models\Room;
use App\Models\Topic;
use App\Support\PostImageUploadManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    public function __construct(
        private readonly PostImageUploadManager $uploadManager,
    ) {
    }

    public function show(Request $request, Room $room): View
    {
        abort_unless($room->isVisibleTo($request->user()), 403);

        $topics = $room->topics()
            ->with(['author.profile'])
            ->withExists(['postsWithImages as has_posted_image'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('last_posted_at')
            ->paginate(25);

        return view('rooms.show', [
            'room' => $room,
            'topics' => $topics,
        ]);
    }

    public function storeTopic(Request $request, Room $room): RedirectResponse
    {
        $user = $request->user();
        $maxAttachments = max(1, (int) config('peoplecine.post_image_limit', 12));
        $maxAttachmentKilobytes = max(1, (int) config('peoplecine.post_image_max_kb', 4096));

        abort_unless($room->isVisibleTo($user), 403);
        abort_unless($user?->canCreateTopic(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:500'],
            'body_html' => ['required', 'string'],
            'attachments' => ['nullable', 'array', 'max:'.$maxAttachments],
            'attachments.*' => ['file', 'image', 'mimes:jpg,jpeg,png,gif,bmp,webp', 'max:'.$maxAttachmentKilobytes],
            'staged_uploads' => ['nullable', 'array', 'max:'.$maxAttachments],
            'staged_uploads.*' => ['string', 'max:100'],
        ]);

        $attachments = array_values(array_filter(
            $request->file('attachments', []),
            static fn (mixed $file): bool => $file instanceof UploadedFile
        ));
        $stagedUploads = array_values(array_filter(
            $validated['staged_uploads'] ?? [],
            static fn (mixed $token): bool => trim((string) $token) !== ''
        ));

        if (($attachments !== [] || $stagedUploads !== []) && ! $user->canUploadImages()) {
            throw ValidationException::withMessages([
                'attachments' => 'Your current member level cannot upload images yet.',
            ]);
        }

        $topic = DB::transaction(function () use ($validated, $attachments, $stagedUploads, $room, $request, $user): Topic {
            $now = now();

            $topic = Topic::query()->create([
                'room_id' => $room->id,
                'author_id' => $user->id,
                'title' => trim($validated['title']),
                'visibility_level' => 0,
                'is_pinned' => false,
                'is_locked' => false,
                'view_count' => 0,
                'reply_count' => 0,
                'legacy_rate' => null,
                'last_posted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $post = Post::query()->create([
                'topic_id' => $topic->id,
                'author_id' => $user->id,
                'legacy_source_table' => 'modern_topic',
                'legacy_source_id' => $this->nextModernSourceId('modern_topic'),
                'position_in_topic' => 1,
                'body_html' => trim($validated['body_html']),
                'ip_address' => $request->ip(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $nextSlot = $this->uploadManager->persistUploadedFilesForPost($user, $post, $attachments, 'topic', $post->created_at, 1);
            $this->uploadManager->claimStagedUploadsForPost($user, $post, $stagedUploads, 'topic', $post->created_at, $nextSlot);

            $topic->forceFill([
                'first_post_id' => $post->id,
                'last_post_id' => $post->id,
                'last_posted_at' => $post->created_at,
                'updated_at' => $post->created_at,
            ])->save();

            return $topic->fresh();
        });

        return redirect()
            ->route('topics.show', $topic)
            ->with('status', 'Your new topic has been posted.');
    }

    private function nextModernSourceId(string $sourceTable): int
    {
        return ((int) Post::query()
            ->withTrashed()
            ->where('legacy_source_table', $sourceTable)
            ->max('legacy_source_id')) + 1;
    }
}
