<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_with_legacy_username_and_password(): void
    {
        $user = User::query()->create([
            'legacy_memberx_id' => 42,
            'username' => 'legacy-user',
            'email' => 'legacy@example.com',
            'password' => Hash::make('legacy-password'),
            'password_reset_required' => false,
            'role' => 'user',
            'account_status' => 'active',
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Legacy Member',
        ]);

        $response = $this->post(route('login.store'), [
            'login' => 'legacy-user',
            'password' => 'legacy-password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_user_can_log_in_with_email_when_email_exists(): void
    {
        $user = User::query()->create([
            'legacy_memberx_id' => 7,
            'username' => 'email-member',
            'email' => 'member@example.com',
            'password' => Hash::make('test-password'),
            'password_reset_required' => false,
            'role' => 'user',
            'account_status' => 'active',
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Needs Reset',
        ]);

        $response = $this->post(route('login.store'), [
            'login' => 'member@example.com',
            'password' => 'test-password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_without_email_cannot_log_in_by_email_until_profile_is_updated(): void
    {
        $user = User::query()->create([
            'legacy_memberx_id' => 99,
            'username' => 'username-only',
            'email' => null,
            'password' => Hash::make('test-password'),
            'password_reset_required' => false,
            'role' => 'user',
            'account_status' => 'active',
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Member 99',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'login' => 'missing@example.com',
            'password' => 'test-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('login');
    }

    public function test_member_can_add_email_in_profile_and_then_use_it_to_log_in(): void
    {
        $user = User::query()->create([
            'legacy_memberx_id' => 101,
            'username' => 'active-member',
            'email' => null,
            'password' => Hash::make('secure-password'),
            'password_reset_required' => false,
            'role' => 'user',
            'account_status' => 'active',
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Active Member',
        ]);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'display_name' => 'Active Member',
            'email' => 'active@example.com',
            'phone' => '',
            'province' => '',
            'biography' => '',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $this->assertSame('active@example.com', $user->fresh()->email);

        auth()->logout();

        $loginResponse = $this->post(route('login.store'), [
            'login' => 'active@example.com',
            'password' => 'secure-password',
        ]);

        $loginResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_claimed_user_can_view_dashboard(): void
    {
        $user = User::query()->create([
            'legacy_memberx_id' => 101,
            'username' => 'active-member',
            'email' => 'active@example.com',
            'password' => Hash::make('secure-password'),
            'password_reset_required' => false,
            'role' => 'user',
            'account_status' => 'active',
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Active Member',
        ]);

        $room = Room::query()->create([
            'slug' => 'general-room',
            'name' => 'General Room',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        Topic::query()->create([
            'room_id' => $room->id,
            'author_id' => $user->id,
            'title' => 'A migrated topic',
            'visibility_level' => 0,
            'is_pinned' => false,
            'is_locked' => false,
            'view_count' => 0,
            'reply_count' => 0,
            'last_posted_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('ยินดีต้อนรับกลับ, Active Member.');
        $response->assertSee('A migrated topic');
    }

    public function test_member_can_save_and_remove_topic_from_saved_topics(): void
    {
        $user = User::query()->create([
            'legacy_memberx_id' => 202,
            'username' => 'bookmark-member',
            'email' => 'bookmark@example.com',
            'password' => Hash::make('secure-password'),
            'password_reset_required' => false,
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 1,
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Bookmark Member',
        ]);

        $room = Room::query()->create([
            'slug' => 'bookmark-room',
            'name' => 'Bookmark Room',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        $topic = Topic::query()->create([
            'room_id' => $room->id,
            'author_id' => $user->id,
            'title' => 'Bookmarked Topic',
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
            'legacy_source_id' => 2001,
            'position_in_topic' => 1,
            'body_html' => 'Bookmark body',
        ]);

        $saveResponse = $this->from(route('topics.show', $topic))
            ->actingAs($user)
            ->post(route('topics.bookmarks.store', $topic));
        $saveResponse->assertRedirect(route('topics.show', $topic));
        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
        ]);

        $dashboardResponse = $this->actingAs($user)->get(route('dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Bookmarked Topic');

        $removeResponse = $this->from(route('dashboard'))
            ->actingAs($user)
            ->delete(route('topics.bookmarks.destroy', $topic));
        $removeResponse->assertRedirect(route('dashboard'));
        $this->assertDatabaseMissing('bookmarks', [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
        ]);
    }

    public function test_banned_user_cannot_log_in(): void
    {
        $user = User::query()->create([
            'legacy_memberx_id' => 404,
            'username' => 'banned-member',
            'email' => 'banned@example.com',
            'password' => Hash::make('blocked-password'),
            'password_reset_required' => false,
            'role' => 'user',
            'account_status' => 'banned',
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Banned Member',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'login' => 'banned-member',
            'password' => 'blocked-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'login' => 'This account has been banned. Please contact an administrator.',
        ]);
        $this->assertGuest();
    }

    public function test_disabled_user_cannot_log_in(): void
    {
        $user = User::query()->create([
            'legacy_memberx_id' => 405,
            'username' => 'disabled-member',
            'email' => 'disabled@example.com',
            'password' => Hash::make('blocked-password'),
            'password_reset_required' => false,
            'role' => 'user',
            'account_status' => 'disabled',
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Disabled Member',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'login' => 'disabled-member',
            'password' => 'blocked-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'login' => 'This account has been disabled. Please contact an administrator.',
        ]);
        $this->assertGuest();
    }
}
