<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordResetIsCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->requiresPasswordReset()) {
            return redirect()
                ->route('password.edit')
                ->with('status', 'Please set a new password before accessing member areas.');
        }

        return $next($request);
    }
}
