<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Room;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForumThaiUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_forum_home_room_and_topic_pages_render_core_labels_in_thai(): void
    {
        $user = User::query()->create([
            'username' => 'thai-ui-user',
            'email' => 'thai-ui@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 3,
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'display_name' => 'สมาชิกตัวอย่าง',
        ]);

        $room = Room::query()->create([
            'slug' => 'thai-room',
            'name' => 'ห้องทดสอบ',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        $topic = Topic::query()->create([
            'room_id' => $room->id,
            'author_id' => $user->id,
            'title' => 'หัวข้อทดสอบ',
            'visibility_level' => 0,
            'is_pinned' => true,
            'is_locked' => false,
            'view_count' => 12,
            'reply_count' => 0,
            'last_posted_at' => now(),
        ]);

        Post::query()->create([
            'topic_id' => $topic->id,
            'author_id' => $user->id,
            'legacy_source_table' => 'topics',
            'legacy_source_id' => 1,
            'position_in_topic' => 1,
            'body_html' => 'ข้อความทดสอบ',
        ]);

        $homeResponse = $this->withSession(['locale' => 'th'])->get(route('home'));
        $homeResponse->assertOk();
        $homeResponse->assertSee('ชุมชนหนังกลางแปลง');
        $homeResponse->assertSee('ห้อง');
        $homeResponse->assertSee('หัวข้อ');
        $homeResponse->assertSee('อ่าน');
        $homeResponse->assertSee('ตอบ');
        $homeResponse->assertSee('ล่าสุด');

        $roomResponse = $this->actingAs($user)->withSession(['locale' => 'th'])->get(route('rooms.show', $room));
        $roomResponse->assertOk();
        $roomResponse->assertSee('ห้องเว็บบอร์ด');
        $roomResponse->assertSee('ตั้งหัวข้อใหม่');
        $roomResponse->assertSee('โพสต์หัวข้อ');
        $roomResponse->assertSee('ปักหมุด');

        $topicResponse = $this->actingAs($user)->withSession(['locale' => 'th'])->get(route('topics.show', $topic));
        $topicResponse->assertOk();
        $topicResponse->assertSee('ครั้งอ่าน');
        $topicResponse->assertSee('ครั้งตอบ');
        $topicResponse->assertSee('ตอบกระทู้');
        $topicResponse->assertSee('ข้อความตอบ');
        $topicResponse->assertSee('โพสต์คำตอบ');
    }
}
