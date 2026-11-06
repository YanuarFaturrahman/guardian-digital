<?php

namespace Tests\Unit;

use App\Rules\StrongPassword;
use Tests\TestCase;

class StrongPasswordTest extends TestCase
{
    public function test_aturan_dasar(): void
    {
        $this->assertNull(StrongPassword::check('Kucing2024x', 'student'));
        $this->assertNotNull(StrongPassword::check('pendek1', 'student'));
        $this->assertNotNull(StrongPassword::check('hurufsajasemua', 'student'));
        $this->assertNotNull(StrongPassword::check('1234567890123', 'student'));
    }

    public function test_kata_sandi_umum_ditolak(): void
    {
        $this->assertNotNull(StrongPassword::check('password123', 'student'));
        $this->assertNotNull(StrongPassword::check('Password123', 'student'));
        $this->assertNotNull(StrongPassword::check('Qwerty123', 'student'));
    }

    public function test_tidak_boleh_memuat_bagian_email(): void
    {
        $this->assertNotNull(StrongPassword::check('Budisantoso99', 'student', 'budisantoso@mail.test'));
        $this->assertNull(StrongPassword::check('Kucing2024x', 'student', 'budisantoso@mail.test'));
    }

    public function test_akun_admin_butuh_12_karakter_dan_huruf_besar_kecil(): void
    {
        $this->assertNotNull(StrongPassword::check('Kucing2024x', 'superadmin'));      // 11 karakter
        $this->assertNotNull(StrongPassword::check('kucing2024xyz', 'school_admin'));  // tanpa huruf besar
        $this->assertNull(StrongPassword::check('Kucing2024xyz', 'superadmin'));
    }
}
