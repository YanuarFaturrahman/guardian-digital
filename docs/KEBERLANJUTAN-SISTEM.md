# Keberlangsungan Sistem (Fitur Non-Fungsional)

Menu **Sistem** (khusus superadmin) kini punya empat tab baru di samping Ringkasan, Log Aktivitas, Pengaturan, Backup, dan Sesi Aktif. Tujuannya satu: website tetap hidup, kalau bermasalah cepat ketahuan, dan bisa dipulihkan.

| Aspek non-fungsional | Fitur | Tab / lokasi |
|---|---|---|
| Ketersediaan (availability) | Pemeriksaan kesehatan, riwayat, uptime, endpoint `/status` | 🩺 Kesehatan |
| Keandalan (reliability) | Verifikasi integritas backup, peringatan email saat backup gagal | 💾 Backup |
| Kemudahan perawatan (maintainability) | Pencatatan error otomatis, dikelompokkan, bisa ditandai selesai | 🐞 Error Aplikasi |
| Kinerja (performance) | Pembersihan data kedaluwarsa, ukuran tabel, manajemen cache | 🧹 Pemeliharaan |
| Komunikasi ke pengguna | Banner pengumuman terjadwal per peran | 📣 Pengumuman |
| Pemantauan proaktif | Peringatan email ke superadmin, batas simpan data | ⚙️ Pengaturan |

## 1. Kesehatan Sistem

Memeriksa langsung: database (waktu respons), cache (tulis lalu baca), folder `storage` (bisa ditulis), ruang disk, dan ekstensi PHP yang dibutuhkan. Butir ini **kritis** dan menentukan status keseluruhan:

- `ok` = semua baik
- `degraded` = ada yang menipis/lambat (mis. ruang disk di bawah 1 GB, database lebih dari 1 detik)
- `down` = database tidak bisa dihubungi

Butir **saran** (tidak memengaruhi status): denyut penjadwal, kesegaran backup, pengiriman email, error baru 24 jam. Ruang disk dinilai dari ruang bebas absolut: `warn` < 1 GB, `fail` < 200 MB.

Penjadwal mencatat pemeriksaan tiap 5 menit ke tabel `health_checks`; dari situlah uptime 24 jam / 7 hari / 30 hari dihitung (persentase pemeriksaan yang tidak `down`). Tombol **Periksa & catat sekarang** mencatat satu pemeriksaan manual (tidak dihitung sebagai denyut penjadwal).

### Endpoint `/status` untuk monitor eksternal

`GET /status` mengembalikan `{"status":"ok|degraded|down|maintenance","time":"..."}`. Kode 200 bila layanan hidup, 503 bila database tidak terjangkau atau mode pemeliharaan aktif. Tidak membuka detail konfigurasi, tidak membuat sesi/cookie, dibatasi 60 permintaan per menit.

Daftarkan alamat `https://domain-anda/status` di **UptimeRobot** atau **BetterStack** (gratis, cek tiap 5 menit). Ini penting karena penjadwal di server tidak bisa melapor kalau servernya sendiri mati; monitor eksternal yang akan memberi tahu.

> `/up` bawaan Laravel tetap ada, tetapi hanya memeriksa PHP hidup, tanpa database. Pakai `/status` untuk pemantauan.

### Agar penjadwal berjalan

| Lingkungan | Yang perlu dilakukan |
|---|---|
| Server/VPS biasa | Cron: `* * * * * cd /path/app && php artisan schedule:run >> /dev/null 2>&1` |
| Hosting tanpa cron (InfinityFree) | Isi `LAZY_SCHEDULER=true` di `.env`. Pemeriksaan jalan tiap ≤10 menit **selama ada pengunjung**. |
| Vercel/Docker | Pakai cron eksternal yang memanggil `schedule:run`, atau `LAZY_SCHEDULER=true`. |

Kartu "Denyut penjadwal" di tab Kesehatan menunjukkan kapan terakhir berjalan; peringatan muncul bila lebih dari 30 menit.

## 2. Error Aplikasi

Setiap error server (HTTP 5xx / exception tak tertangani) dicatat ke tabel `error_logs` dan dikelompokkan per **kelas + file + baris**. Kejadian berulang menaikkan hitungan, bukan menambah baris. Log bawaan Laravel tetap berjalan seperti biasa.

Kenapa di database? Di Vercel/Docker log hanya ke `stderr`, dan di hosting gratis file log sulit dibuka. Dengan ini superadmin bisa melihat error langsung dari browser.

