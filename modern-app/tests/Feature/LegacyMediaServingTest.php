<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Post;
use App\Models\Room;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LegacyMediaServingTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_attachment_route_serves_file_contents(): void
    {
        $root = storage_path('app/private/test-legacy-media');
        $uploads = $root.DIRECTORY_SEPARATOR.'uploads';
        File::ensureDirectoryExists($uploads);

        $content = 'fake-image-content';
        File::put($uploads.DIRECTORY_SEPARATOR.'sample.jpg', $content);

        config()->set('peoplecine.legacy_wboard_root', $root);

        $attachment = Attachment::query()->create([
            'attachable_type' => 'post',
            'attachable_id' => 1,
            'slot_no' => 1,
            'legacy_path' => 'uploads/sample.jpg',
            'original_filename' => 'sample.jpg',
        ]);

        $response = $this->get(route('legacy-media.show', $attachment));

        $response->assertOk();
    }

    public function test_topic_page_renders_attachment_image_tag(): void
    {
        $room = Room::query()->create([
            'slug' => 'attachments-room',
            'name' => 'Attachments Room',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        $topic = Topic::query()->create([
            'room_id' => $room->id,
            'title' => 'Attachment Topic',
            'visibility_level' => 0,
            'is_pinned' => false,
            'is_locked' => false,
            'view_count' => 0,
            'reply_count' => 0,
            'last_posted_at' => now(),
        ]);

        $post = Post::query()->create([
            'topic_id' => $topic->id,
            'legacy_source_table' => 'topics',
            'legacy_source_id' => 10,
            'position_in_topic' => 1,
            'body_html' => 'Example body',
        ]);

        $attachment = Attachment::query()->create([
            'attachable_type' => 'post',
            'attachable_id' => $post->id,
            'slot_no' => 1,
            'legacy_path' => 'uploads/sample.jpg',
            'original_filename' => 'sample.jpg',
        ]);

        $response = $this->get(route('topics.show', $topic));

        $response->assertOk();
        $response->assertSee('src="'.$attachment->legacyUrl().'"', false);
        $response->assertSee('camera-indicator', false);
        $response->assertDontSee('uploads/sample.jpg', false);
        $response->assertDontSee('>sample.jpg<', false);
    }

    public function test_home_page_marks_topics_with_posted_images(): void
    {
        $room = Room::query()->create([
            'slug' => 'main-room',
            'name' => 'Main Room',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        $topic = Topic::query()->create([
            'room_id' => $room->id,
            'title' => 'Photo Topic',
            'visibility_level' => 0,
            'is_pinned' => false,
            'is_locked' => false,
            'view_count' => 0,
            'reply_count' => 0,
            'last_posted_at' => now(),
        ]);

        $post = Post::query()->create([
            'topic_id' => $topic->id,
            'legacy_source_table' => 'topics',
            'legacy_source_id' => 20,
            'position_in_topic' => 1,
            'body_html' => 'Photo post body',
        ]);

        Attachment::query()->create([
            'attachable_type' => 'post',
            'attachable_id' => $post->id,
            'slot_no' => 1,
            'legacy_path' => 'uploads/home-sample.jpg',
            'original_filename' => 'home-sample.jpg',
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('camera-indicator', false);
        $response->assertSee('Photo Topic');
    }
}
