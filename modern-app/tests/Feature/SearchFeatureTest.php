<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Room;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SearchFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_forum_search_returns_public_topic_and_reply_matches_only(): void
    {
        $publicRoom = $this->makeRoom('public-search-room', 'Public Search Room', 0);
        $vipRoom = $this->makeRoom('vip-search-room', 'VIP Search Room', 4);
        $author = $this->makeUser('search-author', 'Search Author', 4);

        $publicTopic = $this->makeTopic($publicRoom, $author, 'Projector Parade', 'The blue elephant reel is ready.');
        $this->makeTopic($vipRoom, $author, 'VIP Projector Parade', 'The vipsignal archive should stay hidden.');

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.21'])
            ->get(route('search.index', ['q' => 'Projector']));

        $response->assertOk();
        $response->assertSee('Projector Parade');
        $response->assertSee('Public Search Room');
        $response->assertDontSee('VIP Projector Parade');
        $response->assertDontSee('VIP Search Room');

        $replyResponse = $this
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.22'])
            ->get(route('search.index', ['q' => 'elephant']));

        $replyResponse->assertOk();
        $replyResponse->assertSee('blue elephant reel');
        $replyResponse->assertSee('#post-'.$publicTopic->posts()->first()->id, false);
        $replyResponse->assertDontSee('vipsignal');
    }

    public function test_member_can_search_visible_vip_topics_and_replies(): void
    {
        $vipRoom = $this->makeRoom('vip-member-room', 'VIP Member Room', 4);
        $vipMember = $this->makeUser('vip-searcher', 'VIP Searcher', 4);

        $topic = $this->makeTopic($vipRoom, $vipMember, 'Golden Speaker Vault', 'Rare speaker diagram inside.');

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.31'])
            ->actingAs($vipMember)
            ->get(route('search.index', ['q' => 'speaker']));

        $response->assertOk();
        $response->assertSee('Golden Speaker Vault');
        $response->assertSee('Rare speaker diagram inside.');
        $response->assertSee('#post-'.$topic->posts()->first()->id, false);
    }

    public function test_search_flood_protection_blocks_immediate_repeat_searches(): void
    {
        $room = $this->makeRoom('flood-room', 'Flood Room', 0);
        $author = $this->makeUser('flood-author', 'Flood Author');

        $this->makeTopic($room, $author, 'Cinema Flood Control', 'Search cooldown body.');

        $ip = '198.51.100.41';

        $firstResponse = $this
            ->withServerVariables(['REMOTE_ADDR' => $ip])
            ->get(route('search.index', ['q' => 'Cinema']));

        $firstResponse->assertOk();
        $firstResponse->assertSee('Cinema Flood Control');

        $secondResponse = $this
            ->withServerVariables(['REMOTE_ADDR' => $ip])
            ->get(route('search.index', ['q' => 'Cinema']));

        $secondResponse->assertStatus(429);
        $secondResponse->assertSee('Search flood protection is active.');
    }

    private function makeRoom(string $slug, string $name, int $accessLevel): Room
    {
        return Room::query()->create([
            'slug' => $slug,
            'name' => $name,
            'access_level' => $accessLevel,
            'sort_order' => 1,
            'is_archived' => false,
        ]);
    }

    private function makeUser(string $username, string $displayName, int $level = 0): User
    {
        $user = User::query()->create([
            'username' => $username,
            'email' => $username.'@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => $level,
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'display_name' => $displayName,
        ]);

        return $user;
    }

    private function makeTopic(Room $room, User $author, string $title, string $body): Topic
    {
        $topic = Topic::query()->create([
            'room_id' => $room->id,
            'author_id' => $author->id,
            'title' => $title,
            'visibility_level' => 0,
            'is_pinned' => false,
            'is_locked' => false,
            'view_count' => 0,
            'reply_count' => 0,
            'last_posted_at' => now(),
        ]);

        $post = Post::query()->create([
            'topic_id' => $topic->id,
            'author_id' => $author->id,
            'legacy_source_table' => 'topics',
            'legacy_source_id' => random_int(1000, 999999),
            'position_in_topic' => 1,
            'body_html' => '<p>'.$body.'</p>',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $topic->forceFill([
            'first_post_id' => $post->id,
            'last_post_id' => $post->id,
            'last_posted_at' => $post->created_at,
        ])->save();

        return $topic->fresh();
    }
}
