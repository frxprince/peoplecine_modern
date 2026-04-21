<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserProfile;
use App\Support\PostedImageWatermarker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PostedImageWatermarkerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_applies_a_text_watermark_to_png_images(): void
    {
        $path = storage_path('app/private/test-watermark.png');
        $this->createPlainPng($path);

        $before = hash_file('sha256', $path);

        $user = User::query()->create([
            'username' => 'watermark-user',
            'email' => 'watermark@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 3,
            'password_reset_required' => false,
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Watermark Tester',
        ]);

        $watermarker = app(PostedImageWatermarker::class);
        $result = $watermarker->watermark($path, $user, now());

        $this->assertTrue($result);
        $this->assertFileExists($path);
        $this->assertNotSame($before, hash_file('sha256', $path));

        @unlink($path);
    }

    public function test_it_can_render_thai_display_names_with_font_fallbacks(): void
    {
        $thaiFontCandidates = [
            'C:/Windows/Fonts/THSarabunNew.ttf',
            'C:/Windows/Fonts/THSarabun.ttf',
            'C:/Windows/Fonts/LeelawUI.ttf',
            'C:/Windows/Fonts/tahoma.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansThai-Regular.ttf',
            '/usr/share/fonts/truetype/tlwg/Garuda.ttf',
        ];

        $availableThaiFont = collect($thaiFontCandidates)
            ->first(static fn (string $path): bool => is_file($path));

        if (! is_string($availableThaiFont) || $availableThaiFont === '') {
            $this->markTestSkipped('No Thai-capable font is available in the current environment.');
        }

        Config::set('peoplecine.watermark_font_path', 'C:/font-that-does-not-exist.ttf');
        Config::set('peoplecine.watermark_font_paths', [$availableThaiFont]);

        $path = storage_path('app/private/test-watermark-thai.png');
        $this->createPlainPng($path);

        $before = hash_file('sha256', $path);

        $user = User::query()->create([
            'username' => 'thai-watermark-user',
            'email' => 'thai-watermark@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 3,
            'password_reset_required' => false,
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'display_name' => 'ผู้ใช้ไทย',
        ]);

        $watermarker = app(PostedImageWatermarker::class);
        $result = $watermarker->watermark($path, $user, now());

        $this->assertTrue($result);
        $this->assertFileExists($path);
        $this->assertNotSame($before, hash_file('sha256', $path));

        @unlink($path);
    }

    private function createPlainPng(string $path): void
    {
        $image = imagecreatetruecolor(1280, 720);
        $background = imagecolorallocate($image, 35, 74, 132);
        imagefill($image, 0, 0, $background);
        imagepng($image, $path);
        imagedestroy($image);
    }
}
