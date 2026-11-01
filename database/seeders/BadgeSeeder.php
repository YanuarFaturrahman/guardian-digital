<?php

namespace Database\Seeders;

use App\Models\Badge;
use App\Models\Category;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        // [slug, nama, deskripsi, ikon, type, threshold, kategori]
        $badges = [
            ['misi-pertama', 'Misi Pertama', 'Menyelesaikan 1 misi.', '🎯', 'scenarios', 1, null],
            ['detektif', 'Detektif Pemula', 'Menyelesaikan 3 misi berbeda.', '🔍', 'scenarios', 3, null],
            ['penjaga-lengkap', 'Penjaga Lengkap', 'Menyelesaikan semua 5 misi.', '🛡️', 'scenarios', 5, null],
            ['skor-sempurna', 'Skor Sempurna', 'Mendapat skor 100 pada 1 misi.', '💯', 'perfect', 1, null],
            ['tanpa-cela', 'Tanpa Cela', 'Skor 100 pada 3 misi berbeda.', '🏆', 'perfect', 3, null],
            ['bijak', 'Pengambil Keputusan Bijak', 'Meraih akhir cerita terbaik 3 kali.', '🧠', 'good_end', 3, null],
            ['pemburu-scam', 'Pemburu Scam', 'Menyelesaikan 2 misi Penipuan Online.', '🎣', 'category', 2, 'penipuan'],
            ['anti-bodong', 'Anti Bodong', 'Menyelesaikan misi Investasi Bodong.', '📈', 'category', 1, 'investasi'],
            ['pemutus-hoaks', 'Pemutus Hoaks', 'Menyelesaikan misi Hoaks.', '📰', 'category', 1, 'hoaks'],
            ['penjaga-data', 'Penjaga Data', 'Menyelesaikan misi Kebocoran Data.', '🔐', 'category', 1, 'data'],
            ['kutu-buku', 'Kutu Buku Digital', 'Membaca 4 materi.', '📚', 'lessons', 4, null],
            ['pelaku-aksi', 'Pelaku Aksi', 'Menyelesaikan 3 aksi keamanan.', '⚡', 'actions', 3, null],
            ['xp-500', 'Level Penjaga', 'Mengumpulkan 500 XP.', '🥈', 'xp', 500, null],
            ['xp-1000', 'Guardian Sejati', 'Mengumpulkan 1000 XP.', '🥇', 'xp', 1000, null],
        ];

        foreach ($badges as [$slug, $name, $desc, $icon, $type, $threshold, $cat]) {
            Badge::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'description' => $desc,
                'icon' => $icon,
                'type' => $type,
                'threshold' => $threshold,
                'category_id' => $cat ? Category::where('slug', $cat)->value('id') : null,
            ]);
        }
    }
}
