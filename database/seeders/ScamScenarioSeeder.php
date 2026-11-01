<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\BuildsScenarios;
use Illuminate\Database\Seeder;

class ScamScenarioSeeder extends Seeder
{
    use BuildsScenarios;

    public function run(): void
    {
        $this->buildScenario($this->kurirPalsu());
        $this->buildScenario($this->giveawayDiamond());
    }

    private function kurirPalsu(): array
    {
        return [
            'slug' => 'kurir-palsu',
            'category' => 'penipuan',
            'title' => 'Paket Misterius dari "Kurir"',
            'synopsis' => 'Pesan WhatsApp mengaku kurir meminta kamu membuka file foto resi. '
                . 'Waktumu cuma satu jam. Apa yang kamu lakukan?',
            'channel' => 'WhatsApp',
            'difficulty' => 1,
            'xp' => 100,
            'nodes' => [
                'start' => [
                    'start' => true,
                    'speaker' => 'Nomor tidak dikenal (+62 8xx-xxxx-xxxx)',
                    'title' => 'Paket gagal diantar?',
                    'body' => "Kamu baru pulang sekolah dan sedang rebahan. Masuk pesan WhatsApp:\n\n"
                        . "\"Selamat siang kak, kami dari kurir ekspres. Paket kakak GAGAL diantar "
                        . "karena alamat kurang jelas. Cek foto paket dan resi di file "
                        . "Foto_Resi_Paket.apk. Jika tidak konfirmasi dalam 1 jam, paket "
                        . "dikembalikan ke pengirim.\"\n\nKebetulan minggu lalu kamu memang "
                        . "memesan baju secara online.",
                    'choices' => [
                        ['label' => 'Unduh dan buka file-nya, takut paketnya dikembalikan.',
                            'to' => 'trap', 'points' => -15,
                            'feedback' => 'Terburu-buru adalah senjata penipu. File berakhiran .apk adalah aplikasi, bukan foto.'],
                        ['label' => 'Abaikan saja pesannya dan lanjut rebahan.',
                            'to' => 'end_neutral', 'points' => 5,
                            'feedback' => 'Aman untuk dirimu, tetapi kamu belum membuktikan apa-apa dan tidak melindungi orang lain.'],
                        ['label' => 'Jangan buka file. Cek status pesanan lewat aplikasi toko tempat aku belanja.',
                            'to' => 'cek', 'points' => 30,
                            'feedback' => 'Tepat! Verifikasi lewat jalur resmi yang kamu buka sendiri, bukan lewat tautan atau file dari pesan.'],
                    ],
                ],
                'trap' => [
                    'title' => 'File terpasang...',
                    'body' => "File terunduh. HP meminta izin \"Pasang dari sumber tidak dikenal\", lalu "
                        . "aplikasi baru meminta akses SMS, notifikasi, dan Aksesibilitas. Layar "
                        . "sempat berkedip dan baterai terasa cepat panas.",
                    'choices' => [
                        ['label' => 'Izinkan semua agar aplikasinya bisa jalan.',
                            'to' => 'end_bad', 'points' => -15,
                            'feedback' => 'Akses SMS dan Aksesibilitas memungkinkan pelaku membaca kode OTP dan mengendalikan layar.'],
                        ['label' => 'Tolak izin, hapus file, matikan data seluler, ganti kata sandi penting, dan beri tahu orang tua.',
                            'to' => 'end_neutral', 'points' => 10,
                            'feedback' => 'Langkah pemulihan yang bagus. Semakin cepat bertindak, semakin kecil kerugiannya.'],
                    ],
                ],
                'cek' => [
                    'title' => 'Ternyata tidak ada masalah',
                    'body' => "Di aplikasi toko, status pesananmu: \"Sedang dikemas\". Tidak ada catatan "
                        . "gagal kirim. Pesan tadi jelas palsu.\n\nTiba-tiba di grup kelas, Dimas "
                        . "menulis: \"Guys, paket gue juga katanya gagal diantar, mau gue buka "
                        . "filenya nih.\"",
                    'choices' => [
                        ['label' => 'Minta Dimas meneruskan file-nya supaya kamu bisa ikut mengecek.',
                            'to' => 'end_bad', 'points' => -10,
                            'feedback' => 'Meneruskan file berbahaya sama dengan ikut menyebarkan jebakan.'],
                        ['label' => 'Diamkan saja, itu urusan masing-masing.',
                            'to' => 'end_neutral', 'points' => 0,
                            'feedback' => 'Kamu selamat, tetapi temanmu masih dalam bahaya padahal kamu bisa menolong.'],
                        ['label' => 'Peringatkan Dimas dan grup kelas dengan screenshot pesan penipu tadi.',
                            'to' => 'aksi', 'points' => 30,
                            'feedback' => 'Hebat! Guardian melindungi orang lain, bukan hanya dirinya sendiri.'],
                    ],
                ],
                'aksi' => [
                    'title' => 'Dimas selamat, tapi pelaku masih aktif',
                    'body' => "Dimas membalas: \"Hampir aja! Thanks, bro.\" Beberapa menit kemudian, "
                        . "nomor penipu mengirim pesan baru yang makin mendesak dan mulai "
                        . "mengancam. Apa langkah terakhirmu?",
                    'choices' => [
                        ['label' => 'Blokir nomornya saja.',
                            'to' => 'end_neutral', 'points' => 15,
                            'feedback' => 'Memblokir baik, tetapi tanpa laporan korban berikutnya tetap bisa terjebak.'],
                        ['label' => 'Simpan bukti, blokir dan laporkan nomor ke WhatsApp serta kanal pengaduan resmi, lalu cerita ke orang tua.',
                            'to' => 'end_good', 'points' => 40,
                            'feedback' => 'Sempurna: bukti disimpan, pelaku dilaporkan, dan orang tepercaya tahu.'],
                    ],
                ],
                'end_good' => [
                    'kind' => 'good',
                    'title' => 'Misi Berhasil: Kamu Menjadi Guardian',
                    'body' => 'Kamu tidak hanya selamat, tetapi juga menyelamatkan Dimas dan teman-teman '
                        . 'sekelas. Laporanmu membantu pihak berwenang menutup nomor yang dipakai pelaku.',
                    'lesson' => 'Kurir asli tidak mengirim aplikasi (.apk) lewat WhatsApp. Selalu cek '
                        . 'status paket lewat aplikasi atau situs resmi yang kamu buka sendiri.',
                ],
                'end_neutral' => [
                    'kind' => 'neutral',
                    'title' => 'Selamat, tetapi masih ada celah',
                    'body' => 'Kamu terhindar dari kerugian besar, tetapi ada langkah yang terlewat: '
                        . 'memverifikasi lewat jalur resmi, memperingatkan orang lain, atau melapor.',
                    'lesson' => 'Menghindar itu baik. Menjadi Guardian berarti satu langkah lagi: '
                        . 'ceritakan dan laporkan agar orang lain tidak jadi korban.',
                ],
                'end_bad' => [
                    'kind' => 'bad',
                    'title' => 'Perangkatmu Terinfeksi',
                    'body' => 'Aplikasi palsu membaca SMS dan kode OTP-mu. Dalam hitungan menit, '
                        . 'akun dompet digital dan media sosialmu dibobol. (Ini hanya simulasi, '
                        . 'tetapi kejadian nyata seperti ini sering terjadi.)',
                    'lesson' => 'Jangan pernah memasang file .apk dari pesan. Jika terlanjur, '
                        . 'putuskan internet, hapus aplikasi, ganti kata sandi, dan hubungi bank.',
                ],
            ],
            'red_flags' => [
                ['Nomor tidak dikenal mengaku kurir', 'Kurir resmi menghubungi lewat aplikasi atau nomor yang tercatat di pesananmu.'],
                ['File berakhiran .apk', 'File .apk adalah aplikasi Android. Foto resi tidak pernah berbentuk aplikasi.'],
                ['Batas waktu mendesak (1 jam)', 'Urgensi dipakai agar kamu panik dan berhenti berpikir.'],
                ['Meminta pasang aplikasi dari luar toko resmi', 'Aplikasi dari sumber tidak dikenal sering memuat malware pencuri OTP.'],
                ['Resi tidak bisa dicek di aplikasi resmi', 'Paket asli selalu tercatat di aplikasi toko atau situs ekspedisi.'],
            ],
        ];
    }

