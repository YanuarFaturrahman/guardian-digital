<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Halaman yang selalu boleh dibuka walau pengguna belum menyelesaikan langkah wajib
 * (ganti kata sandi, verifikasi email, aktivasi 2FA, persetujuan privasi).
 */
final class GateRoutes
{
    public const EXEMPT = [
        'logout',
        'password.force', 'password.force.update',
        'verification.*',
        'two-factor.*',
        'privacy.*',
        'consent.*',
        'account.privacy', 'account.export', 'account.delete',
    ];

    public static function isExempt(Request $request): bool
    {
        return $request->routeIs(...self::EXEMPT);
    }
}
