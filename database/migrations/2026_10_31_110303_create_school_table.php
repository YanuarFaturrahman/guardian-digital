<?php

use App\Models\School;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('match_key')->unique();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('role')
                ->constrained('schools')->nullOnDelete();
        });

        // Orang tua tidak punya sekolah lagi
        DB::table('users')->where('role', 'parent')->update(['school' => null]);

        // Pindahkan nama sekolah lama ke tabel schools (ejaan mirip otomatis digabung)
        $rows = DB::table('users')->whereNotNull('school')->where('school', '!=', '')
            ->get(['id', 'school']);

        foreach ($rows as $row) {
            $school = School::findOrCreateByName($row->school);
            DB::table('users')->where('id', $row->id)->update(['school_id' => $school->id]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('school');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('school')->nullable()->after('role');
        });

        foreach (DB::table('schools')->get() as $school) {
            DB::table('users')->where('school_id', $school->id)->update(['school' => $school->name]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
        });

        Schema::dropIfExists('schools');
    }
};