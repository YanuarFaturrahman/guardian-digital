<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_masuk_berhasil_dan_diarahkan_ke_beranda_peran(): void
    {
        $user = User::factory()->create(['email' => 'rani@contoh.test']);

        $this->post('/masuk', ['email' => 'rani@contoh.test', 'password' => 'Password123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.login']);
    }

    public function test_kata_sandi_salah_ditolak_dan_dicatat(): void
    {
        User::factory()->create(['email' => 'rani@contoh.test']);

        $this->from('/masuk')->post('/masuk', ['email' => 'rani@contoh.test', 'password' => 'salah'])
            ->assertRedirect('/masuk')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.login_failed']);
    }

    public function test_email_tidak_terdaftar_mendapat_pesan_yang_sama(): void
    {
        $this->post('/masuk', ['email' => 'tidakada@contoh.test', 'password' => 'apapun123'])
            ->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak cocok.']);
    }

    public function test_akun_terkunci_setelah_lima_kali_salah_walau_dari_ip_lain(): void
    {
        $user = User::factory()->create(['email' => 'rani@contoh.test']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/masuk', ['email' => 'rani@contoh.test', 'password' => 'salah' . $i]);
        }

        // Kata sandi yang BENAR pun ditolak selama terkunci, dari IP berbeda sekalipun.
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->post('/masuk', ['email' => 'rani@contoh.test', 'password' => 'Password123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertStringStartsWith('Terlalu banyak percobaan masuk.', session('errors')->first('email'));
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.lockout']);
    }

    public function test_penguncian_tidak_membedakan_huruf_besar_kecil_email(): void
    {
        User::factory()->create(['email' => 'rani@contoh.test']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/masuk', ['email' => $i % 2 ? 'RANI@contoh.test' : 'rani@contoh.test', 'password' => 'salah' . $i]);
        }

        $this->post('/masuk', ['email' => 'Rani@Contoh.test', 'password' => 'Password123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_penguncian_hanya_untuk_akun_yang_diserang(): void
    {
        User::factory()->create(['email' => 'rani@contoh.test']);
        $lain = User::factory()->create(['email' => 'bima@contoh.test']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/masuk', ['email' => 'rani@contoh.test', 'password' => 'salah' . $i]);
        }

        $this->post('/masuk', ['email' => 'bima@contoh.test', 'password' => 'Password123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($lain);
    }

    public function test_masuk_berhasil_menghapus_hitungan_gagal(): void
    {
        User::factory()->create(['email' => 'rani@contoh.test']);

        for ($i = 0; $i < 4; $i++) {
            $this->post('/masuk', ['email' => 'rani@contoh.test', 'password' => 'salah' . $i]);
        }
        $this->post('/masuk', ['email' => 'rani@contoh.test', 'password' => 'Password123']);
        $this->post('/keluar');

        $this->assertSame(0, RateLimiter::attempts(\App\Http\Controllers\AuthController::lockoutKey('rani@contoh.test')));

        $this->post('/masuk', ['email' => 'rani@contoh.test', 'password' => 'salah'])->assertSessionHasErrors('email');
        $this->post('/masuk', ['email' => 'rani@contoh.test', 'password' => 'Password123'])->assertRedirect(route('dashboard'));
    }

    public function test_akun_nonaktif_tidak_bisa_masuk(): void
    {
        User::factory()->deactivated()->create(['email' => 'rani@contoh.test']);

        $this->post('/masuk', ['email' => 'rani@contoh.test', 'password' => 'Password123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_kata_sandi_lemah_lama_dipaksa_diganti_saat_masuk(): void
    {
        config(['security.force_strong_password_on_login' => true]);
        $user = User::factory()->create(['email' => 'rani@contoh.test', 'password' => 'password']);

        $this->post('/masuk', ['email' => 'rani@contoh.test', 'password' => 'password'])->assertRedirect();

        $this->assertTrue($user->fresh()->must_change_password);
        $this->get('/siswa')->assertRedirect(route('password.force'));
    }

    public function test_kata_sandi_kuat_tidak_dipaksa_diganti(): void
    {
        config(['security.force_strong_password_on_login' => true]);
        $user = User::factory()->create(['email' => 'rani@contoh.test']);

        $this->post('/masuk', ['email' => 'rani@contoh.test', 'password' => 'Password123']);
        // "Password123" ada di daftar umum, jadi dipaksa ganti. Pakai yang benar-benar kuat:
        $user->forceFill(['password' => 'Kucing2024x', 'must_change_password' => false])->save();
        $this->post('/keluar');

        $this->post('/masuk', ['email' => 'rani@contoh.test', 'password' => 'Kucing2024x']);
        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_ganti_kata_sandi_dari_halaman_akun(): void
    {
        $user = User::factory()->create(['password' => 'Kucing2024x']);

        $this->actingAs($user)->put('/akun/kata-sandi', [
            'current_password' => 'salah',
            'password' => 'KataBaru2025y', 'password_confirmation' => 'KataBaru2025y',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($user)->put('/akun/kata-sandi', [
            'current_password' => 'Kucing2024x',
            'password' => 'lemah', 'password_confirmation' => 'lemah',
        ])->assertSessionHasErrors('password');

        $this->actingAs($user)->put('/akun/kata-sandi', [
            'current_password' => 'Kucing2024x',
            'password' => 'KataBaru2025y', 'password_confirmation' => 'KataBaru2025y',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(\Hash::check('KataBaru2025y', $user->fresh()->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.password_changed']);
    }
}
