<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        if (! in_array($locale, ['th', 'en'], true)) {
            abort(404);
        }

        $request->session()->put('locale', $locale);

        return redirect()->back()->withCookie(cookie(
            'peoplecine_locale',
            $locale,
            60 * 24 * 180,
            '/',
            null,
            $request->isSecure(),
            false,
            false,
            'lax'
        ));
    }
}
