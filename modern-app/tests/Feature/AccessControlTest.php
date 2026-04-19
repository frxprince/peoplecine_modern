<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_see_or_open_vip_room(): void
    {
        $publicRoom = Room::query()->create([
            'slug' => 'public-room',
            'name' => 'Public Room',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        $vipRoom = Room::query()->create([
            'slug' => 'vip-room',
            'name' => 'VIP Room',
            'access_level' => 4,
            'sort_order' => 2,
            'is_archived' => false,
        ]);

        $homeResponse = $this->get(route('home'));

        $homeResponse->assertOk();
        $homeResponse->assertSee('Public Room');
        $homeResponse->assertDontSee('VIP Room');

        $this->get(route('rooms.show', $publicRoom))->assertOk();
        $this->get(route('rooms.show', $vipRoom))->assertForbidden();
    }

    public function test_level_zero_member_cannot_open_vip_room_but_vip_member_can(): void
    {
        $vipRoom = Room::query()->create([
            'slug' => 'vip-room',
            'name' => 'VIP Room',
            'access_level' => 4,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        $levelZero = User::query()->create([
            'username' => 'reader',
            'email' => 'reader@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 0,
        ]);
        UserProfile::query()->create([
            'user_id' => $levelZero->id,
            'display_name' => 'Reader',
        ]);

        $vip = User::query()->create([
            'username' => 'vipmember',
            'email' => 'vip@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 4,
        ]);
        UserProfile::query()->create([
            'user_id' => $vip->id,
            'display_name' => 'VIP Member',
        ]);

        $this->actingAs($levelZero)->get(route('rooms.show', $vipRoom))->assertForbidden();
        $this->actingAs($vip)->get(route('rooms.show', $vipRoom))->assertOk();
    }

    public function test_admin_can_manage_users_and_non_admin_cannot(): void
    {
        $admin = User::query()->create([
            'username' => 'admin-user',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
            'role' => 'admin',
            'account_status' => 'active',
            'legacy_level' => 9,
            'legacy_authorize' => 'Admin',
        ]);
        UserProfile::query()->create([
            'user_id' => $admin->id,
            'display_name' => 'Admin User',
        ]);

        $member = User::query()->create([
            'username' => 'member-user',
            'email' => 'member@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 1,
        ]);
        UserProfile::query()->create([
            'user_id' => $member->id,
            'display_name' => 'Member User',
        ]);

        $this->actingAs($member)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($member)->put(route('admin.users.password.update', $admin), [
            'new_password' => 'SecretPass123!',
            'new_password_confirmation' => 'SecretPass123!',
        ])->assertForbidden();
        $this->actingAs($member)->delete(route('admin.users.destroy-many'), [
            'user_ids' => [$admin->id],
        ])->assertForbidden();

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('User Management')
            ->assertSee('member-user');

        $updateResponse = $this->actingAs($admin)->put(route('admin.users.update', $member), [
            'legacy_level' => 9,
            'account_status' => 'banned',
            'role' => 'admin',
        ]);

        $updateResponse->assertRedirect(route('admin.users.index', [
            'page' => 1,
            'sort' => 'role',
            'direction' => 'desc',
        ]));

        $member->refresh();
        $this->assertSame(9, $member->memberLevel());
        $this->assertSame('banned', $member->account_status);
        $this->assertTrue($member->isAdmin());
    }

    public function test_admin_can_set_user_password_from_admin_table(): void
    {
        $admin = User::query()->create([
            'username' => 'admin-password',
            'email' => 'admin-password@example.com',
            'password' => Hash::make('secret'),
            'role' => 'admin',
            'account_status' => 'active',
            'legacy_level' => 9,
            'legacy_authorize' => 'Admin',
        ]);
        UserProfile::query()->create([
            'user_id' => $admin->id,
            'display_name' => 'Admin Password',
        ]);

        $member = User::query()->create([
            'username' => 'member-password',
            'email' => 'member-password@example.com',
            'password' => Hash::make('old-secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 1,
            'password_reset_required' => true,
        ]);
        UserProfile::query()->create([
            'user_id' => $member->id,
            'display_name' => 'Member Password',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.password.update', $member), [
            'new_password' => 'NewSecret123!',
            'new_password_confirmation' => 'NewSecret123!',
        ]);

        $response->assertRedirect(route('admin.users.index', [
            'page' => 1,
            'sort' => 'role',
            'direction' => 'desc',
        ]));

        $member->refresh();
        $this->assertTrue(Hash::check('NewSecret123!', (string) $member->password));
        $this->assertFalse($member->requiresPasswordReset());
    }

    public function test_admin_can_manage_room_access_permissions_create_rooms_and_non_admin_cannot(): void
    {
        $admin = User::query()->create([
            'username' => 'admin-rooms',
            'email' => 'admin-rooms@example.com',
            'password' => Hash::make('secret'),
            'role' => 'admin',
            'account_status' => 'active',
            'legacy_level' => 9,
            'legacy_authorize' => 'Admin',
        ]);
        UserProfile::query()->create([
            'user_id' => $admin->id,
            'display_name' => 'Admin Rooms',
        ]);

        $member = User::query()->create([
            'username' => 'room-member',
            'email' => 'room-member@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 0,
        ]);
        UserProfile::query()->create([
            'user_id' => $member->id,
            'display_name' => 'Room Member',
        ]);

        $room = Room::query()->create([
            'slug' => 'admin-room-config',
            'name' => 'Admin Room Config',
            'access_level' => 0,
            'sort_order' => 5,
            'is_archived' => false,
        ]);

        $this->actingAs($member)->get(route('admin.rooms.index'))->assertForbidden();
        $this->actingAs($member)->post(route('admin.rooms.store'), [
            'name' => 'Forbidden Room',
            'access_level' => 0,
            'sort_order' => 1,
        ])->assertForbidden();
        $this->actingAs($member)->put(route('admin.rooms.update', $room), [
            'access_level' => 4,
            'sort_order' => 9,
            'is_archived' => 1,
        ])->assertForbidden();

        $this->actingAs($admin)->get(route('admin.rooms.index'))
            ->assertOk()
            ->assertSee('Room Management')
            ->assertSee('Admin Room Config');

        $response = $this->actingAs($admin)->put(route('admin.rooms.update', $room), [
            'access_level' => 9,
            'sort_order' => 9,
            'is_archived' => 1,
        ]);

        $response->assertRedirect(route('admin.rooms.index'));

        $room->refresh();
        $this->assertSame(9, (int) $room->access_level);
        $this->assertSame(9, (int) $room->sort_order);
        $this->assertTrue($room->is_archived);

        $createResponse = $this->actingAs($admin)->post(route('admin.rooms.store'), [
            'name' => 'New Admin Room',
            'slug' => 'new-admin-room',
            'name_en' => 'New Admin Room EN',
            'name_color' => '#FF00FF',
            'description' => 'Created from the admin room config page.',
            'access_level' => 9,
            'sort_order' => 15,
            'is_archived' => 0,
        ]);

        $createResponse->assertRedirect(route('admin.rooms.index'));

        $this->assertDatabaseHas('rooms', [
            'slug' => 'new-admin-room',
            'name' => 'New Admin Room',
            'name_en' => 'New Admin Room EN',
            'name_color' => '#FF00FF',
            'access_level' => 9,
            'sort_order' => 15,
            'is_archived' => false,
        ]);

        $this->actingAs($member)->get(route('rooms.show', $room))->assertForbidden();
        $this->actingAs($admin)->get(route('rooms.show', $room))->assertOk();
    }

    public function test_admin_can_bulk_delete_selected_users_but_not_self(): void
    {
        $admin = User::query()->create([
            'username' => 'bulk-admin',
            'email' => 'bulk-admin@example.com',
            'password' => Hash::make('secret'),
            'role' => 'admin',
            'account_status' => 'active',
            'legacy_level' => 9,
            'legacy_authorize' => 'Admin',
        ]);
        UserProfile::query()->create([
            'user_id' => $admin->id,
            'display_name' => 'Bulk Admin',
        ]);

        $memberOne = User::query()->create([
            'username' => 'delete-one',
            'email' => 'delete-one@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 0,
        ]);
        UserProfile::query()->create([
            'user_id' => $memberOne->id,
            'display_name' => 'Delete One',
        ]);

        $memberTwo = User::query()->create([
            'username' => 'delete-two',
            'email' => 'delete-two@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 1,
        ]);
        UserProfile::query()->create([
            'user_id' => $memberTwo->id,
            'display_name' => 'Delete Two',
        ]);

        $room = Room::query()->create([
            'slug' => 'bulk-delete-room',
            'name' => 'Bulk Delete Room',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        $topic = Topic::query()->create([
            'room_id' => $room->id,
            'author_id' => $memberTwo->id,
            'title' => 'Keep My Posts',
            'visibility_level' => 0,
            'is_pinned' => false,
            'is_locked' => false,
            'view_count' => 0,
            'reply_count' => 0,
            'last_posted_at' => now(),
        ]);

        \App\Models\Post::query()->create([
            'topic_id' => $topic->id,
            'author_id' => $memberTwo->id,
            'legacy_source_table' => 'topics',
            'legacy_source_id' => 9991,
            'position_in_topic' => 1,
            'body_html' => 'I have posted content.',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy-many'), [
            'user_ids' => [$memberOne->id, $memberTwo->id, $admin->id],
        ]);

        $response->assertRedirect(route('admin.users.index', [
            'page' => 1,
            'sort' => 'role',
            'direction' => 'desc',
        ]));

        $this->assertDatabaseMissing('users', ['id' => $memberOne->id]);
        $this->assertDatabaseHas('users', [
            'id' => $memberTwo->id,
            'account_status' => 'disabled',
        ]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_user_management_headers_can_sort(): void
    {
        $admin = User::query()->create([
            'username' => 'admin-sorter',
            'email' => 'admin-sorter@example.com',
            'password' => Hash::make('secret'),
            'role' => 'admin',
            'account_status' => 'active',
            'legacy_level' => 9,
            'legacy_authorize' => 'Admin',
        ]);
        UserProfile::query()->create([
            'user_id' => $admin->id,
            'display_name' => 'Admin Sorter',
        ]);

        $alpha = User::query()->create([
            'username' => 'aaa-member',
            'email' => 'aaa@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 0,
        ]);
        UserProfile::query()->create([
            'user_id' => $alpha->id,
            'display_name' => 'AAA Member',
        ]);

        $omega = User::query()->create([
            'username' => 'zzz-member',
            'email' => 'zzz@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 4,
        ]);
        UserProfile::query()->create([
            'user_id' => $omega->id,
            'display_name' => 'ZZZ Member',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'sort' => 'user',
            'direction' => 'desc',
        ]));

        $response->assertOk();
        $response->assertSeeInOrder(['zzz-member', 'aaa-member']);

        $levelResponse = $this->actingAs($admin)->get(route('admin.users.index', [
            'sort' => 'legacy_level',
            'direction' => 'desc',
        ]));

        $levelResponse->assertOk();
        $levelResponse->assertSeeInOrder(['admin-sorter', 'zzz-member', 'aaa-member']);

        $idResponse = $this->actingAs($admin)->get(route('admin.users.index', [
            'sort' => 'id',
            'direction' => 'desc',
        ]));

        $idResponse->assertOk();
        $idResponse->assertSeeInOrder(['zzz-member', 'aaa-member', 'admin-sorter']);
    }

    public function test_admin_user_management_can_search_users(): void
    {
        $admin = User::query()->create([
            'username' => 'admin-search',
            'email' => 'admin-search@example.com',
            'password' => Hash::make('secret'),
            'role' => 'admin',
            'account_status' => 'active',
            'legacy_level' => 9,
            'legacy_authorize' => 'Admin',
        ]);
        UserProfile::query()->create([
            'user_id' => $admin->id,
            'display_name' => 'Admin Search',
        ]);

        $match = User::query()->create([
            'username' => 'cinemafan',
            'email' => 'fan@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 1,
        ]);
        UserProfile::query()->create([
            'user_id' => $match->id,
            'display_name' => 'Classic Cine Fan',
        ]);

        $other = User::query()->create([
            'username' => 'projector',
            'email' => 'projector@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 1,
        ]);
        UserProfile::query()->create([
            'user_id' => $other->id,
            'display_name' => 'Projector User',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'search' => 'Cine Fan',
            'sort' => 'user',
            'direction' => 'asc',
        ]));

        $response->assertOk();
        $response->assertSee('cinemafan');
        $response->assertDontSee('projector');
    }

    public function test_vip_topics_are_hidden_from_home_when_user_lacks_access(): void
    {
        $publicRoom = Room::query()->create([
            'slug' => 'public-room',
            'name' => 'Public Room',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        $vipRoom = Room::query()->create([
            'slug' => 'vip-room',
            'name' => 'VIP Room',
            'access_level' => 4,
            'sort_order' => 2,
            'is_archived' => false,
        ]);

        Topic::query()->create([
            'room_id' => $publicRoom->id,
            'title' => 'Public Topic',
            'visibility_level' => 0,
            'is_pinned' => false,
            'is_locked' => false,
            'view_count' => 0,
            'reply_count' => 0,
            'last_posted_at' => now(),
        ]);

        Topic::query()->create([
            'room_id' => $vipRoom->id,
            'title' => 'VIP Topic',
            'visibility_level' => 0,
            'is_pinned' => false,
            'is_locked' => false,
            'view_count' => 0,
            'reply_count' => 0,
            'last_posted_at' => now(),
        ]);

        $guestHome = $this->get(route('home'));
        $guestHome->assertSee('Public Topic');
        $guestHome->assertDontSee('VIP Topic');

        $vip = User::query()->create([
            'username' => 'vipmember',
            'email' => 'vip@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 4,
        ]);

        $vipHome = $this->actingAs($vip)->get(route('home'));
        $vipHome->assertSee('Public Topic');
        $vipHome->assertSee('VIP Topic');
    }

    public function test_level_three_member_can_open_poster_profile_from_topic_page(): void
    {
        $poster = User::query()->create([
            'username' => 'poster-member',
            'email' => 'poster@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 1,
        ]);
        UserProfile::query()->create([
            'user_id' => $poster->id,
            'display_name' => 'Poster Member',
            'province' => 'Bangkok',
            'postal_code' => '10200',
            'address' => '99 Archive Road',
            'hide_address' => false,
            'biography' => 'Classic movie fan',
        ]);

        $viewer = User::query()->create([
            'username' => 'level-three',
            'email' => 'level-three@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 3,
        ]);
        UserProfile::query()->create([
            'user_id' => $viewer->id,
            'display_name' => 'Level Three',
        ]);

        $room = Room::query()->create([
            'slug' => 'profiles-room',
            'name' => 'Profiles Room',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        $topic = Topic::query()->create([
            'room_id' => $room->id,
            'author_id' => $poster->id,
            'title' => 'Poster Topic',
            'visibility_level' => 0,
            'is_pinned' => false,
            'is_locked' => false,
            'view_count' => 0,
            'reply_count' => 0,
            'last_posted_at' => now(),
        ]);

        \App\Models\Post::query()->create([
            'topic_id' => $topic->id,
            'author_id' => $poster->id,
            'legacy_source_table' => 'topics',
            'legacy_source_id' => 1001,
            'position_in_topic' => 1,
            'body_html' => 'Poster body',
        ]);

        $topicResponse = $this->actingAs($viewer)->get(route('topics.show', $topic));
        $topicResponse->assertOk();
        $topicResponse->assertSee(route('members.show', $poster), false);

        $profileResponse = $this->actingAs($viewer)->get(route('members.show', $poster));
        $profileResponse->assertOk();
        $profileResponse->assertSee('Poster Member');
        $profileResponse->assertSee('Bangkok');
        $profileResponse->assertSee('10200');
        $profileResponse->assertSee('99 Archive Road');
    }

    public function test_level_two_member_cannot_open_poster_profile(): void
    {
        $poster = User::query()->create([
            'username' => 'plain-poster',
            'email' => 'plain-poster@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 1,
        ]);
        UserProfile::query()->create([
            'user_id' => $poster->id,
            'display_name' => 'Plain Poster',
            'hide_address' => false,
        ]);

        $viewer = User::query()->create([
            'username' => 'level-two',
            'email' => 'level-two@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 2,
        ]);
        UserProfile::query()->create([
            'user_id' => $viewer->id,
            'display_name' => 'Level Two',
        ]);

        $room = Room::query()->create([
            'slug' => 'plain-profiles-room',
            'name' => 'Plain Profiles Room',
            'access_level' => 0,
            'sort_order' => 1,
            'is_archived' => false,
        ]);

        $topic = Topic::query()->create([
            'room_id' => $room->id,
            'author_id' => $poster->id,
            'title' => 'Plain Topic',
            'visibility_level' => 0,
            'is_pinned' => false,
            'is_locked' => false,
            'view_count' => 0,
            'reply_count' => 0,
            'last_posted_at' => now(),
        ]);

        \App\Models\Post::query()->create([
            'topic_id' => $topic->id,
            'author_id' => $poster->id,
            'legacy_source_table' => 'topics',
            'legacy_source_id' => 1002,
            'position_in_topic' => 1,
            'body_html' => 'Poster body',
        ]);

        $topicResponse = $this->actingAs($viewer)->get(route('topics.show', $topic));
        $topicResponse->assertOk();
        $topicResponse->assertDontSee(route('members.show', $poster), false);

        $this->actingAs($viewer)->get(route('members.show', $poster))->assertForbidden();
    }

    public function test_hidden_address_is_hidden_from_members_but_visible_to_admin(): void
    {
        $poster = User::query()->create([
            'username' => 'hidden-address-user',
            'email' => 'hidden-address@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 1,
        ]);
        UserProfile::query()->create([
            'user_id' => $poster->id,
            'display_name' => 'Hidden Address User',
            'province' => 'Chiang Mai',
            'postal_code' => '50000',
            'address' => '55 Secret Street',
            'hide_address' => true,
        ]);

        $levelThree = User::query()->create([
            'username' => 'viewer-three',
            'email' => 'viewer-three@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 3,
        ]);
        UserProfile::query()->create([
            'user_id' => $levelThree->id,
            'display_name' => 'Viewer Three',
        ]);

        $admin = User::query()->create([
            'username' => 'hide-admin',
            'email' => 'hide-admin@example.com',
            'password' => Hash::make('secret'),
            'role' => 'admin',
            'account_status' => 'active',
            'legacy_level' => 9,
            'legacy_authorize' => 'Admin',
        ]);
        UserProfile::query()->create([
            'user_id' => $admin->id,
            'display_name' => 'Hide Admin',
        ]);

        $memberResponse = $this->actingAs($levelThree)->get(route('members.show', $poster));
        $memberResponse->assertOk();
        $memberResponse->assertSee('This member has hidden their address.');
        $memberResponse->assertSee('Hidden by member');
        $memberResponse->assertDontSee('55 Secret Street');
        $memberResponse->assertDontSee('50000');

        $adminResponse = $this->actingAs($admin)->get(route('members.show', $poster));
        $adminResponse->assertOk();
        $adminResponse->assertSee('55 Secret Street');
        $adminResponse->assertSee('50000');
    }
}
