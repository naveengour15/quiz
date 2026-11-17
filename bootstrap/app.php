<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);
        $middleware->validateCsrfTokens(except: [
            'androidapi/*',
            'api/*',
            'fsrapp/quizes/api/mark-out/*',
            'fsrapp/quizes/api/clear-session/*',
        ]);
        $middleware->api(append: [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
        ]);
        $middleware->alias([
            'panel' => \App\Http\Middleware\PanelMiddleware::class,
            'auth.token' => \App\Http\Middleware\AuthenticateApiToken::class,
            'static.token' => \App\Http\Middleware\VerifyStaticSecretKey::class,
            'fsr.auth' => \App\Http\Middleware\FsrAppAuthMiddleware::class,
            'fsr.locale' => \App\Http\Middleware\SetAppLocale::class,
            'track.visitors' => \App\Http\Middleware\TrackPageVisitors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('androidapi/*'),
        );
    })->create();