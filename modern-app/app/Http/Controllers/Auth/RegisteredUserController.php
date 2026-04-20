<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegisteredUserController extends Controller
{
    public function create(Request $request): View
    {
        [$left, $right] = $this->issueChallenge($request);

        return view('auth.register', [
            'title' => __('Register'),
            'challengeLeft' => $left,
            'challengeRight' => $right,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureNotRateLimited($request);

        $validated = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:users,username'],
            'display_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:50'],
            'province' => ['nullable', 'string', 'max:100'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'string', 'max:0'],
            'human_check' => ['required', 'integer'],
        ]);

        $this->validateBotProtection($request, $validated);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::query()->create([
                'username' => trim($validated['username']),
                'email' => Str::lower(trim($validated['email'])),
                'password' => Hash::make($validated['password']),
                'password_reset_required' => false,
                'role' => 'user',
                'account_status' => 'active',
                'legacy_level' => 0,
            ]);

            UserProfile::query()->create([
                'user_id' => $user->id,
                'display_name' => trim($validated['display_name']),
                'phone' => trim((string) ($validated['phone'] ?? '')) ?: null,
                'province' => trim((string) ($validated['province'] ?? '')) ?: null,
                'postal_code' => trim((string) ($validated['postcode'] ?? '')) ?: null,
                'address' => trim((string) ($validated['address'] ?? '')) ?: null,
            ]);

            return $user->fresh(['profile']);
        });

        Auth::login($user);
        $request->session()->regenerate();
        $this->clearChallenge($request);
        RateLimiter::clear($this->throttleKey($request));

        return redirect()
            ->route('dashboard')
            ->with('status', __('Your new member account has been created.'));
    }

    private function issueChallenge(Request $request): array
    {
        $left = random_int(2, 9);
        $right = random_int(1, 9);

        $request->session()->put('registration.challenge_answer', $left + $right);
        $request->session()->put('registration.started_at', now()->timestamp);

        return [$left, $right];
    }

    private function validateBotProtection(Request $request, array $validated): void
    {
        if (($validated['website'] ?? '') !== '') {
            $this->hitRateLimit($request);

            throw ValidationException::withMessages([
                'website' => __('Registration could not be completed. Please try again.'),
            ]);
        }

        $expectedAnswer = (int) $request->session()->get('registration.challenge_answer', -1);
        if ((int) $validated['human_check'] !== $expectedAnswer) {
            $this->issueChallenge($request);
            $this->hitRateLimit($request);

            throw ValidationException::withMessages([
                'human_check' => __('Please answer the human-check question correctly.'),
            ]);
        }

        $startedAt = (int) $request->session()->get('registration.started_at', 0);
        if ($startedAt === 0 || (now()->timestamp - $startedAt) < 3) {
            $this->issueChallenge($request);
            $this->hitRateLimit($request);

            throw ValidationException::withMessages([
                'human_check' => __('Please take a moment to complete the registration form.'),
            ]);
        }
    }

    private function ensureNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        throw ValidationException::withMessages([
            'username' => __('Too many registration attempts. Please wait a few minutes and try again.'),
        ]);
    }

    private function hitRateLimit(Request $request): void
    {
        RateLimiter::hit($this->throttleKey($request), 300);
    }

    private function throttleKey(Request $request): string
    {
        return 'register:'.Str::lower((string) $request->ip());
    }

    private function clearChallenge(Request $request): void
    {
        $request->session()->forget([
            'registration.challenge_answer',
            'registration.started_at',
        ]);
    }
}
