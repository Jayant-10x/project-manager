<?php

use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }

            // Ensure session is initialized from cookie if an error occurred before route middleware ran
            if (! $request->hasSession()) {
                try {
                    $session = app('session')->driver();
                    $cookieName = config('session.cookie');
                    if ($request->cookies->has($cookieName)) {
                        $session->setId($request->cookies->get($cookieName));
                        $session->start();
                        $request->setLaravelSession($session);
                        $authGuard = app('auth')->guard();
                        if ($session->has($authGuard->getName())) {
                            $user = User::find($session->get($authGuard->getName()));
                            if ($user) {
                                $authGuard->setUser($user);
                            }
                        }
                    }
                } catch (Throwable) {
                    // Silently continue to error view rendering
                }
            }

            return null;
        });
    })->create();