    private function giveawayDiamond(): array
    {
        return [
            'slug' => 'giveaway-diamond',
            'category' => 'penipuan',
            'title' => 'Giveaway Diamond Gratis',
            'synopsis' => 'DM Instagram menyebut kamu terpilih mendapat 1000 diamond game favorit. '
                . 'Syaratnya: login lewat tautan dan kirim kode verifikasi.',
            'channel' => 'Instagram',
            'difficulty' => 2,
            'xp' => 120,
            'nodes' => [
                'start' => [
                    'start' => true,
                    'speaker' => '@giveaway.d1amond.id',
                    'title' => 'Kamu terpilih!',
                    'body' => "Sebuah akun mengirim DM:\n\n\"SELAMAT! Kamu terpilih mendapat 1000 DIAMOND "
                        . "GRATIS! Login lewat link di bio, lalu kirim KODE VERIFIKASI yang masuk "
                        . "ke email/HP kamu. Hanya 30 menit, setelah itu hangus!\"\n\nAkun itu "
                        . "memakai logo game kesukaanmu.",
                    'choices' => [
                        ['label' => 'Login lewat link di bio dan kirim kode verifikasinya.',
                            'to' => 'trap', 'points' => -15,
                            'feedback' => 'Kode verifikasi (OTP) adalah kunci akunmu. Tidak ada giveaway resmi yang memintanya.'],
                        ['label' => 'Hapus DM-nya tanpa dipikirkan lagi.',
                            'to' => 'end_neutral', 'points' => 5,
                            'feedback' => 'Aman, tetapi kamu melewatkan kesempatan belajar mengenali tanda-tandanya.'],
                        ['label' => 'Periksa profil akun itu dan cari pengumuman giveaway di akun resmi game.',
                            'to' => 'cek', 'points' => 30,
                            'feedback' => 'Bagus! Selalu bandingkan dengan sumber resmi sebelum percaya.'],
                    ],
                ],
                'trap' => [
                    'title' => 'Notifikasi aneh muncul',
                    'body' => "Kamu mengirim kode. Beberapa menit kemudian, email masuk: \"Akun game kamu "
                        . "login dari perangkat baru\". Lalu pelaku meminta kode kedua dengan alasan "
                        . "\"verifikasi tahap akhir\".",
                    'choices' => [
                        ['label' => 'Kirim juga kode kedua supaya diamond-nya cair.',
                            'to' => 'end_bad', 'points' => -15,
                            'feedback' => 'Setiap kode tambahan memperluas akses pelaku ke akunmu.'],
                        ['label' => 'Berhenti membalas, ganti kata sandi, keluarkan semua perangkat, aktifkan verifikasi dua langkah, lapor ke layanan game.',
                            'to' => 'end_neutral', 'points' => 10,
                            'feedback' => 'Pemulihan yang tepat. Kamu menutup pintu sebelum pelaku masuk lebih jauh.'],
                    ],
                ],
                'cek' => [
                    'title' => 'Hasil penyelidikanmu',
                    'body' => "Temuanmu: akun dibuat bulan lalu, pengikutnya sedikit, nama pengguna "
                        . "memakai angka 1 menggantikan huruf i, dan di akun resmi game tidak ada "
                        . "pengumuman giveaway.\n\nLalu kamu melihat story temanmu, Nadia: \"Dapet "
                        . "diamond gratis! Cek link di bio akun itu, buruan!\" Sepertinya akun "
                        . "Nadia dibajak.",
                    'choices' => [
                        ['label' => 'Ikut mengunggah story yang sama supaya dapat bonus referral.',
                            'to' => 'end_bad', 'points' => -10,
                            'feedback' => 'Kamu jadi alat penyebar jebakan yang sama.'],
                        ['label' => 'Cukup screenshot untuk dirimu sendiri.',
                            'to' => 'end_neutral', 'points' => 0,
                            'feedback' => 'Bukti disimpan, tetapi Nadia belum tahu akunnya bermasalah.'],
                        ['label' => 'Hubungi Nadia lewat jalur lain (telepon atau WhatsApp) bahwa akunnya mungkin dibajak.',
                            'to' => 'aksi', 'points' => 30,
                            'feedback' => 'Tepat! Hubungi lewat jalur berbeda karena akun yang dibajak tidak bisa dipercaya.'],
                    ],
                ],
                'aksi' => [
                    'title' => 'Nadia panik tapi berterima kasih',
                    'body' => "Nadia: \"Astaga, tadi aku emang klik link terus kirim kode! Aku ganti "
                        . "password sekarang!\" Akun palsu itu masih aktif dan terus mengirim DM "
                        . "ke teman-teman lain.",
                    'choices' => [
                        ['label' => 'Blokir akun palsunya saja.',
                            'to' => 'end_neutral', 'points' => 15,
                            'feedback' => 'Melindungi dirimu, tetapi akun itu tetap bebas mencari korban baru.'],
                        ['label' => 'Laporkan akun palsu ke Instagram, bantu Nadia mengamankan akun, dan ingatkan teman: tidak ada giveaway resmi yang minta kode OTP.',
                            'to' => 'end_good', 'points' => 40,
                            'feedback' => 'Luar biasa! Pelaporan massal membantu platform menutup akun penipu.'],
                    ],
                ],
                'end_good' => [
                    'kind' => 'good',
                    'title' => 'Misi Berhasil: Pemburu Giveaway Palsu',
                    'body' => 'Kamu mengenali tanda-tanda penipuan, menyelamatkan akun Nadia, dan '
                        . 'membantu menutup akun palsu. Teman-temanmu kini lebih waspada.',
                    'lesson' => 'Kode verifikasi hanya untukmu. Bagikan peringatan lewat jalur yang aman, '
                        . 'bukan lewat akun yang mungkin sudah dibajak.',
                ],
                'end_neutral' => [
                    'kind' => 'neutral',
                    'title' => 'Selamat, tetapi belum tuntas',
                    'body' => 'Akunmu aman, namun masih ada orang di sekitarmu yang berisiko. '
                        . 'Langkah melapor dan memperingatkan akan membuatmu jadi Guardian sejati.',
                    'lesson' => 'Akun yang mendadak mengunggah ajakan aneh biasanya dibajak. '
                        . 'Hubungi pemiliknya lewat jalur lain.',
                ],
                'end_bad' => [
                    'kind' => 'bad',
                    'title' => 'Akunmu Diambil Alih',
                    'body' => 'Pelaku menguasai akun game dan mengubah email pemulihan. Item dan '
                        . 'progresmu hilang, lalu akunmu dipakai menipu temanmu. (Ini hanya simulasi.)',
                    'lesson' => 'Jangan pernah mengirim kode OTP atau kata sandi kepada siapa pun. '
                        . 'Jika terlanjur, segera ganti kata sandi dan hubungi layanan pelanggan.',
                ],
            ],
            'red_flags' => [
                ['Meminta kode verifikasi (OTP)', 'Kode OTP bersifat rahasia. Tidak ada penyelenggara resmi yang memintanya.'],
                ['Batas waktu sangat singkat', 'Waktu mepet dipakai agar kamu tidak sempat berpikir atau bertanya.'],
                ['Nama akun meniru yang asli', 'Perhatikan angka atau huruf yang diganti, misalnya "i" menjadi "1".'],
                ['Tidak ada pengumuman di akun resmi', 'Giveaway asli selalu diumumkan di akun resmi penyelenggara.'],
                ['Teman tiba-tiba mempromosikan hal aneh', 'Akun teman yang dibajak sering dipakai menyebarkan penipuan.'],
            ],
        ];
    }
}
