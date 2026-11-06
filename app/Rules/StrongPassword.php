<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Satu aturan kata sandi untuk semua tempat (daftar, atur ulang, ganti, akun baru).
 * Peran admin (superadmin, admin sekolah) memakai aturan lebih ketat.
 */
class StrongPassword implements ValidationRule
{
    private const COMMON = [
        'password', 'password1', 'password123', 'passw0rd', 'kata sandi', 'katasandi', 'katasandi123',
        '12345678', '123456789', '1234567890', 'qwerty123', 'qwertyuiop', 'abc12345', 'abcd1234',
        'iloveyou1', 'admin123', 'admin1234', 'guardian123', 'guardiandigital', 'sekolah123',
        'indonesia1', 'indonesia123', 'welcome1', 'letmein123', 'asdfghjkl1', 'asdf1234',
    ];

    public function __construct(private ?string $role = null, private ?string $email = null)
    {
    }

    public static function privileged(?string $role): bool
    {
        return in_array($role, config('security.two_factor_roles', []), true);
    }

    /** Pesan kesalahan pertama, atau null jika kata sandi memenuhi syarat. */
    public static function check(string $password, ?string $role = null, ?string $email = null): ?string
    {
        $privileged = self::privileged($role);
        $min = $privileged
            ? (int) config('security.password.min_length_privileged', 12)
            : (int) config('security.password.min_length', 8);

        if (mb_strlen($password) < $min) {
            return "Kata sandi minimal {$min} karakter.";
        }
        if (! preg_match('/\p{L}/u', $password) || ! preg_match('/\d/', $password)) {
            return 'Kata sandi harus mengandung huruf dan angka.';
        }
        if ($privileged && (! preg_match('/\p{Ll}/u', $password) || ! preg_match('/\p{Lu}/u', $password))) {
            return 'Kata sandi akun admin harus memuat huruf besar dan huruf kecil.';
        }
        if (in_array(mb_strtolower($password), self::COMMON, true) || preg_match('/^(.)\1+$/u', $password)) {
            return 'Kata sandi terlalu mudah ditebak. Pilih yang lebih unik.';
        }
        if ($email) {
            $local = mb_strtolower(explode('@', $email)[0]);
            if (mb_strlen($local) >= 4 && str_contains(mb_strtolower($password), $local)) {
                return 'Kata sandi tidak boleh memuat bagian dari emailmu.';
            }
        }

        return null;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($message = self::check((string) $value, $this->role, $this->email)) {
            $fail($message);
        }
    }
}
