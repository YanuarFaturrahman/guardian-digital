<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            ScamScenarioSeeder::class,
            InvestmentScenarioSeeder::class,
            InformationScenarioSeeder::class,
            LessonSeeder::class,
            SafetyActionSeeder::class,
            BadgeSeeder::class,
        ]);

        // Akun demo memakai kata sandi "password": HANYA untuk lingkungan lokal/tes (atau SEED_DEMO=true
        // yang disengaja). Nama lingkungan lain, termasuk "aiven" dari --env=aiven, dianggap server asli.
        // Di server asli, buat superadmin lewat AdminSeeder (ADMIN_EMAIL dan ADMIN_PASSWORD).
        if (app()->environment('local', 'testing') || filter_var(env('SEED_DEMO'), FILTER_VALIDATE_BOOLEAN)) {
            $this->call(DemoUserSeeder::class);
        } else {
            $this->call(AdminSeeder::class);
        }
    }
}
