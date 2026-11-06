<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        return $override + [
            'role' => 'student', 'name' => 'Rani', 'email' => 'rani@contoh.test',
            'password' => 'Kucing2024x', 'password_confirmation' => 'Kucing2024x',
            'birth_date' => '2000-03-10', 'accept_privacy' => '1',
        ];
    }

    public function test_siswa_dewasa_bisa_mendaftar_dan_persetujuan_tercatat(): void
    {
        $this->post('/daftar', $this->payload())->assertRedirect(route('dashboard'));

        $user = User::where('email', 'rani@contoh.test')->first();
        $this->assertNotNull($user->privacy_accepted_at);
        $this->assertSame(config('privacy.policy_version'), $user->privacy_policy_version);
        $this->assertSame('2000-03-10', $user->birth_date->toDateString());
        $this->assertAuthenticatedAs($user);
    }

    public function test_pendaftaran_menolak_tanpa_persetujuan_privasi(): void
    {
        $this->post('/daftar', $this->payload(['accept_privacy' => null]))->assertSessionHasErrors('accept_privacy');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_siswa_wajib_mengisi_tanggal_lahir(): void
    {
        $this->post('/daftar', $this->payload(['birth_date' => null]))->assertSessionHasErrors('birth_date');
    }

    public function test_tanggal_lahir_masa_depan_atau_terlalu_muda_ditolak(): void
    {
        $this->post('/daftar', $this->payload(['birth_date' => now()->addDay()->toDateString()]))->assertSessionHasErrors('birth_date');
        $this->post('/daftar', $this->payload(['birth_date' => now()->subYears(3)->toDateString()]))->assertSessionHasErrors('birth_date');
    }

    public function test_guru_dan_orang_tua_wajib_menyatakan_dewasa(): void
    {
        $this->post('/daftar', $this->payload(['role' => 'parent', 'birth_date' => null, 'school_id' => null]))
            ->assertSessionHasErrors('confirm_adult');

        $this->post('/daftar', $this->payload(['role' => 'parent', 'birth_date' => null, 'confirm_adult' => '1']))
            ->assertRedirect(route('dashboard'));
    }

    public function test_kata_sandi_lemah_ditolak_saat_daftar(): void
    {
        foreach (['password123', 'pendek1', 'hurufsajasemua'] as $weak) {
            $this->post('/daftar', $this->payload(['password' => $weak, 'password_confirmation' => $weak]))
                ->assertSessionHasErrors('password');
        }
    }

    public function test_peran_admin_tidak_bisa_didaftarkan_sendiri(): void
    {
        $this->post('/daftar', $this->payload(['role' => 'superadmin']))->assertSessionHasErrors('role');
        $this->post('/daftar', $this->payload(['role' => 'school_admin']))->assertSessionHasErrors('role');
    }

    public function test_pendaftar_menerima_email_verifikasi_dan_diblokir_sampai_verifikasi(): void
    {
        $this->useRealMailer();
        Notification::fake();

        $this->post('/daftar', $this->payload(['birth_date' => '1999-01-01']));
        $user = User::where('email', 'rani@contoh.test')->first();

        Notification::assertSentTo($user, VerifyEmail::class);
        $this->assertNull($user->email_verified_at);

        $this->actingAs($user)->get('/siswa')->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get('/verifikasi-email')->assertOk();
    }

    public function test_tautan_verifikasi_membuka_akses(): void
    {
        $this->useRealMailer();
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id, 'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->actingAs($user->fresh())->get('/siswa')->assertOk();
    }

    public function test_tautan_verifikasi_palsu_atau_milik_orang_lain_ditolak(): void
    {
        $this->useRealMailer();
        $user = User::factory()->unverified()->create();
        $other = User::factory()->create();

        // tanpa tanda tangan
        $this->actingAs($user)->get("/verifikasi-email/{$user->id}/" . sha1($user->email))->assertForbidden();

        // tanda tangan valid tetapi untuk akun lain
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $other->id, 'hash' => sha1($other->email),
        ]);
        $this->actingAs($user)->get($url)->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verifikasi_tidak_dituntut_jika_email_tidak_bisa_terkirim(): void
    {
        // MAIL_MAILER=array: jangan mengunci pendaftar baru yang tidak mungkin menerima email.
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/siswa')->assertOk();
    }

    public function test_kirim_ulang_verifikasi(): void
    {
        $this->useRealMailer();
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post('/verifikasi-email/kirim-ulang')->assertSessionHas('status');
        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
