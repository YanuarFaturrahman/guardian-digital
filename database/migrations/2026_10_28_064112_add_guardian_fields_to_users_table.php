<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // student, parent, teacher, admin
            $table->string('role', 20)->default('student')->after('email');
            $table->string('school')->nullable()->after('role');
            $table->string('avatar', 16)->default('🛡️')->after('school');
            // kode yang dibagikan siswa ke orang tua (hanya untuk siswa)
            $table->string('link_code', 12)->nullable()->unique()->after('avatar');
            $table->unsignedInteger('xp')->default(0)->after('link_code');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropUnique(['link_code']);
            $table->dropColumn(['role', 'school', 'avatar', 'link_code', 'xp']);
        });
    }
};
