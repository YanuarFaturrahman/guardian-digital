<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('birth_date')->nullable();
            $table->string('parent_email', 120)->nullable();
        });

        // Catatan persetujuan: bukti siapa menyetujui apa, kapan, dan untuk versi kebijakan yang mana.
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20); // privacy = pemilik akun, parental = orang tua / wali
            $table->string('policy_version', 20);
            $table->string('given_by_name')->nullable();
            $table->string('given_by_email')->nullable();
            $table->foreignId('given_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['birth_date', 'parent_email']);
        });
    }
};
