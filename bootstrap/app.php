<?php

use App\Domain\File\Exception\StoredFileNotFound;
use App\Presentation\Scheduling\FileRetentionSchedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$application = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../src/Presentation/Http/Routes/web.php',
        commands: __DIR__.'/../src/Presentation/Cli/Routes/console.php',
        health: '/up',
    )
    ->withSchedule(new FileRetentionSchedule)
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (StoredFileNotFound $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 404);
            }

            abort(404, $exception->getMessage());
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->expectsJson() || $request->is('api/*'),
        );
    })->create();

$application->useAppPath(dirname(__DIR__).'/src');

return $application;
