<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminManualPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_manual_page_in_thai_ui_with_thai_mock_screens(): void
    {
        $admin = User::query()->create([
            'username' => 'admin-manual',
            'email' => 'admin-manual@example.com',
            'password' => Hash::make('password'),
            'password_reset_required' => false,
            'role' => 'admin',
            'legacy_level' => 9,
        ]);

        $response = $this
            ->withSession(['locale' => 'th'])
            ->actingAs($admin)
            ->get(route('admin.manual.index'));

        $response->assertOk();
        $response->assertSee('คู่มือผู้ดูแลระบบ');
        $response->assertSee('manual-screen');
        $response->assertSee('จัดการผู้ใช้');
        $response->assertSee('จัดการห้อง');
        $response->assertSee('จัดการแบนเนอร์');
        $response->assertDontSee('User Admin overview');
    }

    public function test_non_admin_cannot_open_manual_page(): void
    {
        $user = User::query()->create([
            'username' => 'regular-manual',
            'email' => 'regular-manual@example.com',
            'password' => Hash::make('password'),
            'password_reset_required' => false,
            'role' => 'user',
            'legacy_level' => 3,
        ]);

        $response = $this->actingAs($user)->get(route('admin.manual.index'));

        $response->assertForbidden();
    }
}
