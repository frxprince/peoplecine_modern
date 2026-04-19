<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Room;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AvatarRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_topic_page_renders_legacy_avatar_after_poster_name(): void
    {
        $root = storage_path('app/private/test-legacy-avatars');
        $icons = $root.DIRECTORY_SEPARATOR.'icons';
        File::ensureDirectoryExists($icons);
        File::put($icons.DIRECTORY_SEPARATOR.'sample.jpg', 'avatar-bytes');

        config()->set('peoplecine.legacy_wboard_root', $root);

        $user = User::query()->create([
            'legacy_memberx_id' => 501,
            'username' => 'avatar-member',
            'email' => 'avatar@example.com',
            'password' => Hash::make('secret'),
            'password_reset_required' => false,
            'role' => 'user',
            'account_status' => 'active',
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Avatar Member',
            'avatar_path' => 'icons/sample.jpg',
        ]);

        $room = Room::query()->create([
            'slug' => 'avatars-room',
            'name' => 'Avatars Room',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        $topic = Topic::query()->create([
            'room_id' => $room->id,
            'author_id' => $user->id,
            'title' => 'Avatar Topic',
            'visibility_level' => 0,
            'is_pinned' => false,
            'is_locked' => false,
            'view_count' => 0,
            'reply_count' => 0,
            'last_posted_at' => now(),
        ]);

        Post::query()->create([
            'topic_id' => $topic->id,
            'author_id' => $user->id,
            'legacy_source_table' => 'topics',
            'legacy_source_id' => 1,
            'position_in_topic' => 1,
            'body_html' => 'Avatar post body',
        ]);

        $response = $this->get(route('topics.show', $topic));

        $response->assertOk();
        $response->assertSee('Avatar Member');
        $response->assertSee('src="'.route('avatars.show', $user).'"', false);

        $avatarResponse = $this->get(route('avatars.show', $user));
        $avatarResponse->assertOk();
    }

    public function test_member_can_upload_new_avatar_from_profile_page(): void
    {
        $root = storage_path('app/private/test-profile-avatar-upload');
        File::ensureDirectoryExists($root.DIRECTORY_SEPARATOR.'icons');

        config()->set('peoplecine.legacy_wboard_root', $root);

        $user = User::query()->create([
            'legacy_memberx_id' => 777,
            'username' => 'upload-avatar',
            'email' => 'upload@example.com',
            'password' => Hash::make('secret'),
            'password_reset_required' => false,
            'role' => 'user',
            'account_status' => 'active',
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Upload Avatar',
        ]);

        $uploadPath = tempnam(sys_get_temp_dir(), 'peoplecine-avatar-');
        $this->assertNotFalse($uploadPath);
        file_put_contents($uploadPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9p2nK2wAAAAASUVORK5CYII='));
        $avatarUpload = new UploadedFile(
            $uploadPath,
            'avatar.png',
            'image/png',
            null,
            true
        );

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'display_name' => 'Upload Avatar',
            'email' => 'upload@example.com',
            'phone' => '',
            'province' => '',
            'postal_code' => '10110',
            'address' => '123 Test Lane',
            'hide_address' => '1',
            'biography' => '',
            'avatar' => $avatarUpload,
        ]);

        $response->assertRedirect(route('profile.edit'));

        $profile = $user->fresh()->profile;

        $this->assertNotNull($profile);
        $this->assertNotNull($profile->avatar_path);
        $this->assertSame('10110', $profile->postal_code);
        $this->assertSame('123 Test Lane', $profile->address);
        $this->assertTrue((bool) $profile->hide_address);
        $this->assertStringStartsWith('icons/member-avatar-'.$user->id.'-', $profile->avatar_path);
        $this->assertFileExists($root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $profile->avatar_path));

        $avatarResponse = $this->get(route('avatars.show', $user));
        $avatarResponse->assertOk();
    }
}
