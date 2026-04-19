<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('auth.password-edit', [
            'title' => 'Choose a New Password',
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['nullable', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (! $user->requiresPasswordReset()) {
            if (! Hash::check((string) ($validated['current_password'] ?? ''), $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => 'The current password does not match this account.',
                ]);
            }
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'password_reset_required' => false,
            'remember_token' => null,
        ])->save();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Your password has been updated.');
    }
}
