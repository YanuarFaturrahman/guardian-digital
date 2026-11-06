<?php

namespace App\Http\Middleware;

use App\Support\GateRoutes;
use App\Support\MailStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Akun yang mendaftar sendiri harus memverifikasi email dulu (jika email memang bisa terkirim). */
class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user
            && ! $user->hasVerifiedEmail()
            && MailStatus::emailVerificationActive()
            && ! GateRoutes::isExempt($request)) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
