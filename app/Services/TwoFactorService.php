<?php

namespace App\Services;

use App\Models\User;
use App\Support\Totp;
use Illuminate\Support\Str;

class TwoFactorService
{
    /** Buat rahasia baru yang belum aktif (menunggu konfirmasi kode pertama). */
    public function beginSetup(User $user): string
    {
        if ($user->two_factor_secret === null || $user->two_factor_confirmed_at !== null) {
            $user->forceFill([
                'two_factor_secret' => Totp::generateSecret(),
                'two_factor_confirmed_at' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_last_step' => null,
            ])->save();
        }

        return $user->two_factor_secret;
    }

    /** Cocokkan kode 6 digit. Berhasil hanya sekali per langkah waktu (anti pakai ulang). */
    public function verifyCode(User $user, string $code): bool
    {
        if ($user->two_factor_secret === null) {
            return false;
        }

        $step = Totp::verify($user->two_factor_secret, $code, $user->two_factor_last_step);

        if ($step === null) {
            return false;
        }

        $user->forceFill(['two_factor_last_step' => $step])->save();

        return true;
    }

    /** Aktifkan setelah kode pertama benar. Mengembalikan kode pemulihan (tampil sekali saja). */
    public function confirm(User $user, string $code): ?array
    {
        if ($user->two_factor_secret === null || $user->two_factor_confirmed_at !== null) {
            return null;
        }

        if (! $this->verifyCode($user, $code)) {
            return null;
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $this->regenerateRecoveryCodes($user);
    }

    /** @return array<int,string> kode asli untuk ditampilkan; yang disimpan hanya hash-nya. */
    public function regenerateRecoveryCodes(User $user): array
    {
        $plain = [];
        for ($i = 0; $i < 8; $i++) {
            $plain[] = Str::lower(Str::random(5) . '-' . Str::random(5));
        }

        $user->forceFill([
            'two_factor_recovery_codes' => array_map(fn ($c) => $this->hashCode($c), $plain),
        ])->save();

        return $plain;
    }

    /** Pakai satu kode pemulihan. Setiap kode hanya berlaku sekali. */
    public function useRecoveryCode(User $user, string $code): bool
    {
        $hash = $this->hashCode(Str::lower(trim($code)));
        $codes = $user->two_factor_recovery_codes ?? [];

        foreach ($codes as $i => $stored) {
            if (hash_equals($stored, $hash)) {
                unset($codes[$i]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }

    public function recoveryCodesLeft(User $user): int
    {
        return count($user->two_factor_recovery_codes ?? []);
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_step' => null,
        ])->save();
    }

    private function hashCode(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}
