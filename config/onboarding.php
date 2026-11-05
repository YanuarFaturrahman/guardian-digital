<?php

/*
 | Isi panduan (onboarding tour), dikelompokkan per peran.
 |
 | Kunci setiap slide:
 |   icon, title, lead    teks pembuka
 |   demo                 mission | action | tiers | levels  (bagian khusus)
 |   items                [[ikon, judul, teks], ...]  kartu kecil
 |   steps                [[judul, teks], ...]        langkah bernomor
 |   bullets              [teks, ...]                 daftar poin
 |   note                 kotak catatan
 |   cta                  [[label, nama_route|null, kelas_tombol], ...]  tombol di slide terakhir
 */

return [

    // Samakan dengan User::levelData() bila batas XP level diubah.
    'levels' => [
        ['Rookie', 0],
        ['Pengamat', 200],
        ['Penjaga', 500],
        ['Guardian', 1000],
        ['Grandmaster', 2000],
    ],

    'xp_sources' => [
        ['🎯', 'Misi', 'Sampai XP misi, dikalikan skormu (skor 100 = XP penuh). Ada bonus +10 XP saat pertama kali menyelesaikan sebuah misi.'],
        ['📚', 'Materi', 'Umumnya 20 XP per materi, sekali saja. Angka pastinya tertera di tombol "Tandai selesai".'],
        ['⚡', 'Aksi', 'Umumnya 50 XP (Darurat), 35 XP (Penting), atau 20 XP (Tambahan), sekali per aksi.'],
    ],

    // Mini misi untuk dicoba di panduan (hanya contoh, tidak menambah XP)
    'demo_mission' => [
        'speaker' => 'Nomor tidak dikenal',
        'body' => "Paket kakak GAGAL diantar! Cek foto resi di file Resi_Paket.apk. Jika tidak konfirmasi dalam 1 jam, paket dikembalikan ke pengirim.",
        'choices' => [
            [
                'label' => 'Unduh file-nya supaya paketnya tidak dikembalikan.',
                'points' => -15,
                'feedback' => 'Terburu-buru adalah senjata penipu. File .apk adalah aplikasi, bukan foto resi.',
            ],
            [
                'label' => 'Jangan dibuka. Cek status pesanan lewat aplikasi toko tempat aku belanja.',
                'points' => 30,
                'feedback' => 'Tepat! Verifikasi lewat jalur resmi yang kamu buka sendiri.',
            ],
            [
                'label' => 'Abaikan saja pesannya.',
                'points' => 5,
                'feedback' => 'Aman, tetapi belum melindungi orang lain. Guardian juga memperingatkan dan melapor.',
            ],
        ],
    ],

    // Kartu aksi untuk dicoba di panduan (hanya contoh)
    'demo_action' => [
        'icon' => '🔑',
        'title' => 'Aktifkan verifikasi dua langkah (2FA)',
        'description' => 'Aktifkan 2FA di email utama dan satu akun media sosial. Jangan bagikan kodenya kepada siapa pun.',
    ],

    // ------------------------------------------------------------------ SISWA
    'student' => [
        [
            'icon' => '🛡️',
            'title' => 'Selamat datang, Guardian!',
            'lead' => 'Guardian Digital adalah game simulasi untuk melatihmu mengenali penipuan online, investasi bodong, hoaks, dan kebocoran data pribadi. Semua misi hanya simulasi: boleh salah di sini supaya tidak salah di dunia nyata.',
            'items' => [
                ['👁️', 'Kenali', 'Mainkan misi cerita dan temukan tanda bahayanya.'],
                ['📚', 'Pelajari', 'Pahami cara kerja jebakan lewat materi singkat.'],
                ['⚡', 'Lindungi', 'Lakukan aksi nyata untuk mengamankan dirimu dan orang lain.'],
            ],
        ],
        [
            'icon' => '🧭',
            'title' => 'Cara kerjanya',
            'lead' => 'Ikuti alur ini, dan kamu akan naik level sambil makin aman.',
            'steps' => [
                ['Mainkan misi', 'Pilih tindakanmu di cerita bercabang dan lihat akibatnya.'],
                ['Lihat hasilnya', 'Kamu mendapat skor, XP, ulasan keputusan, dan daftar red flag.'],
                ['Baca materi', 'Perdalam pemahaman dari materi singkat per kategori.'],
                ['Kerjakan aksi', 'Amankan akun aslimu dengan checklist aksi.'],
                ['Kumpulkan hadiah', 'XP, level, Guardian Badge, dan posisi di papan peringkat.'],
            ],
        ],
        [
            'icon' => '🎯',
            'title' => 'Misi: berlatih memilih tanpa risiko',
            'lead' => 'Coba satu misi mini di bawah ini. Pilih salah satu, lalu baca penjelasannya.',
            'demo' => 'mission',
            'bullets' => [
                'Setiap misi adalah cerita pendek bercabang (WhatsApp, Instagram, dan lain-lain).',
                'Pilihanmu menentukan akhir cerita: baik, netral, atau buruk.',
                'Setelah memilih, kamu langsung melihat poin dan alasannya.',
                'Skor akhir 0 sampai 100. Makin bijak pilihanmu, makin besar XP-nya.',
                'Mengulang misi hanya menambah XP kalau skormu naik, jadi tidak bisa dicurangi.',
                'Di akhir ada daftar red flag dan ulasan semua keputusanmu.',
            ],
        ],
        [
            'icon' => '📚',
            'title' => 'Belajar: pahami jebakannya',
            'lead' => 'Setiap kategori punya materi singkat, sekitar 3 sampai 4 menit baca.',
            'steps' => [
                ['Buka menu Belajar', 'Materi dikelompokkan per kategori ancaman.'],
                ['Baca sampai selesai', 'Ada contoh, ciri-ciri, dan cara menghadapinya.'],
                ['Klik "Tandai selesai"', 'XP masuk sekali per materi.'],
                ['Praktikkan', 'Di bawah materi ada tautan ke misi yang sesuai.'],
            ],
            'note' => 'Tips: baca materinya sebelum atau sesudah misi dalam kategori yang sama. Hasilnya lebih menempel.',
        ],
        [
            'icon' => '⚡',
            'title' => 'Aksi: dari yang paling mendesak',
            'lead' => 'Aksi adalah langkah nyata untuk mengamankan akunmu. Makin mendesak, makin besar XP-nya.',
            'demo' => 'tiers',
        ],
        [
            'icon' => '✅',
            'title' => 'Tutorial: mengerjakan aksi, langkah demi langkah',
            'lead' => 'Ikuti enam langkah ini, lalu coba kartu contoh di bawahnya.',
            'steps' => [
                ['Buka menu Aksi', 'Di sana ada tiga bagian: Darurat, Penting, dan Tambahan.'],
                ['Mulai dari Darurat', 'Kerjakan yang paling mendesak dulu. XP-nya juga paling besar.'],
                ['Baca kartu aksinya', 'Setiap kartu menjelaskan apa yang harus dilakukan.'],
                ['Kerjakan di akun aslimu', 'Buka pengaturan akun atau aplikasi yang dimaksud, lalu lakukan langkahnya. Minta bantuan orang tua kalau bingung.'],
                ['Klik "Sudah saya lakukan"', 'Tandai setelah benar-benar dikerjakan. Setiap aksi dihitung sekali.'],
                ['Lihat progresmu', 'Bar progres terisi, XP bertambah, dan lencana bisa terbuka.'],
            ],
            'demo' => 'action',
            'note' => 'Website tidak bisa memeriksa pengaturan akunmu di aplikasi lain, jadi ini berdasarkan kejujuranmu. Tujuannya membentuk kebiasaan aman, bukan sekadar mengejar XP.',
        ],
        [
            'icon' => '🚨',
            'title' => 'Saat ada yang terasa janggal',
            'lead' => 'Hafalkan empat kata ini. Kamu bisa memakainya di dunia nyata.',
            'items' => [
                ['⏸️', 'JEDA', 'Berhenti. Jangan klik, balas, atau transfer. Desakan waktu adalah senjata penipu.'],
                ['🔎', 'CEK', 'Verifikasi lewat jalur resmi yang kamu buka sendiri.'],
                ['🚨', 'LAPOR', 'Ceritakan ke orang tua atau guru, simpan bukti, lalu laporkan.'],
                ['📣', 'BAGIKAN', 'Peringatkan teman dan keluarga lewat jalur aman.'],
            ],
            'note' => 'Daftar kanal pelaporan resmi ada di menu Aksi, bagian "Ke mana melapor?".',
        ],
        [
            'icon' => '⭐',
            'title' => 'XP dan level',
            'lead' => 'XP adalah poin pengalamanmu. Kumpulkan XP untuk naik level.',
            'demo' => 'levels',
        ],
        [
            'icon' => '🏅',
            'title' => 'Guardian Badge',
            'lead' => 'Lencana terbuka otomatis saat kamu mencapai syaratnya. Saat terbuka, muncul pemberitahuan kuning di bagian atas halaman.',
            'items' => [
                ['🎯', 'Jumlah misi', 'Selesaikan beberapa misi berbeda.'],
                ['💯', 'Skor sempurna', 'Raih skor 100 di sebuah misi.'],
                ['🧠', 'Akhir terbaik', 'Capai akhir cerita yang baik beberapa kali.'],
                ['🔍', 'Per kategori', 'Tuntaskan misi dalam kategori tertentu.'],
                ['📚', 'Materi', 'Baca beberapa materi.'],
                ['⚡', 'Aksi dan XP', 'Kerjakan aksi dan capai total XP tertentu.'],
            ],
            'note' => 'Lihat koleksimu di menu Lencana. Lencana yang belum terbuka tampil abu-abu beserta syaratnya.',
        ],
        [
            'icon' => '🏆',
            'title' => 'Papan peringkat',
            'lead' => 'Berlomba menjadi Guardian terbaik, berdasarkan XP.',
            'items' => [
                ['🌏', 'Global', 'Semua siswa di Guardian Digital.'],
                ['🏫', 'Sekolah', 'Pilih sekolah mana pun lewat daftar. Sekolahmu ditandai ⭐.'],
                ['👥', 'Kelas', 'Muncul setelah kamu bergabung ke kelas dari gurumu.'],
            ],
            'note' => 'Nama yang tampil adalah nama tampilanmu. Pakai nama panggilan, jangan nama lengkap, untuk menjaga privasi.',
        ],
        [
            'icon' => '👪',
            'title' => 'Terhubung dengan orang tua dan guru',
            'lead' => 'Kamu yang menentukan siapa yang boleh memantau progresmu.',
            'steps' => [
                ['Kode Tautan Orang Tua', 'Ada di Beranda. Klik Salin, lalu berikan ke orang tuamu agar mereka bisa menautkan akun.'],
                ['Kode Kelas', 'Minta dari gurumu, lalu masukkan di kartu Kelasku di Beranda. Sekolahmu ikut terisi otomatis.'],
            ],
            'bullets' => [
                'Orang tua dan guru hanya melihat ringkasan progres: misi, skor, lencana, dan kategori yang perlu dilatih.',
                'Mereka tidak melihat percakapan atau data pribadimu yang lain.',
                'Tujuannya mengobrol dan mendampingi, bukan menghakimi.',
            ],
        ],
        [
            'icon' => '🚀',
            'title' => 'Siap jadi Guardian?',
            'lead' => 'Mulai dari misi pertama. Panduan ini bisa dibuka lagi kapan saja lewat tombol ❓ Panduan di bagian atas halaman.',
            'cta' => [
                ['Mulai misi pertama ▶', 'student.scenarios.index', 'btn-primary'],
                ['Nanti saja', null, 'btn-ghost'],
            ],
        ],
    ],

    // -------------------------------------------------------------- ORANG TUA
    'parent' => [
        [
            'icon' => '👪',
            'title' => 'Selamat datang di Guardian Digital',
            'lead' => 'Website ini melatih remaja mengenali penipuan, investasi bodong, hoaks, dan kebocoran data lewat misi bergaya game. Sebagai orang tua, kamu bisa memantau progres anak dan mengobrol dengannya.',
            'items' => [
                ['🎮', 'Anak bermain', 'Misi cerita bercabang yang aman, tanpa risiko nyata.'],
                ['📈', 'Kamu memantau', 'Progres, kategori yang kuat dan lemah, serta lencana.'],
                ['💬', 'Kalian mengobrol', 'Ada ide obrolan keluarga yang disesuaikan.'],
            ],
        ],
        [
            'icon' => '🔗',
            'title' => 'Menautkan akun anak',
            'steps' => [
                ['Minta kode ke anakmu', 'Kode Tautan (contoh: GD-AB12CD) ada di Beranda akun anak.'],
                ['Masukkan di Pantau Anak', 'Tempel kodenya di kolom "Hubungkan akun anak", lalu klik Hubungkan.'],
                ['Selesai', 'Anak muncul di daftar. Kamu bisa menautkan lebih dari satu anak.'],
            ],
            'note' => 'Penautan hanya bisa dilakukan dengan kode dari anak, jadi anak yang memutuskan. Kamu bisa melepas tautan kapan saja dari halaman detail.',
        ],
        [
            'icon' => '📊',
            'title' => 'Membaca laporan anak',
            'lead' => 'Klik kartu anak untuk melihat detailnya.',
            'bullets' => [
                'Level dan XP: seberapa aktif anak berlatih.',
                'Misi selesai dan rata-rata skor.',
                'Penguasaan per kategori: mana yang sudah kuat dan mana yang perlu dilatih.',
                'Misi terakhir yang dimainkan dan lencana yang diraih.',
                'Kapan terakhir aktif.',
            ],
            'note' => 'Kamu hanya melihat ringkasan progres. Percakapan dan data pribadi anak tidak ditampilkan.',
        ],
        [
            'icon' => '💬',
            'title' => 'Mengobrol tanpa menghakimi',
            'bullets' => [
                'Mulai dari rasa ingin tahu: "Misi mana yang paling seru? Kamu pilih apa?"',
                'Salah memilih di simulasi itu wajar dan memang tempatnya belajar. Jangan dihukum.',
                'Yakinkan anak bahwa ia boleh bercerita kalau ada hal janggal, bahkan yang memalukan.',
                'Sepakati aturan keluarga: tidak membagikan kode OTP, PIN, atau file aplikasi tanpa bertanya.',
                'Pertimbangkan kata sandi darurat keluarga untuk memastikan telepon atau pesan darurat itu asli.',
            ],
            'note' => 'Halaman detail anak memuat "Ide Obrolan Keluarga" sesuai kategori yang paling perlu dilatih.',
        ],
        [
            'icon' => '🚀',
            'title' => 'Siap memantau',
            'lead' => 'Panduan ini bisa dibuka lagi kapan saja lewat tombol ❓ Panduan di bagian atas halaman.',
            'cta' => [
                ['Buka Pantau Anak ▶', 'family.dashboard', 'btn-primary'],
                ['Nanti saja', null, 'btn-ghost'],
            ],
        ],
    ],

    // ------------------------------------------------------------------- GURU
    'teacher' => [
        [
            'icon' => '🧑‍🏫',
            'title' => 'Selamat datang di Guardian Digital',
            'lead' => 'Platform simulasi literasi digital untuk siswa SMA. Kamu bisa membuat kelas, memantau progres siswa, dan menjadikan hasilnya bahan diskusi di kelas.',
            'items' => [
                ['🏫', 'Buat kelas', 'Satu kelas punya satu kode untuk siswa.'],
                ['📊', 'Pantau progres', 'Per siswa dan per kategori.'],
                ['🏆', 'Peringkat kelas', 'Memotivasi siswa berlomba secara sehat.'],
            ],
        ],
        [
            'icon' => '➕',
            'title' => 'Membuat kelas dan membagikan kode',
            'steps' => [
                ['Buka Kelas Saya', 'Isi nama kelas, misalnya "XI IPA 1", lalu klik Buat kelas.'],
                ['Salin kode kelas', 'Setiap kelas punya kode enam karakter.'],
                ['Bagikan ke siswa', 'Siswa memasukkannya di kartu Kelasku pada Beranda mereka.'],
                ['Sekolah siswa terisi otomatis', 'Sekolah siswa mengikuti sekolahmu saat mereka bergabung ke kelasmu.'],
            ],
        ],
        [
            'icon' => '📊',
            'title' => 'Memantau kelas',
            'bullets' => [
                'Penguasaan kelas per kategori: rata-rata skor semua siswa.',
                'Daftar siswa berisi level, XP, jumlah misi, rata-rata skor, dan kapan terakhir aktif.',
                'Klik Detail untuk laporan lengkap satu siswa.',
                'Tombol Peringkat kelas menampilkan urutan siswa berdasarkan XP.',
            ],
            'note' => 'Kategori dengan rata-rata rendah layak dibahas bersama di kelas.',
        ],
        [
            'icon' => '💡',
            'title' => 'Ide memakainya di kelas',
            'bullets' => [
                'Mainkan satu misi bersama di layar, lalu minta siswa memilih lewat voting.',
                'Bahas red flag di akhir misi sebagai diskusi singkat.',
                'Beri tugas mengerjakan aksi Darurat (2FA dan kata sandi email) sebelum minggu berikutnya.',
                'Rayakan lencana dan peningkatan skor, bukan hanya peringkat teratas.',
            ],
        ],
        [
            'icon' => '🚀',
            'title' => 'Siap mengajar',
            'lead' => 'Panduan ini bisa dibuka lagi kapan saja lewat tombol ❓ Panduan di bagian atas halaman.',
            'cta' => [
                ['Buka Kelas Saya ▶', 'teacher.dashboard', 'btn-primary'],
                ['Nanti saja', null, 'btn-ghost'],
            ],
        ],
    ],

    // ---------------------------------------------- ADMIN / SUPERADMIN / SEKOLAH
    'admin' => [
        [
            'icon' => '🛠️',
            'title' => 'Selamat datang di panel pengelola',
            'lead' => 'Dari sini kamu mengelola isi dan pengguna Guardian Digital. Menu yang tampil menyesuaikan perananmu.',
        ],
        [
            'icon' => '📝',
            'title' => 'Mengelola konten',
            'items' => [
                ['🎮', 'Skenario', 'Tulis misi bercabang, atur poin, lalu terbitkan.'],
                ['📚', 'Belajar', 'Materi Markdown dengan pratinjau langsung.'],
                ['⚡', 'Aksi', 'Checklist aksi per tingkat urgensi.'],
                ['🗂️', 'Kategori', 'Jenis ancaman beserta warna dan ikonnya.'],
                ['🏅', 'Lencana', 'Syarat dan hadiah Guardian Badge.'],
            ],
        ],
        [
            'icon' => '👥',
            'title' => 'Pengguna dan sekolah',
            'bullets' => [
                'Menu Pengguna: kelola akun, atur ulang kata sandi, dan nonaktifkan akun.',
                'Menu Sekolah: rapikan nama sekolah dan gabungkan nama yang kembar.',
                'Menu Peringkat: lihat papan peringkat yang dilihat siswa.',
            ],
        ],
        [
            'icon' => '✅',
            'title' => 'Tips agar aman',
            'bullets' => [
                'Skenario baru berstatus draf. Uji dulu dari akun siswa sebelum diterbitkan.',
                'Gunakan Sembunyikan, bukan Hapus, bila hanya ingin menarik skenario sementara.',
                'Menghapus materi, aksi, atau skenario ikut menghapus catatan penyelesaiannya. XP siswa tetap.',
                'Jangan menjalankan seeder lagi setelah mengedit konten dari panel ini, karena bisa menimpa perubahanmu.',
            ],
            'cta' => [
                ['Mulai mengelola ▶', 'admin.dashboard', 'btn-primary'],
                ['Tutup', null, 'btn-ghost'],
            ],
        ],
    ],
];