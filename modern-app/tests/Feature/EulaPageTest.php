<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EulaPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_eula_page_is_public_and_renders_thai_title(): void
    {
        $response = $this
            ->withSession(['locale' => 'th'])
            ->get(route('eula'));

        $response->assertOk();
        $response->assertSee('ข้อตกลงการใช้งานซอฟต์แวร์');
        $response->assertSee('ข้อตกลงการใช้งานซอฟต์แวร์และเว็บไซต์ (EULA)');
        $response->assertSee('กฎหมายแห่งราชอาณาจักรไทย');
    }

    public function test_top_menu_contains_eula_link(): void
    {
        $response = $this
            ->withSession(['locale' => 'th'])
            ->get(route('landing'));

        $response->assertOk();
        $response->assertSee(route('eula'), false);
        $response->assertSee('ข้อตกลงการใช้งาน');
        $response->assertSeeInOrder([
            'เข้าสู่ระบบสมาชิก',
            'สมัครสมาชิก',
            'ข้อตกลงการใช้งาน',
            'legacy-topnav__locale-text">TH',
        ], false);
    }
}
