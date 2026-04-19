<?php

namespace Tests\Feature;

use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomDescriptionRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_legacy_font_color_tags_in_room_description(): void
    {
        Room::query()->create([
            'slug' => 'font-room',
            'name' => 'Font Room',
            'description' => '<font color="#ff0000">Red notice</font><script>alert(1)</script><br>Visible line',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('<font color="#ff0000">Red notice</font><br>Visible line', false);
        $response->assertDontSee('alert(1)', false);
        $response->assertDontSee('<script>alert(1)</script>', false);
    }
}
