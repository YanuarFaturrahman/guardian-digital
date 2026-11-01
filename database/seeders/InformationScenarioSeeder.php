<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\BuildsScenarios;
use Illuminate\Database\Seeder;

class InformationScenarioSeeder extends Seeder
{
    use BuildsScenarios;

    public function run(): void
    {
        $this->buildScenario($this->broadcastHeboh());
        $this->buildScenario($this->formulirBeasiswa());
    }

    private function broadcastHeboh(): array
    {
        return [
            'slug' => 'broadcast-heboh',
            'category' => 'hoaks',
            'title' => 'Broadcast Heboh di Grup Kelas',
            'synopsis' => 'Sebuah "berita" menakutkan tentang minuman populer beredar di grup '
                . 'kelasmu dengan perintah: sebarkan sebelum dihapus!',
            'channel' => 'Grup WhatsApp',
            'difficulty' => 1,
            'xp' => 100,
            'nodes' => [
                'start' => [
                    'start' => true,
                    'speaker' => 'Grup Kelas XI IPS 2',
                    'title' => 'Pesan terusan yang menakutkan',
                    'body' => "Temanmu meneruskan tangkapan layar:\n\n\"BAHAYA!!! Minuman bersoda "
                        . "rasa jeruk terbukti sebabkan GAGAL GINJAL dalam 3 hari! Kata dokter di "
                        . "rumah sakit besar. SEBARKAN sebelum dihapus pemerintah!\"\n\nAda foto "
                        . "orang di ranjang rumah sakit. Notifikasi grup terus berdatangan.",
                    'choices' => [
                        ['label' => 'Langsung teruskan ke 5 grup lain, siapa tahu menyelamatkan orang.',
                            'to' => 'trap', 'points' => -15,
                            'feedback' => 'Niat baik tidak cukup. Pesan yang memancing takut sering sengaja dibuat agar disebar.'],
                        ['label' => 'Tidak dibalas dan tidak diteruskan.',
                            'to' => 'end_neutral', 'points' => 5,
                            'feedback' => 'Tidak ikut menyebarkan itu baik, tetapi kamu belum membantu meluruskan.'],
                        ['label' => 'Jeda dulu. Cari sumber asli, tanggal, dan apakah media tepercaya memberitakannya.',
                            'to' => 'cek', 'points' => 30,
                            'feedback' => 'Jeda adalah kekuatan super melawan hoaks.'],
                    ],
                ],
                'trap' => [
                    'title' => 'Satu jam kemudian...',
                    'body' => "Pesan menyebar ke banyak grup. Guru BK menegur karena banyak orang tua "
                        . "panik, dan ibu kantin sedih karena dagangannya mendadak sepi.",
                    'choices' => [
                        ['label' => 'Bilang "yang penting niatnya baik" dan jangan minta maaf.',
                            'to' => 'end_bad', 'points' => -15,
                            'feedback' => 'Mengakui kesalahan lebih kuat daripada membela diri.'],
                        ['label' => 'Hapus pesan, minta maaf di grup, dan kirim klarifikasi dengan sumber yang benar.',
                            'to' => 'end_neutral', 'points' => 10,
                            'feedback' => 'Koreksi cepat membatasi kerusakan. Ini sikap dewasa.'],
                    ],
                ],
                'cek' => [
                    'title' => 'Hasil pengecekanmu',
                    'body' => "Temuanmu: tidak ada nama dokter atau rumah sakit yang jelas, tidak ada "
                        . "tanggal, fotonya ternyata berasal dari artikel lain (terlihat lewat "
                        . "pencarian gambar terbalik), dan situs cek fakta memuat klarifikasi serupa.",
                    'choices' => [
                        ['label' => 'Ejek pengirimnya di grup supaya kapok.',
                            'to' => 'end_bad', 'points' => -10,
                            'feedback' => 'Mempermalukan orang membuat orang takut bertanya dan justru tidak mengubah kebiasaan.'],
                        ['label' => 'Simpan hasilnya sendiri.',
                            'to' => 'end_neutral', 'points' => 0,
                            'feedback' => 'Kamu tahu kebenarannya, tetapi grup masih termakan hoaks.'],
                        ['label' => 'Balas ramah di grup dengan hasil pengecekan dan tautan sumber yang baik.',
                            'to' => 'aksi', 'points' => 30,
                            'feedback' => 'Tepat! Koreksi dengan ramah dan bukti lebih efektif daripada menyalahkan.'],
                    ],
                ],
                'aksi' => [
                    'title' => 'Pengirim minta maaf',
                    'body' => "Pengirim menulis: \"Maaf ya, aku nggak ngecek dulu.\" Tetapi pesan itu "
                        . "sudah terlanjur menyebar ke grup lain yang tidak kamu ikuti.",
                    'choices' => [
                        ['label' => 'Cukup di grupmu saja yang diluruskan.',
                            'to' => 'end_neutral', 'points' => 15,
                            'feedback' => 'Grupmu aman, tetapi hoaksnya masih beredar di tempat lain.'],
                        ['label' => 'Bantu klarifikasi di grup lain, laporkan konten ke platform, dan ajak teman memakai cara cek fakta (sumber, tanggal, gambar).',
                            'to' => 'end_good', 'points' => 40,
                            'feedback' => 'Luar biasa! Kamu mengubah satu hoaks menjadi pelajaran bersama.'],
                    ],
                ],
                'end_good' => [
                    'kind' => 'good',
                    'title' => 'Misi Berhasil: Pemutus Rantai Hoaks',
                    'body' => 'Hoaks berhenti menyebar dan teman-temanmu belajar cara mengeceknya. '
                        . 'Suasana grup tetap hangat karena kamu meluruskan tanpa menghakimi.',
                    'lesson' => 'Hoaks memanfaatkan emosi. Jeda, cek sumber, tanggal, dan gambar '
                        . 'sebelum membagikan.',
                ],
                'end_neutral' => [
                    'kind' => 'neutral',
                    'title' => 'Kamu selamat, hoaks masih beredar',
                    'body' => 'Kamu tidak menyebarkan hoaks, namun belum membantu meluruskan. '
                        . 'Satu klarifikasi ramah bisa menyelamatkan banyak orang dari kepanikan.',
                    'lesson' => 'Tidak ikut menyebarkan itu baik; meluruskan dengan sopan dan '
                        . 'bukti membuatmu menjadi Guardian informasi.',
                ],
                'end_bad' => [
                    'kind' => 'bad',
                    'title' => 'Hoaks Menyebar, Suasana Memanas',
                    'body' => 'Kepanikan meluas, dagangan sekolah rugi, dan hubungan di grup memburuk. '
                        . '(Ini hanya simulasi, tetapi dampaknya sering nyata.)',
                    'lesson' => 'Meneruskan tanpa mengecek dan menyalahkan orang lain sama-sama merusak. '
                        . 'Pilih jeda, cek, lalu luruskan dengan ramah.',
                ],
            ],
            'red_flags' => [
                ['Judul dan kata kapital yang menakut-nakuti', 'Hoaks dirancang memancing emosi supaya kamu langsung menyebarkannya.'],
                ['Perintah "sebarkan sebelum dihapus"', 'Informasi benar tidak perlu memaksa kamu menyebar.'],
                ['Sumber tidak jelas ("kata dokter")', 'Tidak ada nama, instansi, atau tautan yang bisa dicek.'],
                ['Tidak ada tanggal', 'Hoaks lama sering didaur ulang dan diedarkan lagi.'],
                ['Foto diambil dari konteks lain', 'Pencarian gambar terbalik dapat menunjukkan asal foto sebenarnya.'],
            ],
        ];
    }

