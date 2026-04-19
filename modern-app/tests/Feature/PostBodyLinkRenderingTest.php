<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Room;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostBodyLinkRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_topic_page_linkifies_plain_urls_and_opens_links_in_new_tab(): void
    {
        $room = Room::query()->create([
            'slug' => 'links-room',
            'name' => 'Links Room',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        $topic = Topic::query()->create([
            'room_id' => $room->id,
            'title' => 'Links Topic',
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
            'legacy_source_id' => 99,
            'position_in_topic' => 1,
            'body_html' => 'Visit https://example.com and <a href="https://peoplecine.test/page">existing link</a> today.',
        ]);

        $response = $this->get(route('topics.show', $topic));

        $response->assertOk();
        $response->assertSee('<a href="https://example.com" target="_blank" rel="noopener noreferrer">https://example.com</a>', false);
        $response->assertSee('<a href="https://peoplecine.test/page" target="_blank" rel="noopener noreferrer">existing link</a>', false);
    }

    public function test_topic_page_embeds_youtube_links_in_message_body(): void
    {
        $room = Room::query()->create([
            'slug' => 'youtube-room',
            'name' => 'Youtube Room',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        $topic = Topic::query()->create([
            'room_id' => $room->id,
            'title' => 'Youtube Topic',
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
            'legacy_source_id' => 100,
            'position_in_topic' => 1,
            'body_html' => 'Watch https://youtu.be/dQw4w9WgXcQ and <a href="https://www.youtube.com/watch?v=oHg5SJYRHA0">this too</a>.',
        ]);

        $response = $this->get(route('topics.show', $topic));

        $response->assertOk();
        $response->assertSee('youtube-embed', false);
        $response->assertSee('src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ"', false);
        $response->assertSee('src="https://www.youtube-nocookie.com/embed/oHg5SJYRHA0"', false);
        $response->assertDontSee('href="https://youtu.be/dQw4w9WgXcQ"', false);
        $response->assertDontSee('href="https://www.youtube.com/watch?v=oHg5SJYRHA0"', false);
    }

    public function test_topic_page_strips_javascript_related_html_before_rendering(): void
    {
        $room = Room::query()->create([
            'slug' => 'safe-html-room',
            'name' => 'Safe Html Room',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        $topic = Topic::query()->create([
            'room_id' => $room->id,
            'title' => 'Safe Html Topic',
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
            'legacy_source_id' => 101,
            'position_in_topic' => 1,
            'body_html' => '<strong>Safe text</strong><script>alert(1)</script><img src="/x.jpg" onerror="alert(1)"><a href="javascript:alert(1)" onclick="alert(1)">bad link</a>',
        ]);

        $response = $this->get(route('topics.show', $topic));

        $response->assertOk();
        $response->assertSee('<strong>Safe text</strong>', false);
        $response->assertDontSee('alert(1)', false);
        $response->assertDontSee('onerror=', false);
        $response->assertDontSee('onclick=', false);
        $response->assertDontSee('javascript:alert(1)', false);
    }
}
