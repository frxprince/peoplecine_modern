<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_page_uses_global_header_stats(): void
    {
        $member = User::query()->create([
            'username' => 'header-user',
            'email' => 'header-user@example.com',
            'password' => bcrypt('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 1,
        ]);

        User::query()->create([
            'username' => 'header-user-2',
            'email' => 'header-user-2@example.com',
            'password' => bcrypt('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 1,
        ]);

        $room = Room::query()->create([
            'slug' => 'header-stats-room',
            'name' => 'Header Stats Room',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        Topic::query()->create([
            'room_id' => $room->id,
            'title' => 'Header Stats Topic',
            'visibility_level' => 0,
            'is_pinned' => false,
            'is_locked' => false,
            'view_count' => 0,
            'reply_count' => 0,
        ]);

        $response = $this->actingAs($member)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('>2</strong>', false);
        $response->assertSee('>1</strong>', false);
    }
}
