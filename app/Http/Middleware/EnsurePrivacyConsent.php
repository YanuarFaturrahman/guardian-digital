<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gerbang persetujuan: pengguna harus menyetujui kebijakan privasi versi terbaru,
 * dan siswa di bawah 18 tahun harus punya persetujuan orang tua / wali,
 * sebelum memakai fitur lain. Halaman privasi, persetujuan, dan keluar tetap terbuka.
 */
class EnsurePrivacyConsent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! config('privacy.enforce')) {
            return $next($request);
        }

        if ($request->routeIs('consent.*', 'privacy.*', 'logout', 'password.force', 'password.force.update')) {
            return $next($request);
        }

        if ($user->privacyGate() !== null) {
            if ($request->expectsJson()) {
                abort(403, 'Persetujuan privasi diperlukan.');
            }

            return redirect()->route('consent.show');
        }

        return $next($request);
    }
}
