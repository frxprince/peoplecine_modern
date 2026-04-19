<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_password_recovery_page(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
        $response->assertSee('Recover access to your PeopleCine account.');
    }

    public function test_member_with_email_can_request_password_reset_link_using_username(): void
    {
        Notification::fake();

        $user = User::query()->create([
            'legacy_memberx_id' => 515,
            'username' => 'recoverable-user',
            'email' => 'recoverable@example.com',
            'password' => Hash::make('old-password'),
            'password_reset_required' => false,
            'role' => 'user',
            'account_status' => 'active',
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Recoverable User',
        ]);

        $response = $this->from(route('password.request'))->post(route('password.email'), [
            'login' => 'recoverable-user',
        ]);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas('status', 'If that account has an email address on file, a recovery link has been sent.');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_member_can_reset_password_from_recovery_link(): void
    {
        Notification::fake();

        $user = User::query()->create([
            'legacy_memberx_id' => 516,
            'username' => 'reset-member',
            'email' => 'resetmember@example.com',
            'password' => Hash::make('old-password'),
            'password_reset_required' => true,
            'role' => 'user',
            'account_status' => 'active',
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Reset Member',
        ]);

        $this->post(route('password.email'), [
            'login' => 'resetmember@example.com',
        ]);

        $token = null;

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->assertNotNull($token);

        $resetResponse = $this->post(route('password.store'), [
            'token' => $token,
            'email' => 'resetmember@example.com',
            'password' => 'BrandNewPass123!',
            'password_confirmation' => 'BrandNewPass123!',
        ]);

        $resetResponse->assertRedirect(route('login'));
        $resetResponse->assertSessionHas('status', 'Your password has been reset. You can sign in now.');

        $this->assertTrue(Hash::check('BrandNewPass123!', (string) $user->fresh()->password));
        $this->assertFalse((bool) $user->fresh()->password_reset_required);

        $loginResponse = $this->post(route('login.store'), [
            'login' => 'reset-member',
            'password' => 'BrandNewPass123!',
        ]);

        $loginResponse->assertRedirect(route('dashboard'));
    }

    public function test_password_recovery_does_not_send_link_for_account_without_email(): void
    {
        Notification::fake();

        $user = User::query()->create([
            'legacy_memberx_id' => 517,
            'username' => 'no-email-member',
            'email' => null,
            'password' => Hash::make('old-password'),
            'password_reset_required' => false,
            'role' => 'user',
            'account_status' => 'active',
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'display_name' => 'No Email Member',
        ]);

        $response = $this->from(route('password.request'))->post(route('password.email'), [
            'login' => 'no-email-member',
        ]);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas('status', 'If that account has an email address on file, a recovery link has been sent.');

        Notification::assertNothingSent();
    }
}
