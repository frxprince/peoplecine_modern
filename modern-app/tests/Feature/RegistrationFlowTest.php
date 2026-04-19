<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_registration_page(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertSee('Create a new PeopleCine account.');
        $response->assertSee('Human Check:', false);
    }

    public function test_guest_can_register_new_member_account(): void
    {
        $this->get(route('register'));
        session()->put('registration.started_at', now()->subSeconds(10)->timestamp);

        $response = $this->post(route('register.store'), [
            'username' => 'newmember',
            'display_name' => 'New Member',
            'email' => 'newmember@example.com',
            'password' => 'SecretPass123!',
            'password_confirmation' => 'SecretPass123!',
            'phone' => '0812345678',
            'province' => 'Bangkok',
            'website' => '',
            'human_check' => session('registration.challenge_answer'),
        ]);

        $response->assertRedirect(route('dashboard'));

        $user = User::query()->where('username', 'newmember')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('SecretPass123!', (string) $user->password));
        $this->assertSame(0, $user->memberLevel());
        $this->assertAuthenticatedAs($user);
        $this->assertSame('New Member', $user->profile?->display_name);
        $this->assertSame('Bangkok', $user->profile?->province);
    }

    public function test_registration_rejects_bot_honeypot_submission(): void
    {
        $this->get(route('register'));
        session()->put('registration.started_at', now()->subSeconds(10)->timestamp);

        $response = $this->from(route('register'))->post(route('register.store'), [
            'username' => 'spamuser',
            'display_name' => 'Spam User',
            'email' => 'spam@example.com',
            'password' => 'SecretPass123!',
            'password_confirmation' => 'SecretPass123!',
            'website' => 'https://bot.example',
            'human_check' => session('registration.challenge_answer'),
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('website');
        $this->assertDatabaseMissing('users', [
            'username' => 'spamuser',
        ]);
    }

    public function test_registration_rejects_fast_or_wrong_human_check_submission(): void
    {
        $this->get(route('register'));
        session()->put('registration.started_at', now()->timestamp);

        $fastResponse = $this->from(route('register'))->post(route('register.store'), [
            'username' => 'fastuser',
            'display_name' => 'Fast User',
            'email' => 'fast@example.com',
            'password' => 'SecretPass123!',
            'password_confirmation' => 'SecretPass123!',
            'website' => '',
            'human_check' => session('registration.challenge_answer'),
        ]);

        $fastResponse->assertRedirect(route('register'));
        $fastResponse->assertSessionHasErrors('human_check');

        $this->get(route('register'));
        session()->put('registration.started_at', now()->subSeconds(10)->timestamp);

        $wrongResponse = $this->from(route('register'))->post(route('register.store'), [
            'username' => 'wronganswer',
            'display_name' => 'Wrong Answer',
            'email' => 'wrong@example.com',
            'password' => 'SecretPass123!',
            'password_confirmation' => 'SecretPass123!',
            'website' => '',
            'human_check' => 999,
        ]);

        $wrongResponse->assertRedirect(route('register'));
        $wrongResponse->assertSessionHasErrors('human_check');
    }
}
