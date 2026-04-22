<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BannerManagementTest extends TestCase
{
    use RefreshDatabase;

    private string $bannerConfigPath;
    private string $bannerPublicRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bannerConfigPath = storage_path('app/private/test-banner-manager.json');
        $this->bannerPublicRoot = base_path('tests/tmp/banner-public');

        File::delete($this->bannerConfigPath);
        File::deleteDirectory($this->bannerPublicRoot);
        File::ensureDirectoryExists(dirname($this->bannerConfigPath));
        File::put($this->bannerConfigPath, json_encode([
            'sidebar' => [],
            'landing' => [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        config()->set('peoplecine.banner_config_path', $this->bannerConfigPath);
        config()->set('peoplecine.banner_public_root', $this->bannerPublicRoot);
        config()->set('peoplecine.banner_public_prefix', 'test-banners');
    }

    protected function tearDown(): void
    {
        File::delete($this->bannerConfigPath);
        File::deleteDirectory($this->bannerPublicRoot);

        parent::tearDown();
    }

    public function test_admin_can_manage_banners_and_non_admin_cannot(): void
    {
        $admin = $this->createUser('banner-admin', 'admin');
        $member = $this->createUser('banner-member', 'user');

        $this->actingAs($member)->get(route('admin.banners.index'))->assertForbidden();

        $this->actingAs($admin)->post(route('admin.banners.store'), [
            'section' => 'sidebar',
            'alt' => 'Sidebar Test Banner',
            'image' => UploadedFile::fake()->image('sidebar-banner.jpg', 320, 200),
        ])->assertRedirect(route('admin.banners.index'));

        $this->actingAs($admin)->post(route('admin.banners.store'), [
            'section' => 'landing',
            'alt' => 'Landing Test Banner',
            'image' => UploadedFile::fake()->image('landing-banner.jpg', 720, 300),
        ])->assertRedirect(route('admin.banners.index'));

        $config = json_decode((string) file_get_contents($this->bannerConfigPath), true);

        $this->assertCount(1, $config['sidebar']);
        $this->assertCount(1, $config['landing']);
        $this->assertSame('Sidebar Test Banner', $config['sidebar'][0]['alt']);
        $this->assertSame('Landing Test Banner', $config['landing'][0]['alt']);

        $landingBannerId = $config['landing'][0]['id'];
        $sidebarBannerPath = $config['sidebar'][0]['path'];

        $this->actingAs($admin)->put(route('admin.banners.update', ['section' => 'landing', 'bannerId' => $landingBannerId]), [
            'sort_order' => 55,
            'alt' => 'Landing Banner Updated',
        ])->assertRedirect(route('admin.banners.index'));

        $updatedConfig = json_decode((string) file_get_contents($this->bannerConfigPath), true);
        $this->assertSame(55, $updatedConfig['landing'][0]['sort_order']);
        $this->assertSame('Landing Banner Updated', $updatedConfig['landing'][0]['alt']);

        $sidebarBannerUrl = route('managed-banners.show', [
            'section' => 'sidebar',
            'filename' => basename($sidebarBannerPath),
        ]);

        $landingBannerUrl = route('managed-banners.show', [
            'section' => 'landing',
            'filename' => basename($updatedConfig['landing'][0]['path']),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee($sidebarBannerUrl, false)
            ->assertSee('Sidebar Test Banner');

        $this->get(route('landing'))
            ->assertOk()
            ->assertSee($landingBannerUrl, false)
            ->assertSee('Landing Banner Updated');

        $this->get($sidebarBannerUrl)->assertOk();
        $this->get($landingBannerUrl)->assertOk();

        $this->assertFileExists($this->bannerPublicRoot.DIRECTORY_SEPARATOR.'sidebar'.DIRECTORY_SEPARATOR.basename($sidebarBannerPath));

        $this->actingAs($admin)->delete(route('admin.banners.destroy', ['section' => 'sidebar', 'bannerId' => $updatedConfig['sidebar'][0]['id']]))
            ->assertRedirect(route('admin.banners.index'));

        $finalConfig = json_decode((string) file_get_contents($this->bannerConfigPath), true);
        $this->assertCount(0, $finalConfig['sidebar']);
    }

    private function createUser(string $username, string $role): User
    {
        $user = User::query()->create([
            'username' => $username,
            'email' => $username.'@example.com',
            'password' => Hash::make('secret'),
            'role' => $role,
            'account_status' => 'active',
            'legacy_level' => $role === 'admin' ? 9 : 1,
            'legacy_authorize' => $role === 'admin' ? 'Admin' : null,
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'display_name' => ucfirst(str_replace('-', ' ', $username)),
        ]);

        return $user;
    }
}
