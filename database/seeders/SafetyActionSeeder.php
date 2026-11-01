<?php

namespace Database\Seeders;

use App\Models\SafetyAction;
use Illuminate\Database\Seeder;

class SafetyActionSeeder extends Seeder
{
    public function run(): void
    {
        $actions = [
            ['🔑', 'Aktifkan verifikasi dua langkah (2FA)', 'Aktifkan 2FA di email utama dan satu akun media sosial. Jangan bagikan kodenya kepada siapa pun.', 40],
            ['🧩', 'Ganti kata sandi email utama', 'Buat kalimat sandi panjang dan unik untuk email, karena email adalah kunci pemulihan semua akun.', 30],
            ['🔒', 'Periksa privasi media sosial', 'Atur siapa yang bisa melihat postingan, mengirim pesan, dan menandai kamu.', 25],
            ['🕵️', 'Periksa apakah emailmu pernah bocor', 'Gunakan layanan pengecekan kebocoran data tepercaya, lalu ganti kata sandi bila ditemukan.', 25],
            ['📞', 'Simpan nomor penting', 'Simpan Kontak OJK 157 dan nomor orang tua atau wali yang bisa dihubungi saat ada hal janggal.', 25],
            ['📣', 'Ajari satu orang mengenali modus APK dan OTP', 'Ceritakan modus kurir palsu atau giveaway palsu kepada keluarga atau teman.', 50],
            ['🔎', 'Cek satu kabar viral dengan 3 sumber', 'Bandingkan sumber resmi, media tepercaya, dan situs cek fakta sebelum membagikan.', 30],
            ['🧹', 'Rapikan izin aplikasi', 'Hapus aplikasi yang tidak dipakai dan tinjau izin lokasi, kontak, dan SMS.', 30],
            ['🗝️', 'Buat kata sandi darurat keluarga', 'Sepakati kata rahasia keluarga untuk memastikan telepon atau pesan darurat itu asli.', 40],
        ];

        foreach ($actions as $i => [$icon, $title, $description, $xp]) {
            SafetyAction::updateOrCreate(['title' => $title], [
                'icon' => $icon,
                'description' => $description,
                'xp_reward' => $xp,
                'position' => $i,
            ]);
        }
    }
}
