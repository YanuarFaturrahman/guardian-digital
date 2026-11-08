# Panduan Pengguna Guardian Digital

Panduan singkat untuk setiap peran. Semua skenario di aplikasi adalah **simulasi**; tidak ada uang atau data nyata yang dipakai. Boleh salah di sini supaya tidak salah di dunia nyata.

## Mendaftar dan Masuk

1. Buka halaman **Daftar** dan pilih peran (Siswa, Guru, atau Orang tua).
2. Isi data, centang persetujuan **Kebijakan Privasi**, lalu daftar.
3. Siswa **di bawah 18 tahun** perlu persetujuan orang tua/wali sebelum bisa bermain (lihat bagian Orang Tua).
4. Lupa kata sandi? Pakai **Lupa kata sandi** di halaman masuk; tautan atur ulang dikirim lewat email.

Aturan kata sandi: minimal 8 karakter, berisi huruf dan angka, bukan sandi umum, dan tidak memuat bagian email.

## 🎓 Siswa

**Bermain misi**
1. Buka **Skenario** dan pilih misi (⭐ menunjukkan tingkat kesulitan).
2. Baca situasi, lalu pilih tindakan. Setiap pilihan langsung memberi umpan balik dan poin (+/−).
3. Capai akhir cerita (baik, netral, atau buruk). Kamu mendapat skor 0–100 dan XP.
4. Buka **ulasan hasil** untuk melihat *red flag* yang terlewat atau berhasil kamu kenali.
5. Mau skor lebih baik? Mainkan ulang. XP hanya bertambah bila skor terbaikmu naik.

**Belajar dan beraksi**
- **Belajar:** materi singkat per kategori; tandai selesai untuk mendapat XP.
- **Aksi:** tugas keamanan nyata (aktifkan 2FA, rapikan izin aplikasi, simpan Kontak OJK 157, dan lain-lain). Urgensinya: Darurat, Penting, Tambahan.
- **Lencana:** 14 lencana yang terbuka otomatis saat syaratnya terpenuhi.
- **Papan peringkat:** lihat posisimu terhadap pengguna lain.
- **Level:** Rookie → Pengamat → Penjaga → Guardian → Grandmaster.

**Bergabung ke kelas:** masukkan **kode kelas** dari gurumu di dasbor siswa.

Tips: aktifkan efek suara atau matikan sesukamu, dan ulangi tur onboarding bila perlu.

## 🦉 Guru

1. Masuk dan buka dasbor **Guru**.
2. Buat **kelas** baru, lalu bagikan **kode kelas** ke siswa.
3. Buka kelas untuk melihat progres seluruh siswa.
4. Klik nama siswa untuk **laporan per siswa**: misi yang dimainkan, skor, materi, dan lencana.

Guru hanya bisa melihat siswa di kelasnya sendiri.

## 🌻 Orang Tua / Wali

1. Minta **kode tautan** dari akun anak (ada di akun siswa).
2. Di dasbor **Orang tua**, masukkan kode itu dan setujui pernyataan persetujuan. Waktu dan identitas wali tercatat.
3. Pantau progres anak dan baca tips **mengobrol, bukan menghakimi**.

Hak wali atas data anak:

| Tindakan | Akibat |
|---|---|
| Unduh data anak | Salinan data dalam format JSON |
| Cabut persetujuan | Akun anak terkunci sampai ada persetujuan baru |
| Hapus akun anak | Akun dihapus permanen |
| Lepas tautan | Hubungan orang tua-anak diputus |

## 🏫 Admin Sekolah

- Wajib memasang **verifikasi dua langkah (2FA)** saat pertama masuk.
- Lihat dasbor sekolah dan kelola pengguna di sekolahnya: buat **sandi sementara**, **reset 2FA**, atau **nonaktifkan/aktifkan** akun.
- Tidak bisa mengakses pengguna sekolah lain atau menu Sistem.

## 👑 Superadmin

Wajib 2FA. Menu utama:

| Menu | Kegunaan |
|---|---|
| Skenario | Buat dan ubah misi: adegan, pilihan, poin, dan *red flag* |
| Kategori, Materi, Aksi, Lencana | Kelola seluruh konten edukasi |
| Sekolah | Ubah nama, gabungkan sekolah duplikat, buat admin sekolah |
| Pengguna | Sandi sementara, reset 2FA, nonaktifkan, hapus permanen (hanya akun nonaktif) |
| Sistem → Ringkasan, Log, Pengaturan | Status keamanan, jejak audit, mode pemeliharaan |
| Sistem → Backup | Backup manual/otomatis, unduh, dan verifikasi integritas |
| Sistem → Sesi | Keluarkan sesi tertentu atau semua |
| Sistem → Kesehatan, Error, Pemeliharaan, Pengumuman | Pemantauan dan perawatan (lihat [KEBERLANJUTAN-SISTEM.md](KEBERLANJUTAN-SISTEM.md)) |

**Menambah misi baru:** Skenario → Baru → isi judul, kategori, dan tingkat → tambah adegan → tambah pilihan di tiap adegan dan arahkan ke adegan berikutnya → tandai satu adegan sebagai awal → publikasikan.

## Verifikasi Dua Langkah (2FA)

1. Buka **Akun → Dua langkah**.
2. Pasang aplikasi autentikator (Google Authenticator atau Authy), lalu masukkan kunci atau tautan `otpauth://` yang ditampilkan.
3. Masukkan kode 6 digit untuk mengaktifkan.
4. **Simpan 8 kode pemulihan** di tempat aman; tiap kode hanya bisa dipakai sekali.

Kehilangan ponsel dan kode pemulihan? Admin lain bisa mereset 2FA lewat menu Pengguna, atau pemilik server menjalankan `php artisan two-factor:reset email@anda`.

## Privasi Akunmu

Di **Akun → Privasi** kamu bisa mengunduh salinan data (JSON) atau menghapus akun secara permanen. Aplikasi tidak meminta nomor telepon atau lokasi.

## Pertanyaan Umum

**Kenapa akun saya terkunci?** Lima kali salah kata sandi mengunci akun selama 15 menit. Tunggu, lalu coba lagi atau atur ulang kata sandi.

**Kenapa saya diminta ganti kata sandi?** Akun memakai sandi sementara atau sandi yang dianggap lemah.

**Kenapa akun saya tidak bisa bermain?** Siswa di bawah 18 tahun menunggu persetujuan orang tua/wali.

**Skor saya turun setelah mengulang?** Skor terbaik tetap tersimpan; XP tidak berkurang.
