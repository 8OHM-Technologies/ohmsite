<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ApiAccessMiddleware;
use App\Http\Middleware\DatasetAccessMiddleware;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SubscribedMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            AuthenticateSession::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'subscribed' => SubscribedMiddleware::class,
            'has.dataset.access' => DatasetAccessMiddleware::class,
            'has.api.access' => ApiAccessMiddleware::class,
        ]);

        // Security Headers
        $middleware->statefulApi(); // For Sanctum
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
