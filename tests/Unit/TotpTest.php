<?php

namespace Tests\Unit;

use App\Support\Totp;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    // Vektor uji RFC 6238 (SHA-1, rahasia ASCII "12345678901234567890"), dipotong ke 6 digit.
    public function test_kode_sesuai_vektor_rfc_6238(): void
    {
        $secret = Totp::base32Encode('12345678901234567890');

        $this->assertSame('287082', Totp::code($secret, intdiv(59, 30)));
        $this->assertSame('081804', Totp::code($secret, intdiv(1111111109, 30)));
        $this->assertSame('050471', Totp::code($secret, intdiv(1111111111, 30)));
        $this->assertSame('005924', Totp::code($secret, intdiv(1234567890, 30)));
        $this->assertSame('279037', Totp::code($secret, intdiv(2000000000, 30)));
    }

    public function test_base32_bolak_balik(): void
    {
        $raw = random_bytes(20);

        $this->assertSame($raw, Totp::base32Decode(Totp::base32Encode($raw)));
        $this->assertSame(32, strlen(Totp::generateSecret()));
    }

    public function test_verifikasi_menerima_jendela_waktu_dan_menolak_di_luarnya(): void
    {
        $secret = Totp::generateSecret();
        $time = 1_700_000_000;
        $step = Totp::currentStep($time);

        $this->assertSame($step, Totp::verify($secret, Totp::code($secret, $step), null, 1, $time));
        $this->assertSame($step - 1, Totp::verify($secret, Totp::code($secret, $step - 1), null, 1, $time));
        $this->assertNull(Totp::verify($secret, Totp::code($secret, $step - 3), null, 1, $time));
    }

    public function test_kode_tidak_bisa_dipakai_ulang(): void
    {
        $secret = Totp::generateSecret();
        $time = 1_700_000_000;
        $step = Totp::currentStep($time);

        $this->assertNull(Totp::verify($secret, Totp::code($secret, $step), $step, 1, $time));
        $this->assertNull(Totp::verify($secret, Totp::code($secret, $step - 1), $step, 1, $time));
    }

    public function test_format_kode_salah_ditolak(): void
    {
        $secret = Totp::generateSecret();

        $this->assertNull(Totp::verify($secret, 'abcdef'));
        $this->assertNull(Totp::verify($secret, '12345'));
        $this->assertNull(Totp::verify($secret, ''));
    }
}
