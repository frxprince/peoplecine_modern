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
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login', [
            'title' => 'Sign In',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['present', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $login = trim((string) $credentials['login']);

        $user = User::query()
            ->with('profile')
            ->where(function ($query) use ($login) {
                $query->whereRaw('LOWER(username) = ?', [Str::lower($login)])
                    ->orWhereRaw('LOWER(email) = ?', [Str::lower($login)]);
            })
            ->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => 'The provided sign-in details do not match our records.',
            ]);
        }

        if ($user->isBanned()) {
            throw ValidationException::withMessages([
                'login' => 'This account has been banned. Please contact an administrator.',
            ]);
        }

        if ($user->isDisabled()) {
            throw ValidationException::withMessages([
                'login' => 'This account has been disabled. Please contact an administrator.',
            ]);
        }

        Auth::login($user, (bool) ($credentials['remember'] ?? false));
        $request->session()->regenerate();

        return redirect()
            ->intended(route('dashboard'))
            ->with('status', 'Welcome back to PeopleCine Modern.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->with('status', 'You have been signed out.');
    }
}
