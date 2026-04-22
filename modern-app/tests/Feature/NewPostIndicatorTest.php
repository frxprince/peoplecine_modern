<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Room;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NewPostIndicatorTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_forum_home_shows_new_indicator_for_recent_room_activity_and_topics(): void
    {
        Carbon::setTestNow('2026-04-22 12:00:00');

        $room = $this->createRoomWithTopic(
            'fresh-room',
            'Fresh Room',
            'Fresh Topic',
            now()->subDay()
        );

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Fresh Room');
        $response->assertSee('Fresh Topic');
        $response->assertSee('forum-new-indicator', false);
    }

    public function test_room_page_hides_new_indicator_for_topics_older_than_three_days(): void
    {
        Carbon::setTestNow('2026-04-22 12:00:00');

        $room = $this->createRoomWithTopic(
            'archive-room',
            'Archive Room',
            'Old Topic',
            now()->subDays(4)
        );

        $response = $this->get(route('rooms.show', $room));

        $response->assertOk();
        $response->assertDontSee('forum-new-indicator', false);
    }

    private function createRoomWithTopic(string $slug, string $roomName, string $topicTitle, Carbon $activityAt): Room
    {
        $room = Room::query()->create([
            'slug' => $slug,
            'name' => $roomName,
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        $topic = Topic::query()->create([
            'room_id' => $room->id,
            'title' => $topicTitle,
            'visibility_level' => 0,
            'is_pinned' => false,
            'is_locked' => false,
            'view_count' => 0,
            'reply_count' => 0,
            'created_at' => $activityAt,
            'updated_at' => $activityAt,
            'last_posted_at' => $activityAt,
        ]);

        $post = Post::query()->create([
            'topic_id' => $topic->id,
            'legacy_source_table' => 'topics',
            'legacy_source_id' => random_int(1000, 999999),
            'position_in_topic' => 1,
            'body_html' => 'Body',
            'created_at' => $activityAt,
            'updated_at' => $activityAt,
        ]);

        $topic->forceFill([
            'first_post_id' => $post->id,
            'last_post_id' => $post->id,
            'last_posted_at' => $activityAt,
            'created_at' => $activityAt,
            'updated_at' => $activityAt,
        ])->save();

        return $room->fresh();
    }
}
