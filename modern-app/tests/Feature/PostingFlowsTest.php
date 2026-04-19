<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Post;
use App\Models\PostChangeLog;
use App\Models\Room;
use App\Models\StagedUpload;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostingFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_level_three_member_can_create_topic_with_multiple_images(): void
    {
        $root = storage_path('app/private/test-topic-posting');
        File::ensureDirectoryExists($root.DIRECTORY_SEPARATOR.'uploads');
        config()->set('peoplecine.legacy_wboard_root', $root);

        $user = $this->createMember('topic-starter', 3);
        $room = $this->createRoom('general-room');

        $firstUpload = $this->makeImageUpload('upload-1.png');
        $secondUpload = $this->makeImageUpload('upload-2.png');

        $response = $this->actingAs($user)->post(route('rooms.topics.store', $room), [
            'title' => 'My brand new topic',
            'body_html' => 'Opening post body',
            'attachments' => [$firstUpload, $secondUpload],
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertTrue(
            $response->isRedirect() || $response->isSuccessful(),
            'Unexpected response status '.$response->getStatusCode().' location='.($response->headers->get('Location') ?? 'none')
        );

        $topic = Topic::query()->first();
        $post = Post::query()->where('legacy_source_table', 'modern_topic')->first();
        $attachments = Attachment::query()->orderBy('slot_no')->get();

        $this->assertNotNull($topic);
        $this->assertNotNull($post);
        $this->assertCount(2, $attachments);
        $response->assertRedirect(route('topics.show', $topic));
        $this->assertSame('My brand new topic', $topic->title);
        $this->assertSame($user->id, $topic->author_id);
        $this->assertSame(1, $post->position_in_topic);
        $this->assertSame([1, 2], $attachments->pluck('slot_no')->all());
        $this->assertSame(['upload-1.png', 'upload-2.png'], $attachments->pluck('original_filename')->all());
        $this->assertSame('uploads', dirname((string) $attachments->first()?->legacy_path));
    }

    public function test_level_one_member_cannot_create_topic(): void
    {
        $user = $this->createMember('reply-only', 1);
        $room = $this->createRoom('general-room');

        $this->actingAs($user)->post(route('rooms.topics.store', $room), [
            'title' => 'Not allowed topic',
            'body_html' => 'Body',
        ])->assertForbidden();

        $this->assertDatabaseCount('topics', 0);
    }

    public function test_level_one_member_can_post_reply_without_image(): void
    {
        $user = $this->createMember('reply-member', 1);
        $topic = $this->createTopic();

        $response = $this->actingAs($user)->post(route('topics.replies.store', $topic), [
            'body_html' => 'This is my reply',
        ]);

        $response->assertRedirect(route('topics.show', $topic));
        $this->assertDatabaseHas('posts', [
            'topic_id' => $topic->id,
            'legacy_source_table' => 'modern_reply',
            'author_id' => $user->id,
            'position_in_topic' => 2,
        ]);

        $topic->refresh();
        $this->assertSame(1, $topic->reply_count);
    }

    public function test_level_three_member_can_post_reply_with_multiple_images(): void
    {
        $root = storage_path('app/private/test-reply-multi-image-posting');
        File::ensureDirectoryExists($root.DIRECTORY_SEPARATOR.'uploads');
        config()->set('peoplecine.legacy_wboard_root', $root);

        $user = $this->createMember('reply-starter', 3);
        $topic = $this->createTopic();
        $firstUpload = $this->makeImageUpload('reply-1.png');
        $secondUpload = $this->makeImageUpload('reply-2.png');

        $response = $this->actingAs($user)->post(route('topics.replies.store', $topic), [
            'body_html' => 'Reply with two images',
            'attachments' => [$firstUpload, $secondUpload],
        ]);

        $response->assertRedirect(route('topics.show', $topic));

        $post = Post::query()->where('legacy_source_table', 'modern_reply')->first();
        $attachments = Attachment::query()
            ->where('attachable_type', 'post')
            ->where('attachable_id', $post?->id)
            ->orderBy('slot_no')
            ->get();

        $this->assertNotNull($post);
        $this->assertCount(2, $attachments);
        $this->assertSame([1, 2], $attachments->pluck('slot_no')->all());
        $this->assertSame(['reply-1.png', 'reply-2.png'], $attachments->pluck('original_filename')->all());
    }

    public function test_level_three_member_can_stage_uploads_and_create_topic(): void
    {
        $root = storage_path('app/private/test-staged-topic-posting');
        File::ensureDirectoryExists($root.DIRECTORY_SEPARATOR.'uploads');
        config()->set('peoplecine.legacy_wboard_root', $root);

        $user = $this->createMember('staged-topic-user', 3);
        $room = $this->createRoom('staged-room');

        $firstToken = $this->stageUploadFor($user, 'stage-topic-1.png');
        $secondToken = $this->stageUploadFor($user, 'stage-topic-2.png');

        $response = $this->actingAs($user)->post(route('rooms.topics.store', $room), [
            'title' => 'Topic from staged uploads',
            'body_html' => 'Body',
            'staged_uploads' => [$firstToken, $secondToken],
        ]);

        $topic = Topic::query()->first();
        $response->assertRedirect(route('topics.show', $topic));

        $attachments = Attachment::query()->orderBy('slot_no')->get();
        $this->assertCount(2, $attachments);
        $this->assertSame(['stage-topic-1.png', 'stage-topic-2.png'], $attachments->pluck('original_filename')->all());
        $this->assertDatabaseHas('staged_uploads', ['token' => $firstToken]);
        $this->assertDatabaseHas('staged_uploads', ['token' => $secondToken]);
        $this->assertNotNull(StagedUpload::query()->where('token', $firstToken)->value('claimed_at'));
        $this->assertNotNull(StagedUpload::query()->where('token', $secondToken)->value('claimed_at'));
    }

    public function test_level_three_member_can_stage_upload_and_post_reply(): void
    {
        $root = storage_path('app/private/test-staged-reply-posting');
        File::ensureDirectoryExists($root.DIRECTORY_SEPARATOR.'uploads');
        config()->set('peoplecine.legacy_wboard_root', $root);

        $user = $this->createMember('staged-reply-user', 3);
        $topic = $this->createTopic();
        $token = $this->stageUploadFor($user, 'stage-reply-1.png');

        $response = $this->actingAs($user)->post(route('topics.replies.store', $topic), [
            'body_html' => 'Reply with staged image',
            'staged_uploads' => [$token],
        ]);

        $response->assertRedirect(route('topics.show', $topic));

        $post = Post::query()->where('legacy_source_table', 'modern_reply')->first();
        $this->assertNotNull($post);
        $this->assertDatabaseHas('attachments', [
            'attachable_type' => 'post',
            'attachable_id' => $post->id,
            'original_filename' => 'stage-reply-1.png',
        ]);
        $this->assertNotNull(StagedUpload::query()->where('token', $token)->value('claimed_at'));
    }

    public function test_level_one_member_cannot_stage_upload_images(): void
    {
        $user = $this->createMember('no-stage', 1);

        $this->actingAs($user)->post(route('composer.uploads.store'), [
            'image' => $this->makeImageUpload('not-allowed.png'),
        ], [
            'Accept' => 'application/json',
        ])->assertForbidden();
    }

    public function test_level_one_member_cannot_upload_reply_image(): void
    {
        $root = storage_path('app/private/test-reply-image-rejection');
        File::ensureDirectoryExists($root.DIRECTORY_SEPARATOR.'uploads');
        config()->set('peoplecine.legacy_wboard_root', $root);

        $user = $this->createMember('reply-member', 1);
        $topic = $this->createTopic();
        $upload = $this->makeImageUpload();

        $response = $this->from(route('topics.show', $topic))->actingAs($user)->post(route('topics.replies.store', $topic), [
            'body_html' => 'Trying to upload',
            'attachments' => [$upload],
        ]);

        $response->assertRedirect(route('topics.show', $topic));
        $response->assertSessionHasErrors('attachments');
        $this->assertDatabaseMissing('posts', [
            'topic_id' => $topic->id,
            'legacy_source_table' => 'modern_reply',
            'author_id' => $user->id,
        ]);
    }

    public function test_level_zero_member_cannot_post_reply(): void
    {
        $user = $this->createMember('reader', 0);
        $topic = $this->createTopic();

        $this->actingAs($user)->post(route('topics.replies.store', $topic), [
            'body_html' => 'Not allowed',
        ])->assertForbidden();
    }

    public function test_owner_can_edit_topic_starter_post_and_title(): void
    {
        $user = $this->createMember('topic-editor', 3);
        $topic = $this->createTopicOwnedBy($user, 'Original Topic', 'Original body');

        $response = $this->actingAs($user)->put(route('topics.posts.update', [$topic, $topic->posts()->first()]), [
            'editing_post_id' => $topic->posts()->first()->id,
            'topic_title' => 'Edited Topic Title',
            'message_body' => 'Edited first post body',
        ]);

        $response->assertRedirect(route('topics.show', $topic));

        $topic->refresh();
        $post = $topic->posts()->first();

        $this->assertSame('Edited Topic Title', $topic->title);
        $this->assertSame('Edited first post body', $post?->body_html);
        $this->assertDatabaseHas('post_change_logs', [
            'post_id' => $post?->id,
            'editor_user_id' => $user->id,
        ]);

        $showResponse = $this->actingAs($user)->get(route('topics.show', $topic));
        $showResponse->assertOk();
        $showResponse->assertSee('Change log');
        $showResponse->assertSee('Edited 1 time');
    }

    public function test_owner_can_replace_reply_image(): void
    {
        $root = storage_path('app/private/test-edit-reply-images');
        File::ensureDirectoryExists($root.DIRECTORY_SEPARATOR.'uploads');
        config()->set('peoplecine.legacy_wboard_root', $root);

        $user = $this->createMember('reply-editor', 3);
        $topic = $this->createTopicOwnedBy($user, 'Owned Topic', 'Original topic body');
        $reply = $this->createReplyForTopic($topic, $user, 'Reply body');
        $existingAttachment = $this->createExistingAttachment($reply, $root, 'old-image.png');
        $stagedToken = $this->stageUploadFor($user, 'new-image.png');

        $response = $this->actingAs($user)->put(route('topics.posts.update', [$topic, $reply]), [
            'editing_post_id' => $reply->id,
            'message_body' => 'Reply body edited',
            'remove_attachment_ids' => [$existingAttachment->id],
            'staged_uploads' => [$stagedToken],
        ]);

        $response->assertRedirect(route('topics.show', $topic));

        $reply->refresh();
        $this->assertSame('Reply body edited', $reply->body_html);
        $this->assertDatabaseMissing('attachments', ['id' => $existingAttachment->id]);
        $this->assertDatabaseHas('attachments', [
            'attachable_type' => 'post',
            'attachable_id' => $reply->id,
            'original_filename' => 'new-image.png',
        ]);
        $this->assertDatabaseMissing('attachments', [
            'attachable_type' => 'post',
            'attachable_id' => $reply->id,
            'legacy_path' => 'uploads/old-image.png',
        ]);
    }

    public function test_non_owner_cannot_edit_someone_elses_post(): void
    {
        $owner = $this->createMember('owner-user', 3);
        $otherUser = $this->createMember('other-user', 3);
        $topic = $this->createTopicOwnedBy($owner, 'Protected Topic', 'Original body');
        $post = $topic->posts()->first();

        $this->actingAs($otherUser)->put(route('topics.posts.update', [$topic, $post]), [
            'editing_post_id' => $post->id,
            'topic_title' => 'Hacked Title',
            'message_body' => 'Hacked body',
        ])->assertForbidden();
    }

    public function test_admin_can_delete_reply(): void
    {
        $admin = $this->createAdmin('reply-admin');
        $owner = $this->createMember('reply-owner', 3);
        $topic = $this->createTopicOwnedBy($owner, 'Delete Reply Topic', 'Topic body');
        $reply = $this->createReplyForTopic($topic, $owner, 'Reply to remove');

        $response = $this->actingAs($admin)->delete(route('topics.posts.destroy', [$topic, $reply]));

        $response->assertRedirect(route('topics.show', $topic));
        $this->assertSoftDeleted('posts', ['id' => $reply->id]);

        $topic->refresh();
        $this->assertSame(0, (int) $topic->reply_count);
        $this->assertSame((int) $topic->first_post_id, (int) $topic->last_post_id);
    }

    public function test_admin_can_delete_topic(): void
    {
        $admin = $this->createAdmin('topic-admin');
        $owner = $this->createMember('topic-owner', 3);
        $topic = $this->createTopicOwnedBy($owner, 'Delete Whole Topic', 'Topic body');
        $reply = $this->createReplyForTopic($topic, $owner, 'Reply body');

        $response = $this->actingAs($admin)->delete(route('topics.destroy', $topic));

        $response->assertRedirect(route('rooms.show', $topic->room));
        $this->assertSoftDeleted('topics', ['id' => $topic->id]);
        $this->assertSoftDeleted('posts', ['id' => $reply->id]);
        $this->get(route('topics.show', $topic))->assertNotFound();
    }

    public function test_new_reply_source_id_skips_soft_deleted_modern_reply_ids(): void
    {
        $user = $this->createMember('reply-after-delete', 3);
        $topic = $this->createTopicOwnedBy($user, 'Source Id Topic', 'Topic body');
        $reply = $this->createReplyForTopic($topic, $user, 'Reply to delete');
        $reply->forceFill([
            'legacy_source_table' => 'modern_reply',
            'legacy_source_id' => 9001,
        ])->save();
        $reply->delete();

        $response = $this->actingAs($user)->post(route('topics.replies.store', $topic), [
            'body_html' => 'Reply after deletion',
        ]);

        $response->assertRedirect(route('topics.show', $topic));
        $this->assertDatabaseHas('posts', [
            'topic_id' => $topic->id,
            'legacy_source_table' => 'modern_reply',
            'legacy_source_id' => 9002,
            'body_html' => 'Reply after deletion',
        ]);
    }

    public function test_new_topic_source_id_skips_soft_deleted_modern_topic_ids(): void
    {
        $user = $this->createMember('topic-after-delete', 3);
        $room = $this->createRoom('topic-after-delete-room');
        $topic = $this->createTopicOwnedBy($user, 'Delete Me', 'Body');
        $topic->posts()->first()?->forceFill([
            'legacy_source_table' => 'modern_topic',
            'legacy_source_id' => 7001,
        ])->save();
        $topic->posts()->first()?->delete();

        $response = $this->actingAs($user)->post(route('rooms.topics.store', $room), [
            'title' => 'New topic after soft delete',
            'body_html' => 'Fresh topic body',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('posts', [
            'legacy_source_table' => 'modern_topic',
            'legacy_source_id' => 7002,
            'body_html' => 'Fresh topic body',
        ]);
    }

    public function test_non_admin_cannot_delete_topic_or_reply(): void
    {
        $owner = $this->createMember('delete-owner', 3);
        $otherUser = $this->createMember('delete-other', 3);
        $topic = $this->createTopicOwnedBy($owner, 'Protected Delete Topic', 'Topic body');
        $reply = $this->createReplyForTopic($topic, $owner, 'Reply body');

        $this->actingAs($otherUser)->delete(route('topics.posts.destroy', [$topic, $reply]))
            ->assertForbidden();

        $this->actingAs($otherUser)->delete(route('topics.destroy', $topic))
            ->assertForbidden();
    }

    public function test_room_and_topic_pages_render_rich_editor_markup(): void
    {
        $user = $this->createMember('editor-member', 3);
        $room = $this->createRoom('editor-room');
        $topic = $this->createTopicOwnedBy($user, 'Editor Topic', 'Existing body');

        $this->actingAs($user)
            ->get(route('rooms.show', $room))
            ->assertOk()
            ->assertSee('data-tinymce-editor', false)
            ->assertSee('data-tinymce-textarea', false);

        $this->actingAs($user)
            ->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee('data-tinymce-editor', false)
            ->assertSee('data-tinymce-textarea', false);
    }

    private function createMember(string $username, int $level): User
    {
        $user = User::query()->create([
            'username' => $username,
            'email' => $username.'@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => $level,
            'password_reset_required' => false,
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'display_name' => ucfirst(str_replace('-', ' ', $username)),
        ]);

        return $user;
    }

    private function createAdmin(string $username): User
    {
        $user = $this->createMember($username, 4);

        $user->forceFill([
            'role' => 'admin',
            'legacy_authorize' => 'Admin',
        ])->save();

        return $user->fresh();
    }

    private function createRoom(string $slug): Room
    {
        return Room::query()->create([
            'slug' => $slug,
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);
    }

    private function createTopic(): Topic
    {
        $room = $this->createRoom('topic-room');

        $topic = Topic::query()->create([
            'room_id' => $room->id,
            'title' => 'Existing Topic',
            'visibility_level' => 0,
            'is_pinned' => false,
            'is_locked' => false,
            'view_count' => 0,
            'reply_count' => 0,
            'last_posted_at' => now(),
        ]);

        Post::query()->create([
            'topic_id' => $topic->id,
            'legacy_source_table' => 'topics',
            'legacy_source_id' => 1,
            'position_in_topic' => 1,
            'body_html' => 'Existing body',
        ]);

        return $topic;
    }

    private function createTopicOwnedBy(User $user, string $title, string $body): Topic
    {
        $room = $this->createRoom('owned-topic-room-'.Str::random(6));

        $topic = Topic::query()->create([
            'room_id' => $room->id,
            'author_id' => $user->id,
            'title' => $title,
            'visibility_level' => 0,
            'is_pinned' => false,
            'is_locked' => false,
            'view_count' => 0,
            'reply_count' => 0,
            'last_posted_at' => now(),
        ]);

        $post = Post::query()->create([
            'topic_id' => $topic->id,
            'author_id' => $user->id,
            'legacy_source_table' => 'topics',
            'legacy_source_id' => random_int(1000, 999999),
            'position_in_topic' => 1,
            'body_html' => $body,
        ]);

        $topic->forceFill([
            'first_post_id' => $post->id,
            'last_post_id' => $post->id,
            'last_posted_at' => $post->created_at ?? now(),
        ])->save();

        return $topic->fresh();
    }

    private function createReplyForTopic(Topic $topic, User $user, string $body): Post
    {
        $post = Post::query()->create([
            'topic_id' => $topic->id,
            'author_id' => $user->id,
            'legacy_source_table' => 'reply',
            'legacy_source_id' => random_int(1000, 999999),
            'position_in_topic' => ((int) $topic->posts()->max('position_in_topic')) + 1,
            'body_html' => $body,
        ]);

        $topic->forceFill([
            'reply_count' => max(0, ((int) $post->position_in_topic) - 1),
            'last_post_id' => $post->id,
            'last_posted_at' => $post->created_at ?? now(),
        ])->save();

        return $post;
    }

    private function createExistingAttachment(Post $post, string $root, string $filename): Attachment
    {
        $path = $root.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.$filename;
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9p2nK2wAAAAASUVORK5CYII='));

        return Attachment::query()->create([
            'attachable_type' => 'post',
            'attachable_id' => $post->id,
            'slot_no' => 1,
            'legacy_path' => 'uploads/'.$filename,
            'storage_disk' => 'legacy',
            'original_filename' => $filename,
            'mime_type' => 'image/png',
            'size_bytes' => filesize($path) ?: null,
        ]);
    }

    private function makeImageUpload(string $originalName = 'upload.png'): UploadedFile
    {
        $uploadPath = tempnam(sys_get_temp_dir(), 'peoplecine-post-');
        $this->assertNotFalse($uploadPath);
        file_put_contents($uploadPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9p2nK2wAAAAASUVORK5CYII='));

        return new UploadedFile(
            $uploadPath,
            $originalName,
            'image/png',
            null,
            true
        );
    }

    private function stageUploadFor(User $user, string $originalName): string
    {
        $response = $this->actingAs($user)->post(route('composer.uploads.store'), [
            'image' => $this->makeImageUpload($originalName),
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertCreated();

        return (string) $response->json('token');
    }
}