    private function formulirBeasiswa(): array
    {
        return [
            'slug' => 'formulir-beasiswa',
            'category' => 'data',
            'title' => 'Formulir Beasiswa Kilat',
            'synopsis' => 'Sebuah akun menawarkan beasiswa dan meminta foto KTP, selfie dengan KTP, '
                . 'dan kode OTP lewat formulir online. Data apa yang pantas dibagikan?',
            'channel' => 'Instagram dan Formulir Online',
            'difficulty' => 2,
            'xp' => 150,
            'nodes' => [
                'start' => [
                    'start' => true,
                    'speaker' => '@info.beasiswa.sma2026',
                    'title' => 'Beasiswa kilat',
                    'body' => "Sebuah akun mengirim tautan:\n\n\"SELAMAT! Kamu lolos seleksi awal beasiswa. "
                        . "Isi formulir ini dalam 2 jam: foto KTP/KK, NIK, selfie sambil memegang KTP, "
                        . "nomor rekening, dan KODE OTP yang kami kirim untuk verifikasi.\"\n\nKamu "
                        . "memang sedang mencari beasiswa.",
                    'choices' => [
                        ['label' => 'Isi semua, termasuk foto KTP orang tua, biar cepat selesai.',
                            'to' => 'trap', 'points' => -15,
                            'feedback' => 'Paket foto KTP, NIK, selfie KTP, dan OTP adalah paket lengkap untuk pelaku membuka akun atau pinjaman atas namamu.'],
                        ['label' => 'Tutup halaman dan lupakan saja.',
                            'to' => 'end_neutral', 'points' => 5,
                            'feedback' => 'Datamu aman, tetapi kamu belum tahu apakah ada beasiswa asli yang bisa kamu ikuti.'],
                        ['label' => 'Cek apakah beasiswa ada di situs resmi penyelenggara atau sekolah, dan data apa yang wajar diminta.',
                            'to' => 'cek', 'points' => 30,
                            'feedback' => 'Bagus! Cari informasi dari kanal resmi yang kamu temukan sendiri.'],
                    ],
                ],
                'trap' => [
                    'title' => 'Beberapa hari kemudian',
                    'body' => "Telepon masuk dari orang yang mengaku petugas bank: \"NIK Anda dipakai "
                        . "mengajukan pinjaman online. Untuk membatalkan, sebutkan kode OTP yang "
                        . "baru masuk.\" Pada saat yang sama muncul SMS penagihan dari aplikasi "
                        . "pinjaman yang tidak pernah kamu pasang.",
                    'choices' => [
                        ['label' => 'Sebutkan kode OTP agar pinjaman dibatalkan.',
                            'to' => 'end_bad', 'points' => -15,
                            'feedback' => 'Petugas bank asli tidak pernah meminta OTP. Ini penipuan lanjutan.'],
                        ['label' => 'Tutup telepon, hubungi bank lewat nomor resmi di kartu atau aplikasi, cek penyalahgunaan NIK, dan beri tahu orang tua.',
                            'to' => 'end_neutral', 'points' => 10,
                            'feedback' => 'Menghubungi lewat nomor resmi yang kamu cari sendiri adalah langkah yang tepat.'],
                    ],
                ],
                'cek' => [
                    'title' => 'Hasil pengecekanmu',
                    'body' => "Temuanmu: formulir bukan dari domain sekolah atau penyelenggara, tidak ada "
                        . "kontak panitia, dan permintaan OTP tidak pernah wajar. Di situs resmi ada "
                        . "beasiswa asli, tetapi pendaftarannya lewat portal resmi dan hanya "
                        . "meminta data akademik.",
                    'choices' => [
                        ['label' => 'Isi formulir dengan data asli, tetapi tidak mengirim OTP.',
                            'to' => 'end_bad', 'points' => -10,
                            'feedback' => 'Data asli yang sudah terkirim tetap bisa disalahgunakan, OTP atau tidak.'],
                        ['label' => 'Tutup saja tanpa melakukan apa-apa.',
                            'to' => 'end_neutral', 'points' => 0,
                            'feedback' => 'Aman, tetapi beasiswa asli belum kamu manfaatkan.'],
                        ['label' => 'Daftar lewat portal resmi dengan data seperlunya, tanpa NIK dan OTP di formulir pihak lain, dan tanya wali kelas lebih dulu.',
                            'to' => 'aksi', 'points' => 30,
                            'feedback' => 'Tepat! Bagikan data seminimal mungkin dan hanya lewat kanal resmi.'],
                    ],
                ],
                'aksi' => [
                    'title' => 'Teman-temanmu terlanjur mengisi',
                    'body' => "Wali kelas membenarkan bahwa beasiswa asli ada lewat portal sekolah. "
                        . "Kamu juga tahu beberapa temanmu sudah mengisi formulir palsu itu.",
                    'choices' => [
                        ['label' => 'Cukup lapor ke wali kelas.',
                            'to' => 'end_neutral', 'points' => 15,
                            'feedback' => 'Baik, tetapi temanmu juga perlu tahu langkah pengamanan akun mereka.'],
                        ['label' => 'Beri tahu teman yang sudah mengisi: ganti kata sandi, waspada telepon dan OTP, laporkan akun palsu, dan lapor wali kelas atau orang tua.',
                            'to' => 'end_good', 'points' => 40,
                            'feedback' => 'Luar biasa! Kamu mengurangi dampak kebocoran data bagi banyak orang.'],
                    ],
                ],
                'end_good' => [
                    'kind' => 'good',
                    'title' => 'Misi Berhasil: Penjaga Data',
                    'body' => 'Datamu aman dan kamu membantu teman-teman mengamankan data mereka. '
                        . 'Kamu juga mendapat peluang beasiswa asli lewat jalur resmi.',
                    'lesson' => 'Bagikan data seminimal mungkin dan hanya lewat kanal resmi. OTP, '
                        . 'PIN, dan kata sandi tidak boleh dibagikan kepada siapa pun.',
                ],
                'end_neutral' => [
                    'kind' => 'neutral',
                    'title' => 'Aman, tetapi belum maksimal',
                    'body' => 'Kamu terhindar dari kerugian besar. Ada peluang untuk membantu orang '
                        . 'lain mengamankan datanya.',
                    'lesson' => 'Setelah menyelamatkan diri, bantu orang sekitarmu dan laporkan akun palsu.',
                ],
                'end_bad' => [
                    'kind' => 'bad',
                    'title' => 'Identitasmu Disalahgunakan',
                    'body' => 'Data dan OTP dipakai untuk mengajukan pinjaman dan mengambil alih akun. '
                        . 'Pemulihan butuh waktu dan bantuan banyak pihak. (Ini hanya simulasi.)',
                    'lesson' => 'Data pribadi yang bocor sulit ditarik kembali. Cegah dengan tidak '
                        . 'mengirimnya ke pihak yang tidak terverifikasi.',
                ],
            ],
            'red_flags' => [
                ['Meminta foto KTP, selfie KTP, dan NIK sekaligus', 'Kombinasi ini cukup untuk membuka akun atau pinjaman atas namamu.'],
                ['Meminta kode OTP', 'OTP tidak pernah boleh dibagikan, termasuk kepada "petugas".'],
                ['Batas waktu 2 jam', 'Urgensi buatan mencegahmu memverifikasi.'],
                ['Formulir bukan dari domain resmi', 'Periksa alamat situs. Penyelenggara asli memakai kanal resminya.'],
                ['Menghubungi lebih dulu dengan kabar "lolos"', 'Kamu tidak mendaftar, tetapi tiba-tiba lolos; itu janggal.'],
            ],
        ];
    }
}
