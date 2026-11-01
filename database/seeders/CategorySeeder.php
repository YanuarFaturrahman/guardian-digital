<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'penipuan',
                'name' => 'Penipuan Online (Scam)',
                'icon' => '🎣',
                'color' => '#f43f5e',
                'tagline' => 'Tawaran manis, jebakan di baliknya',
                'description' => 'Modus yang memancing kamu mengklik tautan, memasang aplikasi, '
                    . 'atau menyerahkan kode OTP dengan iming-iming hadiah, ancaman, atau '
                    . 'rasa takut ketinggalan.',
                'family_tip' => 'Ajak anak menceritakan pesan mencurigakan yang pernah ia terima. '
                    . 'Sepakati aturan keluarga: tidak ada kode OTP, PIN, atau file aplikasi '
                    . 'yang dibagikan tanpa bertanya dulu.',
            ],
            [
                'slug' => 'investasi',
                'name' => 'Investasi Bodong',
                'icon' => '📈',
                'color' => '#f59e0b',
                'tagline' => 'Untung besar tanpa risiko? Waspada',
                'description' => 'Skema yang menjanjikan keuntungan tinggi dan pasti, sering '
                    . 'memakai sistem ajak teman. Korbannya banyak dari kalangan muda yang '
                    . 'ingin cepat punya uang sendiri.',
                'family_tip' => 'Bicarakan cara kerja uang: keuntungan tinggi selalu datang '
                    . 'bersama risiko tinggi. Latih kebiasaan cek legalitas lewat kanal resmi '
                    . 'sebelum menyetor uang berapa pun.',
            ],
            [
                'slug' => 'hoaks',
                'name' => 'Hoaks dan Manipulasi Informasi',
                'icon' => '📰',
                'color' => '#a78bfa',
                'tagline' => 'Jeda sebelum membagikan',
                'description' => 'Informasi palsu atau menyesatkan yang dirancang memancing '
                    . 'emosi agar disebarkan. Satu pesan terusan bisa membuat banyak orang '
                    . 'panik atau salah mengambil keputusan.',
                'family_tip' => 'Biasakan bertanya "dari mana sumbernya?" saat ada kabar viral '
                    . 'di grup keluarga. Contohkan sendiri cara memeriksa sebelum meneruskan.',
            ],
            [
                'slug' => 'data',
                'name' => 'Kebocoran Data Pribadi',
                'icon' => '🔐',
                'color' => '#22d3ee',
                'tagline' => 'Datamu berharga, jaga baik-baik',
                'description' => 'Data seperti NIK, foto KTP, kode OTP, dan kata sandi dapat '
                    . 'dipakai untuk membobol akun atau mengajukan pinjaman atas namamu.',
                'family_tip' => 'Periksa bersama pengaturan privasi dan verifikasi dua langkah '
                    . 'di akun anak. Jelaskan data apa yang tidak boleh dibagikan ke siapa pun.',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
