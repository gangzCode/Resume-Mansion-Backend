<?php

use App\Http\Middleware\Cors;
use App\Http\Middleware\ValidateAPIRole;
use App\Http\Middleware\ValidateRole;
use App\Http\Middleware\ValidateStatus;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => ValidateRole::class,
            'status' => ValidateStatus::class,
            'api_role' => ValidateAPIRole::class,
        ]);
        $middleware->append(Cors::class); 
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
