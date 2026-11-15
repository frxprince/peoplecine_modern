<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Room;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumSubmissionResponseTest extends TestCase
{
    use RefreshDatabase;

    private User $member;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->member = User::query()->create([
            'username' => 'submission-test-member',
            'email' => 'submission-test@example.com',
            'password' => 'test-password',
            'role' => 'admin',
            'legacy_level' => 10,
            'account_status' => 'active',
            'password_reset_required' => false,
        ]);
        $this->room = Room::query()->create([
            'slug' => 'submission-test-room',
            'name' => 'Submission Test Room',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);
    }

    public function test_json_topic_creation_confirms_one_saved_topic_and_its_relative_destination(): void
    {
        $response = $this->actingAs($this->member)->postJson(route('rooms.topics.store', $this->room), [
            'title' => 'New topic',
            'body_html' => '<p>Opening message</p>',
        ]);
        $topic = Topic::query()->sole();

        $response->assertCreated()
            ->assertExactJson(['redirect' => route('topics.show', $topic, false)])
            ->assertSessionHas('status', 'Your new topic has been posted.');
        $this->assertDatabaseCount('posts', 1);
    }

    public function test_json_reply_confirms_saved_reply_without_an_http_redirect(): void
    {
        $topic = $this->createTopic();

        $this->actingAs($this->member)->postJson(route('topics.replies.store', $topic), [
            'body_html' => '<p>Reply message</p>',
        ])->assertCreated()
            ->assertExactJson(['redirect' => route('topics.show', $topic, false)])
            ->assertSessionHas('status', 'Your reply has been posted.');

        $this->assertSame(1, (int) $topic->fresh()->reply_count);
        $this->assertDatabaseCount('posts', 2);
    }

    public function test_confirmed_topic_deletion_sends_the_browser_to_the_room_lobby(): void
    {
        $topic = $this->createTopic();
        $postId = $topic->first_post_id;

        $this->actingAs($this->member)->postJson(route('topics.destroy', $topic), [
            '_method' => 'DELETE',
        ])->assertOk()
            ->assertExactJson(['redirect' => route('rooms.show', $this->room, false)])
            ->assertSessionHas('status', 'The topic has been deleted.');

        $this->assertSoftDeleted('topics', ['id' => $topic->id]);
        $this->assertSoftDeleted('posts', ['id' => $postId]);
    }

    public function test_native_topic_deletion_keeps_a_see_other_redirect_for_non_javascript_clients(): void
    {
        $topic = $this->createTopic();

        $this->actingAs($this->member)->delete(route('topics.destroy', $topic))
            ->assertStatus(303)
            ->assertRedirect(route('rooms.show', $this->room));
    }

    public function test_validation_errors_are_json_and_do_not_save_a_topic(): void
    {
        $this->actingAs($this->member)->postJson(route('rooms.topics.store', $this->room), [
            'title' => '',
            'body_html' => '',
        ])->assertUnprocessable()->assertJsonValidationErrors(['title', 'body_html']);

        $this->assertDatabaseCount('topics', 0);
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_non_admin_cannot_delete_a_topic_using_the_json_flow(): void
    {
        $topic = $this->createTopic();
        $this->member->forceFill(['role' => 'user', 'legacy_level' => 3])->save();

        $this->actingAs($this->member)->deleteJson(route('topics.destroy', $topic))
            ->assertForbidden();

        $this->assertNotSoftDeleted('topics', ['id' => $topic->id]);
    }

    public function test_https_proxy_requests_receive_a_path_instead_of_a_host_or_scheme_dependent_redirect(): void
    {
        $topic = $this->createTopic();

        $this->actingAs($this->member)
            ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'www.peoplecine.com'])
            ->postJson(route('topics.replies.store', $topic), ['body_html' => 'HTTPS reply'])
            ->assertCreated()
            ->assertExactJson(['redirect' => '/topics/'.$topic->id]);
    }

    private function createTopic(): Topic
    {
        $topic = Topic::query()->create([
            'room_id' => $this->room->id,
            'author_id' => $this->member->id,
            'title' => 'Existing topic',
            'visibility_level' => 0,
            'is_pinned' => false,
            'is_locked' => false,
            'view_count' => 0,
            'reply_count' => 0,
            'last_posted_at' => now(),
        ]);
        $post = Post::query()->create([
            'topic_id' => $topic->id,
            'author_id' => $this->member->id,
            'legacy_source_table' => 'modern_topic',
            'legacy_source_id' => 1,
            'position_in_topic' => 1,
            'body_html' => '<p>Existing message</p>',
        ]);
        $topic->forceFill(['first_post_id' => $post->id, 'last_post_id' => $post->id])->save();

        return $topic;
    }
}
