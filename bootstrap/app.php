<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Endpoint ringan untuk monitor uptime eksternal (UptimeRobot, BetterStack, dll.).
            // Sengaja di luar grup "web": tanpa sesi, tanpa cookie, tanpa mode pemeliharaan.
            \Illuminate\Support\Facades\Route::get('/status', [\App\Http\Controllers\StatusController::class, 'show'])
                ->middleware('throttle:60,1')
                ->name('status');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Tambahkan baris ini di paling atas dalam withMiddleware
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);

        // Paksa pengguna yang memakai kata sandi sementara untuk menggantinya
        $middleware->web(append: [
            \App\Http\Middleware\CheckMaintenanceMode::class,
            \App\Http\Middleware\EnsureAccountIsActive::class,
            \App\Http\Middleware\RequirePasswordChange::class,
            \App\Http\Middleware\EnsureEmailIsVerified::class,
            \App\Http\Middleware\EnsureTwoFactorEnrollment::class,
            \App\Http\Middleware\EnsurePrivacyCompliance::class,
            \App\Http\Middleware\RunLazyScheduler::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Simpan error ke database (menu Sistem > Error Aplikasi). Log bawaan tetap berjalan.
        $exceptions->report(function (\Throwable $e) {
            \App\Support\ErrorReporter::capture($e);
        });
    })->create();