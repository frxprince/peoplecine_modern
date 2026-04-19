<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password', [
            'title' => 'Recover Password',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
        ]);

        $login = Str::lower(trim((string) $validated['login']));

        $user = User::query()
            ->where(function ($query) use ($login) {
                $query->whereRaw('LOWER(username) = ?', [$login])
                    ->orWhereRaw('LOWER(email) = ?', [$login]);
            })
            ->first();

        if ($user !== null && $user->canAuthenticate() && filled($user->email)) {
            Password::broker()->sendResetLink([
                'email' => $user->email,
            ]);
        }

        return back()->with('status', 'If that account has an email address on file, a recovery link has been sent.');
    }
}
