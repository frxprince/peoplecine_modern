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

        DB::table('sessions')->insert([
            [
                'id' => 'landing-member-session',
                'user_id' => $member->id,
                'ip_address' => '198.51.100.10',
                'user_agent' => 'PHPUnit member',
                'payload' => 'member-payload',
                'last_activity' => now()->subMinute()->timestamp,
            ],
            [
                'id' => 'landing-guest-session',
                'user_id' => null,
                'ip_address' => '203.0.113.25',
                'user_agent' => 'PHPUnit guest',
                'payload' => 'guest-payload',
                'last_activity' => now()->timestamp,
            ],
        ]);

        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertSee('Landing Member');
        $response->assertSee('203.0.113.25');
    }
}
