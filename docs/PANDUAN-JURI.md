# Panduan Penilaian untuk Juri

Panduan ini membantu menjelajahi Guardian Digital dengan cepat. Perkiraan waktu: **10–15 menit** untuk alur inti.

## Tautan

| | |
|---|---|
| Live demo | https://guardian-digital.gamer.gd |
| Repositori | https://github.com/YanuarFaturrahman/guardian-digital |
| Akun demo | Lihat bagian [Akun Demo di README](../README.md#-akun-demo) |

## Ringkasan Tiga Kalimat

Guardian Digital melatih pelajar mengenali penipuan, investasi bodong, hoaks, dan kebocoran data lewat **simulasi cerita bercabang** yang aman untuk salah. Guru bisa memantau progres, orang tua bisa terlibat dan menjaga hak data anak, dan admin bisa mengelola konten serta memantau kesehatan sistem tanpa menyentuh kode. Privasi dan keamanan dirancang sejak awal karena aplikasi ini menangani data pelajar.

## Rute Demo yang Disarankan

| Menit | Langkah | Yang bisa dinilai |
|---|---|---|
| 0–1 | Buka beranda, lihat konsep **Kenali · Pelajari · Lindungi** | Kejelasan pesan dan tema |
| 1–5 | Masuk sebagai **Siswa**, mainkan satu misi (mis. "Paket Misterius dari Kurir") dan ambil pilihan yang salah dulu | Cerita bercabang, umpan balik langsung, skor |
| 5–6 | Buka **ulasan hasil** | Daftar *red flag* dan alasannya |
| 6–7 | Buka **Belajar**, **Aksi**, **Lencana**, **Papan peringkat** | Gamifikasi dan aksi nyata |
| 7–9 | Masuk sebagai **Guru**, buka kelas dan laporan siswa | Keterukuran bagi guru |
| 9–10 | Masuk sebagai **Orang tua**, lihat data anak dan hak wali | Keterlibatan keluarga dan privasi |
| 10–13 | Masuk sebagai **Superadmin**, buka Skenario lalu menu **Sistem** | CMS, audit, backup, kesehatan, error, pengumuman |

> Akun superadmin dan admin sekolah memakai **verifikasi dua langkah**. Kode disediakan lewat kanal pengumpulan karya; lihat catatan di README.

## Kesesuaian dengan Tema

Tema: *"Guardians of the Digital Space – Empowering Today's Generation Toward a Safer Cyber Ecosystem"*

| Unsur tema | Wujud di aplikasi |
|---|---|
| Guardians | Pengguna berperan sebagai penjaga dan naik level hingga Grandmaster |
| Digital space | Empat kategori ancaman nyata: scam, investasi bodong, hoaks, kebocoran data |
| Today's generation | Format game: skor, XP, lencana, papan peringkat, efek suara |
| Safer cyber ecosystem | Melibatkan siswa, guru, orang tua, dan sekolah; aksi keamanan nyata di akhir misi |

## Titik Penilaian (Rubrik Mandiri)

| Aspek | Bukti di proyek | Lokasi |
|---|---|---|
| Fungsionalitas | 5 peran, 5 misi, 8 materi, 9 aksi, 14 lencana, CMS lengkap | `routes/web.php`, `database/seeders/` |
| Antarmuka dan pengalaman pengguna | Tailwind 4, tur onboarding per peran, responsif | `resources/views/`, `resources/css/app.css` |
| Keamanan | 2FA TOTP, penguncian login, CSRF, validasi, kebijakan kata sandi, audit log | [KEAMANAN-DAN-PRIVASI.md](KEAMANAN-DAN-PRIVASI.md) |
| Privasi | Persetujuan orang tua, unduh/hapus data, anonimisasi log | `PrivacyService`, `AccountDataService` |
| Kualitas kode | Controller per peran, service terpisah, policy, middleware | [ARSITEKTUR-DAN-DATABASE.md](ARSITEKTUR-DAN-DATABASE.md) |
| Pengujian | Tes otomatis PHPUnit (`php artisan test`) | `tests/Feature/`, `tests/Unit/` |
| Keberlangsungan | Kesehatan, uptime, error, backup terverifikasi, peringatan | [KEBERLANJUTAN-SISTEM.md](KEBERLANJUTAN-SISTEM.md) |
| Dokumentasi | README, panduan pengguna, arsitektur, keamanan, keberlangsungan | folder `docs/` |
| Inovasi | Cerita bercabang berbasis graf yang bisa diedit tanpa kode, tiga pemangku kepentingan dalam satu platform | `scenario_nodes`, `scenario_choices` |

## Hal yang Mudah Dicoba

- **Cerita bercabang:** ambil pilihan berbeda di misi yang sama dan bandingkan akhir ceritanya.
- **XP adil:** ulangi misi dengan skor lebih rendah; XP tidak berkurang dan tidak bertambah.
- **Pembatasan peran:** coba buka `/admin` dari akun siswa; akses ditolak.
- **Endpoint pemantauan:** buka `https://guardian-digital.gamer.gd/status` untuk melihat status ringkas sistem.
- **Privasi:** daftar sebagai siswa di bawah 18 tahun; akun menunggu persetujuan orang tua.

## Menjalankan Sendiri

Langkah lengkap ada di [README](../README.md#-menjalankan-di-komputer-lokal). Singkatnya:

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm run build && php artisan serve
php artisan test
```

## Keterbatasan yang Kami Sadari

Dicatat terbuka di bagian *Keterbatasan* pada README: konten baru 5 misi dan 8 materi, 2FA belum memakai kode QR, teks kebijakan privasi perlu ditinjau ahli hukum, dan penjadwal di hosting gratis hanya berjalan saat ada pengunjung.
