<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LandingVisitorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_shows_recent_member_and_guest_visitors(): void
    {
        $member = User::query()->create([
            'username' => 'landing-member',
            'email' => 'landing-member@example.com',
            'password' => bcrypt('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 1,
        ]);

        UserProfile::query()->create([
            'user_id' => $member->id,
            'display_name' => 'Landing Member',
        ]);

        DB::table('recent_visitors')->insert([
            [
                'visitor_key' => 'user:'.$member->id,
                'user_id' => $member->id,
                'ip_address' => '198.51.100.10',
                'last_visited_at' => now()->subMinute(),
            ],
            [
                'visitor_key' => 'guest:203.0.113.25',
                'user_id' => null,
                'ip_address' => '203.0.113.25',
                'last_visited_at' => now(),
            ],
        ]);

        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertSee('Landing Member');
        $response->assertSee('203.0.113.25');
    }

    public function test_landing_page_keeps_only_twenty_latest_visitors_in_order(): void
    {
        $member = User::query()->create([
            'username' => 'persistent-member',
            'email' => 'persistent-member@example.com',
            'password' => bcrypt('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 1,
        ]);

        UserProfile::query()->create([
            'user_id' => $member->id,
            'display_name' => 'Persistent Member',
        ]);

        DB::table('recent_visitors')->insert([
            'visitor_key' => 'user:'.$member->id,
            'user_id' => $member->id,
            'ip_address' => '198.51.100.10',
            'last_visited_at' => now()->subSeconds(5),
        ]);

        foreach (range(1, 21) as $index) {
            DB::table('recent_visitors')->insert([
                'visitor_key' => 'guest:203.0.113.'.$index,
                'user_id' => null,
                'ip_address' => '203.0.113.'.$index,
                'last_visited_at' => now()->subSeconds($index + 10),
            ]);
        }

        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertSeeTextInOrder([
            'Persistent Member',
            '203.0.113.1',
            '203.0.113.2',
        ]);
        $response->assertDontSee('203.0.113.21');
    }
}