- **Tandai selesai** menyembunyikannya dari daftar utama. Jika error yang sama **muncul lagi**, otomatis dibuka kembali.
- Tidak dicatat: error 4xx (halaman tidak ditemukan, validasi gagal, belum login).
- **Perlindungan data pribadi** (relevan dengan UU PDP): isi form tidak disimpan; query string dibuang; segmen URL yang tampak seperti token disamarkan jadi `*`; alamat email di pesan error disensor menjadi `[email]` (MySQL memang menaruh nilai kolom di pesan duplikat); jejak tumpukan hanya `file:baris` tanpa argumen; path absolut server dibuang.
- Catatan lama dibuang otomatis sesuai **Simpan catatan error (hari)** di Pengaturan (bawaan 30 hari, dihitung dari kemunculan terakhir).

## 3. Pemeliharaan Data & Cache

Membuang data sementara yang tidak berguna, tiap hari pukul 04:00 (`php artisan system:maintain`) atau lewat tombol:

| Jenis | Kriteria |
|---|---|
| Sesi login | `last_activity` lebih tua dari `SESSION_LIFETIME` |
| Token atur ulang sandi | lebih tua dari masa berlaku (`auth.passwords.users.expire`) |
| Cache (driver `database`) | `expiration` sudah lewat |
| Log aktivitas | lebih tua dari batas simpan di Pengaturan |
| Catatan error / riwayat kesehatan | lebih tua dari batas simpan di Pengaturan |

**Tidak pernah menyentuh** pengguna, skenario, progres belajar, atau persetujuan privasi. Halaman ini juga menampilkan ukuran database dan tabel terbesar (MySQL: dari `information_schema`, jumlah baris adalah perkiraan).

Tombol cache: *Muat ulang cache pengaturan* dan *Bersihkan cache tampilan* aman dipakai kapan saja. *Kosongkan seluruh cache* ikut me-reset hitungan penguncian login per akun (aman, tapi percobaan masuk yang gagal terhitung ulang dari nol).

## 4. Pengumuman

Banner di bawah navbar untuk memberi tahu pengguna sebelum atau saat ada gangguan. Atur isi (maks. 300 karakter, teks biasa), tingkat (Informasi / Peringatan / Penting), sasaran (semua pengunjung atau satu peran), serta jadwal mulai/berakhir.

- Tingkat **Penting / darurat** tidak bisa ditutup pengguna dan selalu tampil paling atas; tingkat lain bisa ditutup (selama sesi browser).
- Isi selalu di-escape, jadi HTML/skrip yang diketik tidak dijalankan.
- Perubahan langsung terlihat (cache banner dihapus saat disimpan).

Alur yang disarankan untuk pemeliharaan terencana: buat pengumuman terjadwal H-1 → aktifkan **Mode Pemeliharaan** (Pengaturan) di jam yang dijanjikan → nonaktifkan setelah selesai.

## 5. Backup: verifikasi & peringatan

- Setiap backup (manual maupun otomatis) kini **diverifikasi otomatis** setelah dibuat: arsip gzip harus utuh (tidak terpotong/rusak), berformat benar, dan untuk backup SQL harus lengkap sampai penutup. Backup yang tidak lolos dilaporkan sebagai gagal.
- Tombol **Verifikasi** di tiap baris untuk memeriksa backup lama kapan saja (hasil tercatat di Log Aktivitas).
- Restore tetap **manual** dan sengaja tidak lewat tombol (menimpa seluruh data); petunjuknya ada di tab Backup.

> ⚠️ Backup disimpan di server aplikasi. Di Vercel/container, file ini hilang saat deploy ulang. Unduh backup penting secara berkala dan simpan di tempat lain.

## 6. Peringatan email

Aktifkan **Kirim peringatan email ke superadmin** di Pengaturan > Pemantauan & peringatan. Email dikirim saat:

- status sistem **berubah** dari `ok` menjadi `degraded`/`down` (bukan terus-menerus selama masih buruk),
- backup gagal dibuat atau tidak lolos verifikasi.

Satu jenis peringatan maksimal sekali per 6 jam. Butuh email yang benar-benar terkirim (`MAIL_MAILER=smtp`, lihat dokumen Keamanan & Privasi bagian 1); jika masih `log`, peringatan tidak dikirim dan Pengaturan menampilkan pemberitahuannya.

> Peringatan kesehatan bergantung pada penjadwal. Untuk kasus "server mati total", andalkan monitor eksternal pada `/status`.

## Perintah artisan baru

```bash
php artisan system:health-check   # periksa kesehatan + catat riwayat
php artisan system:maintain       # bersihkan data kedaluwarsa
php artisan migrate               # membuat tabel health_checks, error_logs, announcements
```

## Pengujian

`tests/Feature/SystemContinuityTest.php` (57 tes) mencakup hak akses, status down saat database putus, ambang disk, pengelompokan dan penyamaran error, pembersihan data kedaluwarsa, jendela waktu dan sasaran pengumuman, verifikasi backup (utuh / terpotong / rusak / bukan backup / SQLite), cooldown peringatan, serta penjadwal. Jalankan: `php artisan test`.
