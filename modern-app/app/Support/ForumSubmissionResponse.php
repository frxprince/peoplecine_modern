<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ForumSubmissionResponse
{
    public static function toRoute(
        Request $request,
        string $route,
        array $parameters,
        string $message,
        int $jsonStatus = 200
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            $request->session()->flash('status', $message);

            return response()->json([
                // Keep browser navigation on the current origin behind HTTPS proxies.
                'redirect' => route($route, $parameters, false),
            ], $jsonStatus);
        }

        return redirect()->route($route, $parameters, 303)->with('status', $message);
    }
}
