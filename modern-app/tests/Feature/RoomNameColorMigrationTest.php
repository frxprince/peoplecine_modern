<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomNameColorMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_room_name_uses_clean_text_and_only_high_level_rooms_show_badge(): void
    {
        $viewer = User::query()->create([
            'username' => 'vip-viewer-th',
            'email' => 'vip-viewer-th@example.com',
            'password' => bcrypt('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 4,
        ]);

        Room::query()->create([
            'slug' => 'public-room',
            'name' => 'ห้องทั่วไป',
            'name_color' => '#ff00ff',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        Room::query()->create([
            'slug' => 'vip-room',
            'name' => 'ห้องวีไอพี',
            'name_color' => '#00aa44',
            'access_level' => 4,
            'sort_order' => 2,
            'is_archived' => false,
        ]);

        $response = $this->actingAs($viewer)->get(route('home'));

        $response->assertOk();
        $response->assertSee('ห้องทั่วไป');
        $response->assertSee('ห้องวีไอพี');
        $response->assertSee('LV-4', false);
        $response->assertSee('title="ระดับ 4 ห้อง VIP"', false);
        $response->assertSee('style="color: #ff00ff"', false);
        $response->assertSee('style="color: #00aa44"', false);
        $response->assertDontSee('LV-0', false);
        $response->assertDontSee('&lt;font', false);
        $response->assertDontSee('<font color=', false);
    }

    public function test_english_ui_uses_english_room_name_when_available_and_badges_only_for_level_above_three(): void
    {
        $viewer = User::query()->create([
            'username' => 'vip-viewer-en',
            'email' => 'vip-viewer-en@example.com',
            'password' => bcrypt('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 4,
        ]);

        Room::query()->create([
            'slug' => 'public-room-en',
            'name' => 'ห้องทั่วไป',
            'name_en' => 'Public Room',
            'name_color' => '#ff00ff',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        Room::query()->create([
            'slug' => 'vip-room-en',
            'name' => 'ห้องวีไอพี',
            'name_en' => 'VIP Room',
            'name_color' => '#00aa44',
            'access_level' => 4,
            'sort_order' => 2,
            'is_archived' => false,
        ]);

        $response = $this
            ->withCookie('peoplecine_locale', 'en')
            ->actingAs($viewer)
            ->get(route('home'));

        $response->assertOk();
        $response->assertSee('Public Room');
        $response->assertSee('VIP Room');
        $response->assertDontSee('ห้องทั่วไป');
        $response->assertDontSee('ห้องวีไอพี');
        $response->assertSee('LV-4', false);
        $response->assertSee('title="Level 4 VIP access"', false);
        $response->assertDontSee('LV-0', false);
        $response->assertSee('style="color: #ff00ff"', false);
        $response->assertSee('style="color: #00aa44"', false);
    }
}
