<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TwoFactorService;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function currentCode(User $user, int $offset = 0): string
    {
        return Totp::code($user->two_factor_secret, Totp::currentStep() + $offset);
    }

    public function test_admin_tanpa_2fa_dipaksa_ke_halaman_pemasangan(): void
    {
        $admin = User::factory()->superadmin()->create(['two_factor_secret' => null, 'two_factor_confirmed_at' => null]);

        $this->actingAs($admin)->get('/admin')->assertRedirect(route('two-factor.setup'));
        $this->actingAs($admin)->get('/akun/dua-langkah')->assertOk();
        $this->actingAs($admin)->get('/admin/sistem')->assertRedirect(route('two-factor.setup'));
    }

    public function test_admin_sekolah_juga_wajib_2fa_tetapi_siswa_tidak(): void
    {
        $schoolAdmin = User::factory()->schoolAdmin()->create(['two_factor_secret' => null, 'two_factor_confirmed_at' => null]);
        $this->actingAs($schoolAdmin)->get('/admin-sekolah')->assertRedirect(route('two-factor.setup'));

        $this->actingAs(User::factory()->create())->get('/siswa')->assertOk();
    }

    public function test_pemasangan_2fa_dengan_kode_benar_memberi_kode_pemulihan(): void
    {
        $admin = User::factory()->superadmin()->create(['two_factor_secret' => null, 'two_factor_confirmed_at' => null]);

        $this->actingAs($admin)->get('/akun/dua-langkah')->assertOk()->assertSee('otpauth://', false);
        $admin->refresh();

        $this->actingAs($admin)->post('/akun/dua-langkah', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertFalse($admin->fresh()->hasTwoFactorEnabled());

        $this->actingAs($admin)->post('/akun/dua-langkah', ['code' => $this->currentCode($admin)])
            ->assertRedirect(route('two-factor.setup'))
            ->assertSessionHas('recovery_codes');

        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());
        $this->assertCount(8, $admin->fresh()->two_factor_recovery_codes);
        $this->actingAs($admin->fresh())->get('/admin')->assertOk();
    }

    public function test_rahasia_dan_kode_pemulihan_tersimpan_terenkripsi(): void
    {
        $admin = User::factory()->superadmin()->create(['two_factor_secret' => null, 'two_factor_confirmed_at' => null]);
        $service = app(TwoFactorService::class);

        $secret = $service->beginSetup($admin);
        $plain = $service->confirm($admin, Totp::code($secret, Totp::currentStep()));

        $raw = \DB::table('users')->where('id', $admin->id)->first();
        $this->assertStringNotContainsString($secret, $raw->two_factor_secret);
        $this->assertStringNotContainsString($plain[0], $raw->two_factor_recovery_codes);
    }

    public function test_login_admin_butuh_kode_setelah_kata_sandi(): void
    {
        $admin = User::factory()->superadmin()->create(['email' => 'admin@contoh.test', 'password' => 'KataSandiAdmin99']);

        $this->post('/masuk', ['email' => 'admin@contoh.test', 'password' => 'KataSandiAdmin99'])
            ->assertRedirect(route('two-factor.challenge'));

        $this->assertGuest();
        $this->get('/admin')->assertRedirect(route('login'));

        $this->post('/verifikasi-dua-langkah', ['code' => $this->currentCode($admin)])
            ->assertRedirect();

        $this->assertAuthenticatedAs($admin);
    }

    public function test_kode_salah_ditolak_dan_tidak_login(): void
    {
        $admin = User::factory()->superadmin()->create(['email' => 'admin@contoh.test', 'password' => 'KataSandiAdmin99']);
        $this->post('/masuk', ['email' => 'admin@contoh.test', 'password' => 'KataSandiAdmin99']);

        $this->post('/verifikasi-dua-langkah', ['code' => '123456'])->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.two_factor_failed']);
    }

    public function test_kode_yang_sama_tidak_bisa_dipakai_dua_kali(): void
    {
        $admin = User::factory()->superadmin()->create(['email' => 'admin@contoh.test', 'password' => 'KataSandiAdmin99']);
        $code = $this->currentCode($admin);

        $this->post('/masuk', ['email' => 'admin@contoh.test', 'password' => 'KataSandiAdmin99']);
        $this->post('/verifikasi-dua-langkah', ['code' => $code]);
        $this->assertAuthenticated();

        $this->post('/keluar');
        $this->post('/masuk', ['email' => 'admin@contoh.test', 'password' => 'KataSandiAdmin99']);
        $this->post('/verifikasi-dua-langkah', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_kode_pemulihan_hanya_berlaku_sekali(): void
    {
        $admin = User::factory()->superadmin()->create(['email' => 'admin@contoh.test', 'password' => 'KataSandiAdmin99']);
        $codes = app(TwoFactorService::class)->regenerateRecoveryCodes($admin);

        $this->post('/masuk', ['email' => 'admin@contoh.test', 'password' => 'KataSandiAdmin99']);
        $this->post('/verifikasi-dua-langkah', ['recovery_code' => $codes[0]])->assertRedirect();
        $this->assertAuthenticatedAs($admin);
        $this->assertCount(7, $admin->fresh()->two_factor_recovery_codes);

        $this->post('/keluar');
        $this->post('/masuk', ['email' => 'admin@contoh.test', 'password' => 'KataSandiAdmin99']);
        $this->post('/verifikasi-dua-langkah', ['recovery_code' => $codes[0]])->assertSessionHasErrors('recovery_code');
        $this->assertGuest();
    }

    public function test_percobaan_kode_dibatasi(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $admin = User::factory()->superadmin()->create(['email' => 'admin@contoh.test', 'password' => 'KataSandiAdmin99']);
        $this->post('/masuk', ['email' => 'admin@contoh.test', 'password' => 'KataSandiAdmin99']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/verifikasi-dua-langkah', ['code' => '111111']);
        }

        // Kode yang benar pun ditolak setelah 5 kali salah.
        $this->post('/verifikasi-dua-langkah', ['code' => $this->currentCode($admin)])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_tantangan_tanpa_login_kata_sandi_ditolak(): void
    {
        $this->get('/verifikasi-dua-langkah')->assertRedirect(route('login'));
        $this->post('/verifikasi-dua-langkah', ['code' => '123456'])->assertRedirect(route('login'));
    }

    public function test_ingat_saya_diabaikan_untuk_akun_dengan_2fa_wajib(): void
    {
        $admin = User::factory()->superadmin()->create(['email' => 'admin@contoh.test', 'password' => 'KataSandiAdmin99']);
        $this->post('/masuk', ['email' => 'admin@contoh.test', 'password' => 'KataSandiAdmin99', 'remember' => '1']);
        $response = $this->post('/verifikasi-dua-langkah', ['code' => $this->currentCode($admin)]);

        $this->assertEmpty(collect($response->headers->getCookies())->filter(fn ($c) => str_starts_with($c->getName(), 'remember_web'))->all());
    }

    public function test_2fa_wajib_tidak_bisa_dimatikan_admin_tetapi_bisa_dimatikan_pengguna_biasa(): void
    {
        $admin = User::factory()->superadmin()->create(['password' => 'KataSandiAdmin99']);
        $this->actingAs($admin)->delete('/akun/dua-langkah', ['password' => 'KataSandiAdmin99'])->assertSessionHasErrors('password');
        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());

        $teacher = User::factory()->teacher()->withTwoFactor()->create(['password' => 'KataSandiGuru99']);
        $this->actingAs($teacher)->delete('/akun/dua-langkah', ['password' => 'KataSandiGuru99'])->assertRedirect();
        $this->assertFalse($teacher->fresh()->hasTwoFactorEnabled());
    }

    public function test_superadmin_bisa_mereset_2fa_admin_sekolah_tetapi_tidak_sebaliknya(): void
    {
        $super = User::factory()->superadmin()->create();
        $schoolAdmin = User::factory()->schoolAdmin()->create();

        $this->actingAs($schoolAdmin)->post("/pengguna/{$super->id}/reset-dua-langkah")->assertForbidden();
        $this->assertTrue($super->fresh()->hasTwoFactorEnabled());

        $this->actingAs($super)->post("/pengguna/{$schoolAdmin->id}/reset-dua-langkah")->assertRedirect();
        $this->assertFalse($schoolAdmin->fresh()->hasTwoFactorEnabled());
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.two_factor_reset']);
    }

    public function test_perintah_artisan_reset_2fa(): void
    {
        $admin = User::factory()->superadmin()->create(['email' => 'admin@contoh.test']);

        $this->artisan('two-factor:reset', ['email' => 'admin@contoh.test'])->assertSuccessful();
        $this->assertFalse($admin->fresh()->hasTwoFactorEnabled());
        $this->artisan('two-factor:reset', ['email' => 'tidakada@contoh.test'])->assertFailed();
    }
}
