<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Room;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DebugStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_programmer_can_open_statistics_dashboard_and_others_cannot(): void
    {
        $programmer = User::query()->create([
            'username' => 'stats-programmer',
            'email' => 'stats-programmer@example.com',
            'password' => Hash::make('secret'),
            'role' => 'admin',
            'account_status' => 'active',
            'legacy_level' => 10,
            'legacy_authorize' => 'Programmer',
            'visit_count' => 44,
        ]);
        UserProfile::query()->create([
            'user_id' => $programmer->id,
            'display_name' => 'Stats Programmer',
        ]);

        $admin = User::query()->create([
            'username' => 'stats-admin',
            'email' => 'stats-admin@example.com',
            'password' => Hash::make('secret'),
            'role' => 'admin',
            'account_status' => 'active',
            'legacy_level' => 9,
            'legacy_authorize' => 'Admin',
        ]);
        UserProfile::query()->create([
            'user_id' => $admin->id,
            'display_name' => 'Stats Admin',
        ]);

        $member = User::query()->create([
            'username' => 'stats-member',
            'email' => 'stats-member@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 1,
            'visit_count' => 12,
        ]);
        UserProfile::query()->create([
            'user_id' => $member->id,
            'display_name' => 'Stats Member',
        ]);

        $room = Room::query()->create([
            'slug' => 'stats-room',
            'name' => 'Stats Room',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        $topic = Topic::query()->create([
            'room_id' => $room->id,
            'author_id' => $member->id,
            'title' => 'Most Read Topic',
            'visibility_level' => 0,
            'is_pinned' => false,
            'is_locked' => false,
            'view_count' => 123,
            'reply_count' => 7,
            'last_posted_at' => now(),
        ]);

        Post::query()->create([
            'topic_id' => $topic->id,
            'author_id' => $member->id,
            'legacy_source_table' => 'modern_topic',
            'legacy_source_id' => 60001,
            'position_in_topic' => 1,
            'body_html' => '<p>Topic body</p>',
            'ip_address' => '198.51.100.10',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('recent_visitors')->insert([
            [
                'visitor_key' => 'user:'.$member->id,
                'user_id' => $member->id,
                'ip_address' => '198.51.100.10',
                'last_visited_at' => now(),
            ],
            [
                'visitor_key' => 'guest:203.0.113.9',
                'user_id' => null,
                'ip_address' => '203.0.113.9',
                'last_visited_at' => now()->subMinute(),
            ],
        ]);

        $this->actingAs($programmer)->get(route('home'))
            ->assertOk()
            ->assertSee(route('debug.statistics'), false);

        $this->actingAs($programmer)->get(route('debug.statistics'))
            ->assertOk()
            ->assertSee('Most Read Topic')
            ->assertSee('Stats Room')
            ->assertSee('203.0.113.9')
            ->assertSee('198.51.100.10')
            ->assertSee('123');

        $this->actingAs($admin)->get(route('home'))
            ->assertOk()
            ->assertDontSee(route('debug.statistics'), false);

        $this->actingAs($admin)->get(route('debug.statistics'))->assertForbidden();
        $this->actingAs($member)->get(route('debug.statistics'))->assertForbidden();
    }
}
