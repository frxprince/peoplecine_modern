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

    public function test_legacy_attachment_route_handles_uppercase_extension_on_linux(): void
    {
        $root = storage_path('app/private/test-legacy-media-uppercase');
        $uploads = $root.DIRECTORY_SEPARATOR.'uploads';
        File::ensureDirectoryExists($uploads);

        File::put($uploads.DIRECTORY_SEPARATOR.'sample.JPG', 'fake-image-content');

        config()->set('peoplecine.legacy_wboard_root', $root);

        $attachment = Attachment::query()->create([
            'attachable_type' => 'post',
            'attachable_id' => 1,
            'slot_no' => 1,
            'legacy_path' => 'uploads/sample.jpg',
            'original_filename' => 'sample.jpg',
        ]);

        $this->get(route('legacy-media.show', $attachment))->assertOk();
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

    public function test_topic_page_rewrites_inline_legacy_picpost_image_with_uppercase_extension(): void
    {
        $root = storage_path('app/private/test-inline-legacy-picpost-uppercase');
        $picpost = $root.DIRECTORY_SEPARATOR.'picpost'.DIRECTORY_SEPARATOR.'2026'.DIRECTORY_SEPARATOR.'04';
        File::ensureDirectoryExists($picpost);
        File::put($picpost.DIRECTORY_SEPARATOR.'INLINE.JPG', 'fake-inline-image');

        config()->set('peoplecine.legacy_wboard_root', $root);

        $room = Room::query()->create([
            'slug' => 'inline-media-room',
            'name' => 'Inline Media Room',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        $topic = Topic::query()->create([
            'room_id' => $room->id,
            'title' => 'Inline Image Topic',
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
            'legacy_source_id' => 123,
            'position_in_topic' => 1,
            'body_html' => '<p><img src="picpost/2026/04/inline.jpg" alt="Inline"></p>',
        ]);

        $response = $this->get(route('topics.show', $topic));

        $response->assertOk();
        $response->assertSee('legacy-inline-media?path=picpost%2F2026%2F04%2Finline.jpg', false);

        $this->get(route('legacy-inline-media.show', ['path' => 'picpost/2026/04/inline.jpg']))
            ->assertOk();
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
