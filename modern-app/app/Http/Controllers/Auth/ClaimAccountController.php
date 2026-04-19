<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ClaimAccountController extends Controller
{
    public function create(): View
    {
        return view('auth.claim-account', [
            'title' => 'Claim Legacy Account',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $username = trim((string) $validated['username']);
        $email = Str::lower(trim((string) $validated['email']));

        $user = User::query()
            ->whereRaw('LOWER(username) = ?', [Str::lower($username)])
            ->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'username' => 'We could not find a legacy account with that username.',
            ]);
        }

        if ($user->account_status !== 'active') {
            throw ValidationException::withMessages([
                'username' => 'This account is not currently available for self-service activation.',
            ]);
        }

        if (! $user->requiresPasswordReset()) {
            throw ValidationException::withMessages([
                'username' => 'This account has already been claimed. Please sign in instead.',
            ]);
        }

        if ($user->email === null) {
            throw ValidationException::withMessages([
                'email' => 'This legacy account does not have an email address on file yet. Please contact an administrator for manual help.',
            ]);
        }

        if (Str::lower($user->email) !== $email) {
            throw ValidationException::withMessages([
                'email' => 'The email address does not match the legacy account on file.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'password_reset_required' => false,
            'email_verified_at' => now(),
            'remember_token' => null,
        ])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Your legacy account is now activated in the new site.');
    }
}
