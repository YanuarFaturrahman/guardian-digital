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
            DemoUserSeeder::class,
        ]);
    }
}
