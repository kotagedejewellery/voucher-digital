<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\PrivacyHeaders::class);
        $middleware->encryptCookies(except: ['_fbp','_fbc']);
        $middleware->trustProxies(at: array_values(array_filter(explode(',',(string)env('TRUSTED_PROXIES','')))));
        $middleware->alias(['active'=>\App\Http\Middleware\ActiveAdmin::class]);
        $middleware->prependToPriorityList(\Illuminate\Routing\Middleware\SubstituteBindings::class,\App\Http\Middleware\ActiveAdmin::class);
        $middleware->redirectGuestsTo('/admin/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $exception, Request $request) {
            if ($request->expectsJson() || !$request->is('admin/login')) return null;
            $response=app(\App\Http\Controllers\AuthController::class)->throttled($request, (int)($exception->getHeaders()['Retry-After'] ?? 60));
            $response->headers->add($exception->getHeaders());
            return $response;
        });
    })->create();
