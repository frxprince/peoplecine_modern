<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PrivateMessageFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_private_messages(): void
    {
        $this->get(route('messages.index'))->assertRedirect(route('login'));
    }

    public function test_member_can_send_private_message_and_recipient_sees_unread_indicator(): void
    {
        [$sender, $recipient] = $this->makeMessagingPair();

        $sendResponse = $this->actingAs($sender)->post(route('messages.store'), [
            'recipient' => 'recipient-user',
            'subject' => 'Archive trade',
            'message' => '<p>Hello from the rebuilt inbox.</p>',
        ]);

        $conversation = Conversation::query()->first();

        $this->assertNotNull($conversation);
        $sendResponse->assertRedirect(route('messages.show', $conversation));

        $this->assertDatabaseCount('conversations', 1);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
        ]);

        $recipientHome = $this->actingAs($recipient)->get(route('home'));
        $recipientHome->assertOk();
        $recipientHome->assertSee('message-nav-badge', false);

        $recipientInbox = $this->actingAs($recipient)->get(route('messages.index'));
        $recipientInbox->assertOk();
        $recipientInbox->assertSee('Archive trade');
        $recipientInbox->assertSee('New');
    }

    public function test_opening_conversation_marks_private_message_as_read(): void
    {
        [$sender, $recipient] = $this->makeMessagingPair();

        $conversation = Conversation::query()->create([
            'subject' => 'Unread test',
        ]);

        $conversation->participants()->attach([
            $sender->id => [
                'joined_at' => now(),
                'last_read_at' => now(),
                'last_read_message_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            $recipient->id => [
                'joined_at' => now(),
                'last_read_at' => null,
                'last_read_message_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body_html' => '<p>Unread message</p>',
        ]);

        $homeBefore = $this->actingAs($recipient)->get(route('home'));
        $homeBefore->assertSee('message-nav-badge', false);

        $showResponse = $this->actingAs($recipient)->get(route('messages.show', $conversation));
        $showResponse->assertOk();
        $showResponse->assertSee('Unread message');

        $homeAfter = $this->actingAs($recipient)->get(route('home'));
        $homeAfter->assertDontSee('message-nav-badge', false);
    }

    public function test_member_can_reply_to_private_message_and_other_member_gets_unread_indicator(): void
    {
        [$sender, $recipient] = $this->makeMessagingPair();

        $conversation = Conversation::query()->create([
            'subject' => 'Reply chain',
        ]);

        $conversation->participants()->attach([
            $sender->id => [
                'joined_at' => now(),
                'last_read_at' => now(),
                'last_read_message_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            $recipient->id => [
                'joined_at' => now(),
                'last_read_at' => now(),
                'last_read_message_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $firstMessage = Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body_html' => '<p>First message</p>',
        ]);

        $conversation->participants()->updateExistingPivot($sender->id, [
            'last_read_message_id' => $firstMessage->id,
        ]);
        $conversation->participants()->updateExistingPivot($recipient->id, [
            'last_read_message_id' => $firstMessage->id,
        ]);

        $replyResponse = $this->actingAs($recipient)->post(route('messages.reply', $conversation), [
            'message' => '<p>Reply back</p>',
        ]);

        $replyResponse->assertRedirect(route('messages.show', $conversation));
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $recipient->id,
        ]);

        $senderHome = $this->actingAs($sender)->get(route('home'));
        $senderHome->assertSee('message-nav-badge', false);
    }

    public function test_member_cannot_message_self(): void
    {
        [$sender] = $this->makeMessagingPair();

        $response = $this->from(route('messages.create'))
            ->actingAs($sender)
            ->post(route('messages.store'), [
                'recipient' => 'sender-user',
                'subject' => 'Self note',
                'message' => '<p>Nope</p>',
            ]);

        $response->assertRedirect(route('messages.create'));
        $response->assertSessionHasErrors('recipient');
    }

    public function test_member_cannot_open_other_members_conversation(): void
    {
        [$sender, $recipient] = $this->makeMessagingPair();
        $outsider = $this->makeUser('outsider-user', 'Outsider');

        $conversation = Conversation::query()->create([
            'subject' => 'Private',
        ]);

        $conversation->participants()->attach([
            $sender->id => [
                'joined_at' => now(),
                'last_read_at' => now(),
                'last_read_message_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            $recipient->id => [
                'joined_at' => now(),
                'last_read_at' => now(),
                'last_read_message_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body_html' => '<p>Private text</p>',
        ]);

        $this->actingAs($outsider)->get(route('messages.show', $conversation))->assertForbidden();
    }

    public function test_member_can_archive_unarchive_and_delete_conversation(): void
    {
        [$sender, $recipient] = $this->makeMessagingPair();
        $conversation = $this->makeConversationBetween($sender, $recipient, 'Archive lane', '<p>Hello archive</p>');

        $this->actingAs($recipient)
            ->post(route('messages.archive', $conversation))
            ->assertRedirect(route('messages.index'));

        $this->assertNotNull($this->participantState($conversation, $recipient, 'archived_at'));

        $this->actingAs($recipient)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertDontSee('Archive lane');

        $this->actingAs($recipient)
            ->get(route('messages.index', ['folder' => 'archived']))
            ->assertOk()
            ->assertSee('Archive lane')
            ->assertSee(__('Unarchive'));

        $this->actingAs($recipient)
            ->delete(route('messages.archive.destroy', $conversation))
            ->assertRedirect(route('messages.index', ['folder' => 'archived']));

        $this->assertNull($this->participantState($conversation, $recipient, 'archived_at'));

        $this->actingAs($recipient)
            ->delete(route('messages.destroy', $conversation))
            ->assertRedirect(route('messages.index'));

        $this->assertNotNull($this->participantState($conversation, $recipient, 'deleted_at'));

        $this->actingAs($recipient)->get(route('messages.show', $conversation))->assertForbidden();
        $this->actingAs($recipient)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertDontSee('Archive lane');
    }

    public function test_member_can_bulk_delete_conversations_from_inbox(): void
    {
        [$sender, $recipient] = $this->makeMessagingPair();

        $firstConversation = $this->makeConversationBetween($sender, $recipient, 'Inbox bulk 1', '<p>First bulk delete</p>');
        $secondConversation = $this->makeConversationBetween($sender, $recipient, 'Inbox bulk 2', '<p>Second bulk delete</p>');

        $response = $this->actingAs($recipient)
            ->delete(route('messages.destroy-many'), [
                'conversation_ids' => [$firstConversation->id, $secondConversation->id],
                'folder' => 'inbox',
            ]);

        $response->assertRedirect(route('messages.index'));
        $this->assertNotNull($this->participantState($firstConversation, $recipient, 'deleted_at'));
        $this->assertNotNull($this->participantState($secondConversation, $recipient, 'deleted_at'));

        $this->actingAs($recipient)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertDontSee('Inbox bulk 1')
            ->assertDontSee('Inbox bulk 2');
    }

    public function test_member_can_bulk_delete_conversations_from_archived_folder(): void
    {
        [$sender, $recipient] = $this->makeMessagingPair();

        $conversation = $this->makeConversationBetween($sender, $recipient, 'Archived bulk', '<p>Archive bulk delete</p>');

        $this->actingAs($recipient)
            ->post(route('messages.archive', $conversation))
            ->assertRedirect(route('messages.index'));

        $response = $this->actingAs($recipient)
            ->delete(route('messages.destroy-many'), [
                'conversation_ids' => [$conversation->id],
                'folder' => 'archived',
            ]);

        $response->assertRedirect(route('messages.index', ['folder' => 'archived']));
        $this->assertNotNull($this->participantState($conversation, $recipient, 'deleted_at'));
    }

    public function test_blocked_member_cannot_start_or_reply_to_private_messages(): void
    {
        [$sender, $recipient] = $this->makeMessagingPair();

        $this->actingAs($recipient)
            ->post(route('members.block', $sender))
            ->assertRedirect();

        $this->assertDatabaseHas('direct_message_preferences', [
            'user_id' => $recipient->id,
            'target_user_id' => $sender->id,
            'is_blocked' => true,
        ]);

        $sendResponse = $this->from(route('messages.create'))
            ->actingAs($sender)
            ->post(route('messages.store'), [
                'recipient' => $recipient->username,
                'subject' => 'Blocked send',
                'message' => '<p>Should fail</p>',
            ]);

        $sendResponse->assertRedirect(route('messages.create'));
        $sendResponse->assertSessionHasErrors('recipient');
        $this->assertDatabaseCount('conversations', 0);

        $conversation = $this->makeConversationBetween($sender, $recipient, 'Existing thread', '<p>Old message</p>');

        $replyResponse = $this->from(route('messages.show', $conversation))
            ->actingAs($sender)
            ->post(route('messages.reply', $conversation), [
                'message' => '<p>Blocked reply</p>',
            ]);

        $replyResponse->assertRedirect(route('messages.show', $conversation));
        $replyResponse->assertSessionHasErrors('recipient');
        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $conversation->id,
            'body_html' => '<p>Blocked reply</p>',
        ]);

        $this->actingAs($recipient)
            ->delete(route('members.unblock', $sender))
            ->assertRedirect();

        $this->assertDatabaseMissing('direct_message_preferences', [
            'user_id' => $recipient->id,
            'target_user_id' => $sender->id,
        ]);
    }

    public function test_muted_member_does_not_raise_unread_badge_but_conversation_still_appears(): void
    {
        [$sender, $recipient] = $this->makeMessagingPair();

        $this->actingAs($recipient)
            ->post(route('members.mute', $sender))
            ->assertRedirect();

        $this->assertDatabaseHas('direct_message_preferences', [
            'user_id' => $recipient->id,
            'target_user_id' => $sender->id,
            'is_muted' => true,
        ]);

        $this->actingAs($sender)->post(route('messages.store'), [
            'recipient' => $recipient->username,
            'subject' => 'Muted thread',
            'message' => '<p>Muted hello</p>',
        ])->assertRedirect();

        $this->actingAs($recipient)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('message-nav-badge', false);

        $this->actingAs($recipient)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Muted thread')
            ->assertSee('Muted');

        $this->actingAs($recipient)
            ->delete(route('members.unmute', $sender))
            ->assertRedirect();

        $this->assertDatabaseMissing('direct_message_preferences', [
            'user_id' => $recipient->id,
            'target_user_id' => $sender->id,
        ]);
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function makeMessagingPair(): array
    {
        return [
            $this->makeUser('sender-user', 'Sender User'),
            $this->makeUser('recipient-user', 'Recipient User'),
        ];
    }

    private function makeConversationBetween(User $sender, User $recipient, string $subject, string $body): Conversation
    {
        $conversation = Conversation::query()->create([
            'subject' => $subject,
        ]);

        $conversation->participants()->attach([
            $sender->id => [
                'joined_at' => now(),
                'last_read_at' => now(),
                'last_read_message_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            $recipient->id => [
                'joined_at' => now(),
                'last_read_at' => null,
                'last_read_message_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body_html' => $body,
        ]);

        $conversation->participants()->updateExistingPivot($sender->id, [
            'last_read_message_id' => $message->id,
            'updated_at' => now(),
        ]);

        return $conversation->fresh();
    }

    private function participantState(Conversation $conversation, User $user, string $column): mixed
    {
        return DB::table('conversation_participants')
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->value($column);
    }

    private function makeUser(string $username, string $displayName): User
    {
        $user = User::query()->create([
            'username' => $username,
            'email' => $username.'@example.com',
            'password' => Hash::make('secret-password'),
            'role' => 'user',
            'account_status' => 'active',
            'legacy_level' => 0,
            'password_reset_required' => false,
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'display_name' => $displayName,
        ]);

        return $user;
    }
}
