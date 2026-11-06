<?php

namespace App\Http\Middleware;

use App\Support\GateRoutes;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Superadmin dan admin sekolah wajib mengaktifkan verifikasi dua langkah. */
class EnsureTwoFactorEnrollment
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user
            && $user->requiresTwoFactor()
            && ! $user->hasTwoFactorEnabled()
            && ! GateRoutes::isExempt($request)) {
            return redirect()->route('two-factor.setup')
                ->with('status', 'Akun admin wajib memakai verifikasi dua langkah. Aktifkan dulu sebelum melanjutkan.');
        }

        return $next($request);
    }
}
