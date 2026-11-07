<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel untuk fitur keberlangsungan website (menu Sistem):
 *  - health_checks : riwayat pemeriksaan kesehatan (dasar perhitungan uptime)
 *  - error_logs    : error aplikasi yang dikelompokkan per sidik jari (fingerprint)
 *  - announcements : pengumuman / banner pemberitahuan untuk pengguna
 *
 * Aman dijalankan ulang (memeriksa dulu apakah tabel sudah ada).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('health_checks')) {
            Schema::create('health_checks', function (Blueprint $table) {
                $table->id();
                $table->string('status', 10)->index();          // ok | degraded | down
                $table->string('source', 12)->default('scheduler'); // scheduler | manual
                $table->unsignedInteger('db_ms')->nullable();
                $table->unsignedTinyInteger('disk_free_pct')->nullable();
                $table->json('issues')->nullable();
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }

        if (! Schema::hasTable('error_logs')) {
            Schema::create('error_logs', function (Blueprint $table) {
                $table->id();
                $table->string('fingerprint', 40)->unique();
                $table->string('class', 190);
                $table->string('message', 500);
                $table->string('file', 255)->nullable();
                $table->unsignedInteger('line')->nullable();
                $table->text('trace')->nullable();
                $table->string('method', 10)->nullable();
                $table->string('url', 255)->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedInteger('occurrences')->default(1);
                $table->timestamp('first_seen_at')->useCurrent();
                $table->timestamp('last_seen_at')->useCurrent()->index();
                $table->timestamp('resolved_at')->nullable()->index();
            });
        }

        if (! Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
                $table->id();
                $table->string('message', 300);
                $table->string('level', 10)->default('info');       // info | warning | danger
                $table->string('audience', 20)->default('all');     // all | student | parent | teacher | school_admin
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('error_logs');
        Schema::dropIfExists('health_checks');
    }
};
