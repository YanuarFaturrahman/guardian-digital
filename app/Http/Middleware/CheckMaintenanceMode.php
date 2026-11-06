<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mode pemeliharaan: saat aktif, semua halaman menampilkan pesan 503
 * kecuali untuk superadmin, halaman masuk, dan keluar.
 */
class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::bool('maintenance_mode')) {
            return $next($request);
        }

        $user = $request->user();

        if ($user?->role === 'superadmin'
            || $request->routeIs('login', 'logout', 'two-factor.challenge', 'two-factor.challenge.store')
            || $request->is('masuk')) {
            return $next($request);
        }

        return response()
            ->view('maintenance', ['message' => Setting::read('maintenance_message')], 503)
            ->header('Retry-After', '3600');
    }
}
