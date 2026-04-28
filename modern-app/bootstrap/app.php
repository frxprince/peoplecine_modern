<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO |
                Request::HEADER_X_FORWARDED_PREFIX |
                Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'programmer' => \App\Http\Middleware\EnsureUserIsProgrammer::class,
            'password.reset.completed' => \App\Http\Middleware\EnsurePasswordResetIsCompleted::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
                return null;
            }

            // Don't redirect 404s for media/assets to the landing page.
            if ($request->is([
                'css/*',
                'js/*',
                'images/*',
                'vendor/*',
                'storage/*',
                'legacy-media/*',
                'legacy-inline-media*',
                'legacy-article-media*',
                'managed-banners/*',
                'avatars/*',
                'articles/pdf/*',
            ])) {
                return null;
            }

            $message = app()->getLocale() === 'th'
                ? 'ไม่พบหน้าที่ต้องการ'
                : 'Page not found';

            return redirect()
                ->route('landing')
                ->with('header_error', $message);
        });
    })->create();
