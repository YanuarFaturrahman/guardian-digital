<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationIdempotentTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrasi_keamanan_bisa_dijalankan_ulang_walau_sebagian_kolom_sudah_ada(): void
    {
        $migration = require database_path('migrations/2026_11_05_120000_add_security_and_privacy_to_users_table.php');

        // Meniru migrasi yang pernah gagal setengah jalan: sebagian kolom hilang, sebagian masih ada.
        Schema::table('users', fn ($t) => $t->dropColumn(['two_factor_secret', 'parental_consent_by']));
        $this->assertTrue(Schema::hasColumn('users', 'birth_date'));

        $migration->up();
        $migration->up(); // dua kali pun tidak masalah

        foreach (['two_factor_secret', 'birth_date', 'parental_consent_by', 'privacy_accepted_at'] as $col) {
            $this->assertTrue(Schema::hasColumn('users', $col), $col);
        }
    }
}
