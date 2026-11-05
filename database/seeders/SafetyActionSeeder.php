<?php

namespace Database\Seeders;

use App\Models\SafetyAction;
use Illuminate\Database\Seeder;

class SafetyActionSeeder extends Seeder
{
    public function run(): void
    {
        // Dikelompokkan per tingkat urgensi: 1 = Darurat, 2 = Penting, 3 = Tambahan.
        // XP otomatis mengikuti tingkat (50 / 35 / 20) dari SafetyAction::PRIORITIES.
        $tiers = [
            1 => [
                ['🔑', 'Aktifkan verifikasi dua langkah (2FA)', 'Aktifkan 2FA di email utama dan satu akun media sosial. Jangan bagikan kodenya kepada siapa pun.'],
                ['🧩', 'Ganti kata sandi email utama', 'Buat kalimat sandi panjang dan unik untuk email, karena email adalah kunci pemulihan semua akun.'],
            ],
            2 => [
                ['🔒', 'Periksa privasi media sosial', 'Atur siapa yang bisa melihat postingan, mengirim pesan, dan menandai kamu.'],
                ['🕵️', 'Periksa apakah emailmu pernah bocor', 'Gunakan layanan pengecekan kebocoran data tepercaya, lalu ganti kata sandi bila ditemukan.'],
                ['🧹', 'Rapikan izin aplikasi', 'Hapus aplikasi yang tidak dipakai dan tinjau izin lokasi, kontak, dan SMS.'],
                ['📞', 'Simpan nomor penting', 'Simpan Kontak OJK 157 dan nomor orang tua atau wali yang bisa dihubungi saat ada hal janggal.'],
            ],
            3 => [
                ['📣', 'Ajari satu orang mengenali modus APK dan OTP', 'Ceritakan modus kurir palsu atau giveaway palsu kepada keluarga atau teman.'],
                ['🔎', 'Cek satu kabar viral dengan 3 sumber', 'Bandingkan sumber resmi, media tepercaya, dan situs cek fakta sebelum membagikan.'],
                ['🗝️', 'Buat kata sandi darurat keluarga', 'Sepakati kata rahasia keluarga untuk memastikan telepon atau pesan darurat itu asli.'],
            ],
        ];

        $position = 1;

        foreach ($tiers as $priority => $items) {
            foreach ($items as [$icon, $title, $description]) {
                SafetyAction::updateOrCreate(['title' => $title], [
                    'icon' => $icon,
                    'description' => $description,
                    'priority' => $priority,
                    'xp_reward' => SafetyAction::PRIORITIES[$priority]['xp'],
                    'position' => $position++,
                ]);
            }
        }
    }
}