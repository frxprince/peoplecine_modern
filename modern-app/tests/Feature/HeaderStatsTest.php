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

    public function test_layout_includes_click_counter_and_build_datetime(): void
    {
        config(['app.locale' => 'en']);
        app()->setLocale('en');

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Click counter');
        $response->assertSee('Build datetime');
    }

    public function test_sidebar_shows_current_member_click_count(): void
    {
        config(['app.locale' => 'en']);
        app()->setLocale('en');

        $member = User::query()->create([
            'username' => 'click-member',
            'email' => 'click-member@example.com',
            'password' => bcrypt('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 1,
            'visit_count' => 456,
        ]);

        $response = $this->actingAs($member)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Clicks');
        $response->assertSee('457');
    }

    public function test_authenticated_member_click_count_increments_on_page_view(): void
    {
        config(['app.locale' => 'en']);
        app()->setLocale('en');

        $member = User::query()->create([
            'username' => 'increment-member',
            'email' => 'increment-member@example.com',
            'password' => bcrypt('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 1,
            'visit_count' => 12,
        ]);

        $this->actingAs($member)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('13');

        $member->refresh();
        $this->assertSame(13, $member->visit_count);
    }
}
