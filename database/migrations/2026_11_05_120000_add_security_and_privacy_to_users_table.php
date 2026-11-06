<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aman dijalankan ulang: hanya menambah kolom yang belum ada. MySQL tidak bisa membatalkan
     * ALTER TABLE yang gagal di tengah, jadi migrasi yang pernah gagal setengah jalan tetap bisa dilanjutkan.
     */
    public function up(): void
    {
        $columns = [
            // Verifikasi dua langkah (TOTP). Rahasia dan kode pemulihan disimpan terenkripsi.
            'two_factor_secret' => fn (Blueprint $t) => $t->text('two_factor_secret')->nullable(),
            'two_factor_recovery_codes' => fn (Blueprint $t) => $t->text('two_factor_recovery_codes')->nullable(),
            'two_factor_confirmed_at' => fn (Blueprint $t) => $t->timestamp('two_factor_confirmed_at')->nullable(),
            'two_factor_last_step' => fn (Blueprint $t) => $t->unsignedBigInteger('two_factor_last_step')->nullable(),

            // Privasi dan persetujuan (UU PDP: data anak butuh persetujuan orang tua/wali).
            'birth_date' => fn (Blueprint $t) => $t->date('birth_date')->nullable(),
            'privacy_accepted_at' => fn (Blueprint $t) => $t->timestamp('privacy_accepted_at')->nullable(),
            'privacy_policy_version' => fn (Blueprint $t) => $t->string('privacy_policy_version', 20)->nullable(),
            'parental_consent_at' => fn (Blueprint $t) => $t->timestamp('parental_consent_at')->nullable(),
            'parental_consent_by' => fn (Blueprint $t) => $t->unsignedBigInteger('parental_consent_by')->nullable(),
        ];

        foreach ($columns as $name => $define) {
            if (! Schema::hasColumn('users', $name)) {
                Schema::table('users', fn (Blueprint $table) => $define($table));
            }
        }

        // Akun yang sudah ada dianggap terverifikasi agar tidak terkunci setelah pembaruan.
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        $drop = array_filter([
            'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
            'two_factor_last_step', 'birth_date', 'privacy_accepted_at',
            'privacy_policy_version', 'parental_consent_at', 'parental_consent_by',
        ], fn ($c) => Schema::hasColumn('users', $c));

        if ($drop) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn($drop));
        }
    }
};
