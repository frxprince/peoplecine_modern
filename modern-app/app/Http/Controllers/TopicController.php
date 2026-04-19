<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Post;
use App\Models\PostChangeLog;
use App\Models\Topic;
use App\Support\PostImageUploadManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TopicController extends Controller
{
    public function __construct(
        private readonly PostImageUploadManager $uploadManager,
    ) {
    }

    public function show(Request $request, Topic $topic): View
    {
        $topic->load(['room', 'author.profile'])
            ->loadExists(['postsWithImages as has_posted_image']);

        abort_unless($topic->room?->isVisibleTo($request->user()) ?? false, 403);

        $isBookmarked = $request->user()?->bookmarks()
            ->whereKey($topic->id)
            ->exists() ?? false;

        $posts = $topic->posts()
            ->with(['author.profile', 'attachments', 'changeLogs.editor.profile'])
            ->withExists(['imageAttachments as has_image_attachment'])
            ->get();

        return view('topics.show', [
            'topic' => $topic,
            'posts' => $posts,
            'isBookmarked' => $isBookmarked,
        ]);
    }

    public function storeReply(Request $request, Topic $topic): RedirectResponse
    {
        $topic->load('room');

        $user = $request->user();
        $maxAttachments = max(1, (int) config('peoplecine.post_image_limit', 12));
        $maxAttachmentKilobytes = max(1, (int) config('peoplecine.post_image_max_kb', 4096));

        abort_unless($topic->room?->isVisibleTo($user) ?? false, 403);
        abort_unless($user?->canReply(), 403);
        abort_if($topic->is_locked && ! $user?->isAdmin(), 403);

        $validated = $request->validate([
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

        $now = now();
        $position = ((int) $topic->posts()->withTrashed()->max('position_in_topic')) + 1;

        $post = Post::query()->create([
            'topic_id' => $topic->id,
            'author_id' => $user->id,
            'legacy_source_table' => 'modern_reply',
            'legacy_source_id' => $this->nextModernSourceId('modern_reply'),
            'position_in_topic' => $position,
            'body_html' => trim($validated['body_html']),
            'ip_address' => $request->ip(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $nextSlot = $this->uploadManager->persistUploadedFilesForPost($user, $post, $attachments, 'reply', $post->created_at, 1);
        $this->uploadManager->claimStagedUploadsForPost($user, $post, $stagedUploads, 'reply', $post->created_at, $nextSlot);

        $topic->forceFill([
            'reply_count' => max(0, $position - 1),
            'last_post_id' => $post->id,
            'last_posted_at' => $post->created_at,
            'updated_at' => $post->created_at,
        ])->save();

        return redirect()
            ->route('topics.show', $topic)
            ->with('status', 'Your reply has been posted.');
    }

    public function updatePost(Request $request, Topic $topic, Post $post): RedirectResponse
    {
        $topic->load('room');

        abort_unless((int) $post->topic_id === (int) $topic->id, 404);
        abort_unless($topic->room?->isVisibleTo($request->user()) ?? false, 403);
        abort_unless($post->isEditableBy($request->user()), 403);

        $user = $request->user();
        $maxAttachments = max(1, (int) config('peoplecine.post_image_limit', 12));
        $maxAttachmentKilobytes = max(1, (int) config('peoplecine.post_image_max_kb', 4096));

        $validated = $request->validate([
            'editing_post_id' => ['required', 'integer'],
            'message_body' => ['required', 'string'],
            'topic_title' => ['nullable', 'string', 'max:500'],
            'remove_attachment_ids' => ['nullable', 'array'],
            'remove_attachment_ids.*' => ['integer'],
            'attachments' => ['nullable', 'array', 'max:'.$maxAttachments],
            'attachments.*' => ['file', 'image', 'mimes:jpg,jpeg,png,gif,bmp,webp', 'max:'.$maxAttachmentKilobytes],
            'staged_uploads' => ['nullable', 'array', 'max:'.$maxAttachments],
            'staged_uploads.*' => ['string', 'max:100'],
        ]);

        abort_unless((int) $validated['editing_post_id'] === (int) $post->id, 403);

        $attachments = array_values(array_filter(
            $request->file('attachments', []),
            static fn (mixed $file): bool => $file instanceof UploadedFile
        ));
        $stagedUploads = array_values(array_filter(
            $validated['staged_uploads'] ?? [],
            static fn (mixed $token): bool => trim((string) $token) !== ''
        ));
        $removeAttachmentIds = array_values(array_filter(
            $validated['remove_attachment_ids'] ?? [],
            static fn (mixed $attachmentId): bool => (int) $attachmentId > 0
        ));

        if (($attachments !== [] || $stagedUploads !== []) && ! $user?->canUploadImages()) {
            throw ValidationException::withMessages([
                'attachments' => 'Your current member level cannot upload images yet.',
            ]);
        }

        $remainingAttachments = max(0, $post->attachments()->count() - count($removeAttachmentIds));
        $incomingAttachments = count($attachments) + count($stagedUploads);

        if (($remainingAttachments + $incomingAttachments) > $maxAttachments) {
            throw ValidationException::withMessages([
                'attachments' => "You can keep up to {$maxAttachments} images on one post.",
            ]);
        }

        $newBody = trim($validated['message_body']);
        $newTopicTitle = trim((string) ($validated['topic_title'] ?? ''));
        $bodyChanged = $newBody !== trim((string) $post->body_html);
        $titleChanged = $post->isTopicStarter()
            && $newTopicTitle !== ''
            && $newTopicTitle !== trim((string) $topic->title);
        $removedAttachments = $post->attachments()
            ->whereIn('id', $removeAttachmentIds)
            ->count();
        $addedAttachments = count($attachments) + count($stagedUploads);

        if (! $bodyChanged && ! $titleChanged && $removedAttachments === 0 && $addedAttachments === 0) {
            return redirect()
                ->route('topics.show', $topic)
                ->with('status', 'No changes were made to this post.');
        }

        DB::transaction(function () use (
            $user,
            $post,
            $topic,
            $newBody,
            $bodyChanged,
            $newTopicTitle,
            $titleChanged,
            $removeAttachmentIds,
            $attachments,
            $stagedUploads,
            $removedAttachments,
            $addedAttachments
        ): void {
            if ($bodyChanged) {
                $post->forceFill([
                    'body_html' => $newBody,
                    'updated_at' => now(),
                ])->save();
            }

            if ($titleChanged) {
                $topic->forceFill([
                    'title' => $newTopicTitle,
                    'updated_at' => now(),
                ])->save();
            }

            $this->uploadManager->syncPostAttachments(
                $user,
                $post,
                $removeAttachmentIds,
                $attachments,
                $stagedUploads,
                $post->isTopicStarter() ? 'topic' : 'reply',
                $post->created_at ?? now()
            );

            PostChangeLog::query()->create([
                'post_id' => $post->id,
                'editor_user_id' => $user?->id,
                'summary' => $this->buildChangeSummary(
                    $bodyChanged,
                    $titleChanged,
                    $removedAttachments,
                    $addedAttachments
                ),
                'metadata' => [
                    'body_changed' => $bodyChanged,
                    'topic_title_changed' => $titleChanged,
                    'removed_image_count' => $removedAttachments,
                    'added_image_count' => $addedAttachments,
                ],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()
            ->route('topics.show', $topic)
            ->with('status', 'Your post has been updated.');
    }

    public function destroyTopic(Request $request, Topic $topic): RedirectResponse
    {
        $topic->load('room');

        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless($topic->room?->isVisibleTo($request->user()) ?? false, 403);

        DB::transaction(function () use ($topic): void {
            $topic->posts()->get()->each->delete();
            $topic->delete();
        });

        return redirect()
            ->route('rooms.show', $topic->room)
            ->with('status', 'The topic has been deleted.');
    }

    public function destroyPost(Request $request, Topic $topic, Post $post): RedirectResponse
    {
        $topic->load('room');

        abort_unless((int) $post->topic_id === (int) $topic->id, 404);
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless($topic->room?->isVisibleTo($request->user()) ?? false, 403);
        abort_if($post->isTopicStarter(), 403);

        DB::transaction(function () use ($topic, $post): void {
            $post->delete();
            $this->refreshTopicPointers($topic->fresh());
        });

        return redirect()
            ->route('topics.show', $topic)
            ->with('status', 'The reply has been deleted.');
    }

    public function storeBookmark(Request $request, Topic $topic): RedirectResponse
    {
        $topic->load('room');

        $user = $request->user();

        abort_unless($topic->room?->isVisibleTo($user) ?? false, 403);

        $user?->bookmarks()->syncWithoutDetaching([$topic->id]);

        return redirect()
            ->back()
            ->with('status', 'Topic saved to Saved Topics.');
    }

    public function destroyBookmark(Request $request, Topic $topic): RedirectResponse
    {
        $topic->load('room');

        $user = $request->user();

        abort_unless($topic->room?->isVisibleTo($user) ?? false, 403);

        $user?->bookmarks()->detach($topic->id);

        return redirect()
            ->back()
            ->with('status', 'Topic removed from Saved Topics.');
    }

    private function nextModernSourceId(string $sourceTable): int
    {
        return ((int) Post::query()
            ->withTrashed()
            ->where('legacy_source_table', $sourceTable)
            ->max('legacy_source_id')) + 1;
    }

    private function buildChangeSummary(
        bool $bodyChanged,
        bool $titleChanged,
        int $removedAttachments,
        int $addedAttachments
    ): string {
        $changes = [];

        if ($bodyChanged) {
            $changes[] = 'message updated';
        }

        if ($titleChanged) {
            $changes[] = 'topic title changed';
        }

        if ($removedAttachments > 0) {
            $changes[] = $removedAttachments === 1
                ? '1 image removed'
                : "{$removedAttachments} images removed";
        }

        if ($addedAttachments > 0) {
            $changes[] = $addedAttachments === 1
                ? '1 image added'
                : "{$addedAttachments} images added";
        }

        return ucfirst(implode(', ', $changes));
    }

    private function refreshTopicPointers(Topic $topic): void
    {
        $remainingPosts = $topic->posts()->get();
        $firstPost = $remainingPosts->first();
        $lastPost = $remainingPosts->last();

        if ($firstPost === null || $lastPost === null) {
            $topic->delete();

            return;
        }

        $topic->forceFill([
            'first_post_id' => $firstPost->id,
            'last_post_id' => $lastPost->id,
            'reply_count' => max(0, $remainingPosts->count() - 1),
            'last_posted_at' => $lastPost->created_at,
            'updated_at' => now(),
        ])->save();
    }
}
