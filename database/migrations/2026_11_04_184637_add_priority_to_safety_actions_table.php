<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tingkat aksi bawaan: 1 = Darurat, 2 = Penting, 3 = Tambahan. */
    private array $map = [
        'Aktifkan verifikasi dua langkah (2FA)' => 1,
        'Ganti kata sandi email utama' => 1,
        'Periksa privasi media sosial' => 2,
        'Periksa apakah emailmu pernah bocor' => 2,
        'Rapikan izin aplikasi' => 2,
        'Simpan nomor penting' => 2,
        'Ajari satu orang mengenali modus APK dan OTP' => 3,
        'Cek satu kabar viral dengan 3 sumber' => 3,
        'Buat kata sandi darurat keluarga' => 3,
    ];

    private array $xp = [1 => 50, 2 => 35, 3 => 20];

    public function up(): void
    {
        if (! Schema::hasColumn('safety_actions', 'priority')) {
            Schema::table('safety_actions', function (Blueprint $table) {
                // 1 = Darurat, 2 = Penting, 3 = Tambahan
                $table->unsignedTinyInteger('priority')->default(3)->after('xp_reward');
            });
        }

        // Aksi bawaan: tentukan tingkat dan XP sesuai tingkatnya.
        // Aksi buatan admin (judul lain) tetap di tingkat 3 dengan XP-nya sendiri.
        foreach ($this->map as $title => $priority) {
            DB::table('safety_actions')->where('title', $title)->update([
                'priority' => $priority,
                'xp_reward' => $this->xp[$priority],
            ]);
        }

        // Rapikan nomor urut: tingkat 1, lalu 2, lalu 3, mengikuti urutan lama di tiap tingkat.
        $ids = DB::table('safety_actions')
            ->orderBy('priority')->orderBy('position')->orderBy('id')
            ->pluck('id');

        foreach ($ids as $i => $id) {
            DB::table('safety_actions')->where('id', $id)->update(['position' => $i + 1]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('safety_actions', 'priority')) {
            Schema::table('safety_actions', function (Blueprint $table) {
                $table->dropColumn('priority');
            });
        }
    }
};