<?php

namespace Database\Seeders;

use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Database\Seeder;

/**
 * Membuat satu superadmin awal dari variabel lingkungan, tanpa kata sandi bawaan di kode.
 *
 *   ADMIN_EMAIL=nama@domain.com ADMIN_PASSWORD='kata-sandi-kuat-12+' php artisan db:seed --class=AdminSeeder --force
 *
 * Aman dijalankan berulang: tidak membuat akun kedua jika sudah ada superadmin.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('role', 'superadmin')->exists()) {
            $this->command?->info('Superadmin sudah ada, dilewati.');

            return;
        }

        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command?->warn('Belum ada superadmin. Jalankan lagi dengan ADMIN_EMAIL dan ADMIN_PASSWORD terisi.');

            return;
        }

        if ($error = StrongPassword::check($password, 'superadmin', $email)) {
            $this->command?->error('ADMIN_PASSWORD ditolak: ' . $error);

            return;
        }

        User::create([
            'name' => env('ADMIN_NAME', 'Admin Guardian'),
            'email' => $email,
            'password' => $password,
            'role' => 'superadmin',
            'avatar' => '👑',
            'email_verified_at' => now(),
        ]);

        $this->command?->info("Superadmin {$email} dibuat. Verifikasi dua langkah akan diminta saat masuk pertama.");
    }
}
