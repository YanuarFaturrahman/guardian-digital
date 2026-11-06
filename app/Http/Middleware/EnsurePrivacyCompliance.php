<?php

namespace App\Http\Middleware;

use App\Support\GateRoutes;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 1) Pengguna harus menyetujui kebijakan privasi versi terbaru (siswa juga mengisi tanggal lahir).
 * 2) Siswa di bawah usia persetujuan hanya bisa memakai aplikasi setelah orang tua/wali menyetujui.
 */
class EnsurePrivacyCompliance
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || GateRoutes::isExempt($request)) {
            return $next($request);
        }

        if (! $user->hasAcceptedCurrentPrivacyPolicy() || $user->needsBirthDate()) {
            return redirect()->route('privacy.accept');
        }

        if ($user->needsParentalConsent()) {
            return redirect()->route('consent.pending');
        }

        return $next($request);
    }
}
