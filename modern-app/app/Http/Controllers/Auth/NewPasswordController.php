<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class NewPasswordController extends Controller
{
    public function create(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'title' => 'Reset Password',
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email:rfc'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::broker()->reset(
            $validated,
            function ($user) use ($validated) {
                if ($user->isBanned()) {
                    throw ValidationException::withMessages([
                        'email' => 'This account has been banned. Please contact an administrator.',
                    ]);
                }

                if ($user->isDisabled()) {
                    throw ValidationException::withMessages([
                        'email' => 'This account has been disabled. Please contact an administrator.',
                    ]);
                }

                $user->forceFill([
                    'password' => Hash::make($validated['password']),
                    'password_reset_required' => false,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        return redirect()
            ->route('login')
            ->with('status', 'Your password has been reset. You can sign in now.');
    }
}
