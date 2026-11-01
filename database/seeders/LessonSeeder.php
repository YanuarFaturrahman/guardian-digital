<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    public function run(): void
    {
        $lessons = [
            ['penipuan', 'anatomi-penipuan', 'Anatomi Penipuan: Urgensi, Hadiah, dan Otoritas Palsu',
                'Tiga pemicu psikologis yang dipakai hampir semua penipu.', 3,
                "Penipu tidak membobol kecerdasanmu; mereka memanfaatkan **emosi**.\n\n"
                . "## Tiga pemicu utama\n\n"
                . "- **Urgensi**: \"Hanya 1 jam!\" Waktu mepet membuatmu tidak sempat berpikir.\n"
                . "- **Hadiah atau keuntungan**: \"Kamu terpilih!\" Rasa senang menumpulkan kewaspadaan.\n"
                . "- **Otoritas palsu**: mengaku kurir, bank, polisi, atau admin agar kamu patuh.\n\n"
                . "## Yang harus dilakukan\n\n"
                . "Begitu kamu merasa ditekan atau terlalu senang, **berhenti sejenak**. Hubungi pihak yang "
                . "bersangkutan lewat nomor atau aplikasi resmi yang kamu cari sendiri, bukan lewat "
                . "kontak yang diberikan pesan itu.\n\n"
                . "Ingat: korban penipuan bukan orang bodoh. Siapa pun bisa terjebak saat sedang lelah, "
                . "terburu-buru, atau sangat berharap."],
            ['penipuan', 'otp-apk-tautan', 'OTP, APK, dan Tautan: Tiga Pintu Masuk Penipu',
                'Tiga hal yang tidak boleh kamu berikan atau buka sembarangan.', 3,
                "## 1. Kode OTP\n\n"
                . "OTP adalah kunci sekali pakai untuk akunmu. **Tidak ada pihak resmi** yang meminta OTP "
                . "lewat chat atau telepon.\n\n"
                . "## 2. File APK\n\n"
                . "File berakhiran **.apk** adalah aplikasi Android. Pasang aplikasi hanya dari toko "
                . "aplikasi resmi. Aplikasi dari pesan bisa membaca SMS dan mengendalikan layarmu.\n\n"
                . "## 3. Tautan mencurigakan\n\n"
                . "- Periksa alamat situs dengan teliti (huruf diganti angka, domain aneh).\n"
                . "- Jangan klik tautan dari nomor tak dikenal; ketik alamat resmi sendiri.\n"
                . "- Tautan pendek bisa menyembunyikan tujuan sebenarnya.\n\n"
                . "Jika terlanjur: putuskan internet, hapus aplikasi, ganti kata sandi, dan hubungi bank "
                . "lewat nomor resmi."],
            ['investasi', 'ponzi-piramida', 'Untung Besar Tanpa Risiko? Kenali Skema Ponzi dan Piramida',
                'Cara kerja investasi bodong dan mengapa selalu runtuh.', 4,
                "Dalam **skema Ponzi**, \"keuntungan\" anggota lama dibayar dari setoran anggota baru, "
                . "bukan dari usaha nyata. Dalam **skema piramida**, penghasilan datang dari merekrut orang.\n\n"
                . "## Mengapa pasti runtuh\n\n"
                . "Jumlah anggota baru yang dibutuhkan terus membesar. Ketika rekrutmen melambat, uang "
                . "habis dan penyelenggara menghilang.\n\n"
                . "## Ciri-ciri\n\n"
                . "- Janji untung tinggi dan pasti dalam waktu singkat.\n"
                . "- Bonus untuk mengajak teman.\n"
                . "- Tidak jelas usaha atau produknya.\n"
                . "- Tekanan untuk top up agar naik level.\n\n"
                . "Ingat: **semakin tinggi imbal hasil, semakin tinggi risikonya**. Tidak ada investasi "
                . "sah yang menjamin untung besar tanpa risiko."],
            ['investasi', 'cek-2l', 'Cek 2L: Legal dan Logis',
                'Dua pertanyaan sederhana sebelum menyetor uang.', 3,
                "Sebelum menaruh uang di mana pun, tanyakan dua hal:\n\n"
                . "## Legal?\n\n"
                . "Apakah perusahaan dan produknya berizin dan diawasi lembaga resmi seperti OJK? "
                . "Kamu dapat bertanya lewat **Kontak OJK 157** atau memeriksa kanal resmi OJK dan "
                . "Satgas PASTI.\n\n"
                . "## Logis?\n\n"
                . "Apakah keuntungan yang dijanjikan masuk akal? Bunga atau imbal hasil yang jauh "
                . "melebihi pasar adalah tanda bahaya.\n\n"
                . "## Kebiasaan baik\n\n"
                . "- Diskusikan dengan orang tua atau guru sebelum menyetor.\n"
                . "- Jangan gunakan uang yang tidak siap hilang.\n"
                . "- Jika ragu, jangan."],
            ['hoaks', 'cara-kerja-hoaks', 'Cara Kerja Hoaks dan Mengapa Kita Terpancing',
                'Hoaks bekerja lewat emosi, bukan fakta.', 3,
                "Hoaks efektif karena menyentuh **emosi kuat**: takut, marah, atau kagum. Saat emosi naik, "
                . "kita cenderung langsung membagikan.\n\n"
                . "## Jebakan pikiran\n\n"
                . "- **Bias konfirmasi**: kita mudah percaya yang sesuai pendapat kita.\n"
                . "- **Efek ikut-ikutan**: \"semua orang membagikan, pasti benar\".\n"
                . "- **Rasa ingin menolong**: niat baik yang dimanfaatkan.\n\n"
                . "## Kenali jenisnya\n\n"
                . "Konten menyesatkan, gambar atau video lama yang dipakai ulang, tangkapan layar "
                . "berita palsu, hingga konten buatan AI.\n\n"
                . "Kuncinya: **jeda sebelum membagikan**."],
            ['hoaks', 'cek-fakta-4-langkah', 'Jurus Cek Fakta 4 Langkah',
                'Cara cepat memeriksa kabar viral dalam beberapa menit.', 4,
                "## 1. Sumber\n\n"
                . "Siapa yang menulis? Apakah ada nama, instansi, atau tautan yang bisa dicek?\n\n"
                . "## 2. Tanggal dan konteks\n\n"
                . "Apakah informasi ini baru atau lama? Apakah terjadi di tempat yang disebut?\n\n"
                . "## 3. Gambar dan video\n\n"
                . "Gunakan pencarian gambar terbalik untuk melihat asal foto.\n\n"
                . "## 4. Bandingkan\n\n"
                . "Cari kabar yang sama di media tepercaya atau situs cek fakta seperti turnbackhoax.id. "
                . "Jika tidak ada satu pun yang memberitakan, curigai.\n\n"
                . "Jika ternyata hoaks, luruskan dengan **ramah dan sertakan bukti**, bukan mempermalukan."],
            ['data', 'data-pribadi', 'Data Pribadi: Apa yang Berharga dan Mengapa',
                'Mengenali data yang tidak boleh sembarangan dibagikan.', 3,
                "Data pribadi adalah \"bahan baku\" kejahatan digital. Jenis yang berisiko tinggi:\n\n"
                . "- **NIK, foto KTP, dan KK**\n"
                . "- **Selfie sambil memegang KTP**\n"
                . "- **Kode OTP, PIN, dan kata sandi**\n"
                . "- Nomor rekening dan data kartu\n\n"
                . "## Prinsip minimal\n\n"
                . "Berikan **hanya data yang benar-benar dibutuhkan**, hanya kepada pihak yang "
                . "terverifikasi, lewat kanal resmi.\n\n"
                . "Indonesia memiliki Undang-Undang Pelindungan Data Pribadi (UU PDP No. 27 Tahun 2022) "
                . "yang mengatur hak pemilik data. Tetap saja, pencegahan paling efektif dimulai darimu."],
            ['data', 'sandi-2fa-privasi', 'Kata Sandi, 2FA, dan Privasi Media Sosial',
                'Tiga kebiasaan sederhana yang menutup banyak celah.', 4,
                "## Kata sandi kuat\n\n"
                . "Gunakan kalimat sandi (passphrase) yang panjang dan **unik** untuk tiap akun penting, "
                . "terutama email. Jangan memakai tanggal lahir atau nama sendiri.\n\n"
                . "## Verifikasi dua langkah (2FA)\n\n"
                . "Aktifkan 2FA di email dan media sosial. Walau kata sandi bocor, pelaku tetap butuh "
                . "langkah kedua. Jangan pernah membagikan kode 2FA.\n\n"
                . "## Privasi media sosial\n\n"
                . "- Pertimbangkan akun pribadi (private).\n"
                . "- Jangan unggah foto KTP, tiket, atau seragam dengan alamat jelas.\n"
                . "- Batasi siapa yang bisa mengirim pesan dan menandai kamu.\n"
                . "- Tinjau izin aplikasi (lokasi, kontak, SMS) secara berkala."],
        ];

        foreach ($lessons as $i => [$cat, $slug, $title, $summary, $minutes, $body]) {
            Lesson::updateOrCreate(['slug' => $slug], [
                'category_id' => Category::where('slug', $cat)->value('id'),
                'title' => $title,
                'summary' => $summary,
                'body' => $body,
                'read_minutes' => $minutes,
                'xp_reward' => 20,
                'position' => $i,
            ]);
        }
    }
}
