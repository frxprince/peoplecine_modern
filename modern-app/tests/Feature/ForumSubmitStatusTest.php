<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Room;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumSubmitStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_topic_and_reply_forms_include_the_wait_dialog_in_both_languages(): void
    {
        $user = User::query()->create([
            'username' => 'submit-status-member',
            'email' => 'submit-status@example.com',
            'password' => 'test-password',
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 3,
            'password_reset_required' => false,
        ]);
        $room = Room::query()->create([
            'slug' => 'submit-status-test',
            'name' => 'Submit Status Test',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);
        $topic = Topic::query()->create([
            'room_id' => $room->id,
            'author_id' => $user->id,
            'title' => 'Submit Status Test Topic',
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
            'legacy_source_table' => 'modern_topic',
            'legacy_source_id' => 1,
            'position_in_topic' => 1,
            'body_html' => 'Submit status test body.',
        ]);
        $topic->forceFill(['first_post_id' => $post->id, 'last_post_id' => $post->id])->save();

        foreach ([
            'th' => ['กำลังโพสต์หัวข้อ', 'กำลังโพสต์คำตอบ', 'อย่าปิดหน้านี้หรือกดโพสต์ซ้ำ'],
            'en' => ['Posting your topic', 'Posting your reply', 'Please keep this page open and do not post again.'],
        ] as $locale => [$topicTitle, $replyTitle, $description]) {
            foreach ([
                [route('rooms.show', $room), 'topic'],
                [route('topics.show', $topic), 'reply'],
            ] as [$url, $type]) {
                $this->actingAs($user)->withSession(['locale' => $locale])->get($url)
                    ->assertOk()
                    ->assertSee('data-forum-submit="'.$type.'"', false)
                    ->assertSee('data-forum-submit-dialog', false)
                    ->assertSee($topicTitle)
                    ->assertSee($replyTitle)
                    ->assertSee($description)
                    ->assertSee('js/forum-submit-status.js', false)
                    ->assertSee('css/forum-submit-status.css', false);
            }
        }
    }
}
