<?php

namespace Tests\Feature;

use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomNameColorMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_room_name_uses_clean_text_and_separate_color_field(): void
    {
        $room = Room::query()->create([
            'slug' => 'vip-room',
            'name' => 'ห้องวีไอพี (สำหรับสมาชิกคลาส 4)',
            'name_color' => '#ff00ff',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('ห้องวีไอพี (สำหรับสมาชิกคลาส 4)');
        $response->assertSee('style="color: #ff00ff"', false);
        $response->assertDontSee('&lt;font', false);
        $response->assertDontSee('<font color=', false);
    }
    public function test_english_ui_uses_english_room_name_when_available(): void
    {
        Room::query()->create([
            'slug' => 'vip-room-en',
            'name' => 'ห้องวีไอพี',
            'name_en' => 'VIP Room',
            'name_color' => '#ff00ff',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        $response = $this
            ->withCookie('peoplecine_locale', 'en')
            ->get(route('home'));

        $response->assertOk();
        $response->assertSee('VIP Room');
        $response->assertDontSee('ห้องวีไอพี');
        $response->assertSee('style="color: #ff00ff"', false);
    }
}
