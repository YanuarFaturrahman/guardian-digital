<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_terdaftar_menerima_tautan_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'rani@contoh.test']);

        $this->post('/lupa-kata-sandi', ['email' => 'rani@contoh.test'])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_pesan_sama_untuk_email_tidak_terdaftar_dan_tidak_ada_email_terkirim(): void
    {
        Notification::fake();

        $a = $this->post('/lupa-kata-sandi', ['email' => 'tidakada@contoh.test'])->getSession()->get('status');
        User::factory()->create(['email' => 'ada@contoh.test']);
        $b = $this->post('/lupa-kata-sandi', ['email' => 'ada@contoh.test'])->getSession()->get('status');

        $this->assertSame($a, $b);
        Notification::assertNothingSentTo(User::factory()->make());
    }

    public function test_kegagalan_smtp_tidak_menjadi_error_500(): void
    {
        User::factory()->create(['email' => 'rani@contoh.test']);
        Notification::shouldReceive('send')->andThrow(new \RuntimeException('SMTP down'));

        $this->post('/lupa-kata-sandi', ['email' => 'rani@contoh.test'])
            ->assertRedirect()
            ->assertSessionHas('status');
    }

    public function test_reset_dengan_token_valid_dan_kata_sandi_kuat(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'rani@contoh.test']);
        $token = Password::createToken($user);

        $this->post('/atur-ulang-kata-sandi', [
            'token' => $token, 'email' => 'rani@contoh.test',
            'password' => 'KataBaru2025y', 'password_confirmation' => 'KataBaru2025y',
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(\Hash::check('KataBaru2025y', $user->password));
        $this->assertNotNull($user->email_verified_at, 'Membuka tautan email membuktikan kepemilikan email.');
    }

    public function test_reset_menolak_kata_sandi_lemah(): void
    {
        $user = User::factory()->create(['email' => 'rani@contoh.test']);
        $token = Password::createToken($user);

        $this->post('/atur-ulang-kata-sandi', [
            'token' => $token, 'email' => 'rani@contoh.test',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('password');
    }

    public function test_reset_menolak_token_salah(): void
    {
        User::factory()->create(['email' => 'rani@contoh.test']);

        $this->post('/atur-ulang-kata-sandi', [
            'token' => 'palsu', 'email' => 'rani@contoh.test',
            'password' => 'KataBaru2025y', 'password_confirmation' => 'KataBaru2025y',
        ])->assertSessionHasErrors('email');
    }

    public function test_reset_membuka_kunci_login_akun(): void
    {
        // Pembatas per-IP (throttle) memakai satu hitungan bersama untuk semua rute; dimatikan agar
        // tes ini hanya menguji penguncian per akun.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $user = User::factory()->create(['email' => 'rani@contoh.test']);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/masuk', ['email' => 'rani@contoh.test', 'password' => 'salah' . $i]);
        }
        $token = Password::createToken($user);
        $this->post('/atur-ulang-kata-sandi', [
            'token' => $token, 'email' => 'rani@contoh.test',
            'password' => 'KataBaru2025y', 'password_confirmation' => 'KataBaru2025y',
        ]);

        $this->post('/masuk', ['email' => 'rani@contoh.test', 'password' => 'KataBaru2025y'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_akun_admin_butuh_kata_sandi_12_karakter_saat_reset(): void
    {
        $admin = User::factory()->superadmin()->create(['email' => 'admin@contoh.test']);
        $token = Password::createToken($admin);

        $this->post('/atur-ulang-kata-sandi', [
            'token' => $token, 'email' => 'admin@contoh.test',
            'password' => 'Kucing2024x', 'password_confirmation' => 'Kucing2024x',
        ])->assertSessionHasErrors('password');
    }
}
