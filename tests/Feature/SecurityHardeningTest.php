<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\MailStatus;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_akun_tampil_untuk_semua_peran(): void
    {
        $users = [
            User::factory()->create(), User::factory()->parent()->create(), User::factory()->teacher()->create(),
            User::factory()->schoolAdmin()->create(), User::factory()->superadmin()->create(),
        ];

        foreach ($users as $user) {
            $this->actingAs($user)->get('/akun/keamanan')->assertOk()->assertSee('Ganti kata sandi');
            $this->actingAs($user)->get('/akun/privasi')->assertOk();
            $this->actingAs($user)->get('/akun/dua-langkah')->assertOk();
        }
    }

    public function test_halaman_persetujuan_privasi_tampil(): void
    {
        $user = User::factory()->create(['privacy_accepted_at' => null]);
        $this->actingAs($user)->get('/privasi/persetujuan')->assertOk()->assertSee('Setuju dan lanjutkan');
    }

    public function test_halaman_tantangan_2fa_tampil(): void
    {
        $this->post('/masuk', ['email' => ($admin = User::factory()->superadmin()->create(['password' => 'KataSandiAdmin99']))->email, 'password' => 'KataSandiAdmin99']);
        $this->get('/verifikasi-dua-langkah')->assertOk()->assertSee('Verifikasi dua langkah');
    }

    public function test_pendaftaran_menampilkan_persetujuan_dan_tautan_kebijakan(): void
    {
        $this->get('/daftar')->assertOk()->assertSee('Kebijakan Privasi')->assertSee('accept_privacy', false);
    }

    public function test_seeder_production_tidak_membuat_akun_demo_berkata_sandi_lemah(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])->assertExitCode(0);

        $this->assertSame(0, User::count(), 'Tidak boleh ada akun demo di production');
    }

    public function test_seeder_dengan_env_aiven_juga_dianggap_server_asli(): void
    {
        $this->app['env'] = 'aiven'; // php artisan db:seed --env=aiven

        $this->seed(DatabaseSeeder::class);

        $this->assertFalse(User::where('email', 'admin@guardian.test')->exists());
    }

    public function test_seeder_non_production_membuat_akun_demo(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(User::where('email', 'admin@guardian.test')->exists());
    }

    public function test_admin_seeder_membuat_superadmin_dari_env_dan_menolak_sandi_lemah(): void
    {
        putenv('ADMIN_EMAIL=owner@contoh.test');
        putenv('ADMIN_PASSWORD=lemah');
        $this->seed(AdminSeeder::class);
        $this->assertSame(0, User::count());

        putenv('ADMIN_PASSWORD=SandiPemilik2026!');
        $this->seed(AdminSeeder::class);
        $owner = User::where('email', 'owner@contoh.test')->first();
        $this->assertNotNull($owner);
        $this->assertSame('superadmin', $owner->role);
        $this->assertTrue(\Hash::check('SandiPemilik2026!', $owner->password));

        // Aman dijalankan ulang.
        $this->seed(AdminSeeder::class);
        $this->assertSame(1, User::where('role', 'superadmin')->count());

        putenv('ADMIN_EMAIL');
        putenv('ADMIN_PASSWORD');
    }

    public function test_status_email(): void
    {
        config(['mail.default' => 'log']);
        $this->assertFalse(MailStatus::isDeliverable());
        $this->assertFalse(MailStatus::emailVerificationActive());

        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1']);
        $this->assertFalse(MailStatus::isDeliverable());

        $this->useRealMailer();
        $this->assertTrue(MailStatus::isDeliverable());
        $this->assertTrue(MailStatus::emailVerificationActive());

        config(['security.email_verification' => false]);
        $this->assertFalse(MailStatus::emailVerificationActive());
    }

    public function test_perintah_mail_test_menolak_mailer_log(): void
    {
        config(['mail.default' => 'log']);

        $this->artisan('mail:test', ['to' => 'a@b.test'])->assertFailed();
    }

    public function test_perintah_security_check_berjalan_dan_melaporkan(): void
    {
        config(['mail.default' => 'log']);

        $this->artisan('security:check')->expectsOutputToContain('GAGAL')->assertFailed();
    }

    public function test_halaman_sistem_memperingatkan_email_belum_aktif(): void
    {
        config(['mail.default' => 'log']);
        $admin = User::factory()->superadmin()->create();

        $this->actingAs($admin)->get('/admin/sistem')->assertOk()->assertSee('Email benar-benar terkirim');
    }

    public function test_batas_percobaan_kode_tautan_orang_tua(): void
    {
        $parent = User::factory()->parent()->create();

        $statuses = [];
        for ($i = 0; $i < 12; $i++) {
            $statuses[] = $this->actingAs($parent)->post('/orang-tua/hubungkan', ['code' => 'GD-TEBAK' . $i])->getStatusCode();
        }

        $this->assertContains(429, $statuses, 'Menebak kode tautan harus dibatasi');
    }
}
