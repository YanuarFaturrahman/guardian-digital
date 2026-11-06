# Keamanan & Privasi Guardian Digital

Dokumen ini merangkum perubahan untuk lima temuan prioritas tinggi dan cara mengaktifkannya di server.

## 1. Email reset sandi benar-benar terkirim (SMTP)

- `MAIL_MAILER=log` hanya menulis email ke `storage/logs`. Di production pakai `smtp`.
- Isi di **Vercel → Settings → Environment Variables** (contoh Brevo, gratis 300 email/hari):

  | Variabel | Nilai |
  |---|---|
  | `MAIL_MAILER` | `smtp` |
  | `MAIL_HOST` | `smtp-relay.brevo.com` |
  | `MAIL_PORT` | `587` |
  | `MAIL_USERNAME` | login SMTP dari Brevo |
  | `MAIL_PASSWORD` | SMTP key dari Brevo |
  | `MAIL_FROM_ADDRESS` | alamat pengirim yang sudah diverifikasi di Brevo |
  | `MAIL_FROM_NAME` | `Guardian Digital` |

- Uji dari komputer lokal (dengan variabel yang sama di `.env.aiven`/`.env`): `php artisan mail:test emailanda@gmail.com`
- Kegagalan SMTP kini tidak membuat halaman "Lupa kata sandi" menjadi 500 (dicatat di log), dan halaman **Keamanan & Sistem** menampilkan peringatan jika email belum benar-benar terkirim.

## 2. Rahasia di dalam zip / Git

- Pemeriksaan pada repository Anda: `.env` dan `.env.aiven` **tidak pernah ter-commit** (riwayat Git bersih). Yang berisiko hanya jika zip/folder dibagikan.
- `.gitignore` kini mengabaikan semua `.env.*` (kecuali `*.example`) dan berkas `*.sql`.
- Zip ini sengaja **tidak memuat** `.env`, `.env.aiven`, `.git`, `vendor`, dan `node_modules`. Gunakan `.env.example` dan `.env.aiven.example` sebagai templat.
- Jalankan `php artisan security:check` untuk memeriksa konfigurasi (APP_DEBUG, email, cookie aman, 2FA, kontak privasi, berkas `.env` yang ter-commit).
- **Seeder:** `db:seed` tidak lagi membuat akun demo (`admin@guardian.test` / `password`) kecuali lingkungan `local`/`testing`. Untuk superadmin pertama di server asli:
  1. Isi sementara di `.env.aiven`: `ADMIN_EMAIL=...` dan `ADMIN_PASSWORD=...` (min. 12 karakter, huruf besar+kecil+angka).
  2. `php artisan db:seed --env=aiven --force` (mengisi data materi + membuat superadmin).
  3. **Hapus** `ADMIN_PASSWORD` dari `.env.aiven`.
- Kalau screenshot berisi `DB_PASSWORD`/`APP_KEY` pernah dibagikan, ganti password database di Aiven dan `APP_KEY` di Vercel.

## 3. Perlindungan akun

| Fitur | Perilaku |
|---|---|
| Penguncian per akun | 5 kali salah kata sandi untuk satu email mengunci login email itu 15 menit, dari IP mana pun (`LOGIN_MAX_ATTEMPTS`, `LOGIN_LOCKOUT_MINUTES`). Reset kata sandi lewat email membuka kunci. Batas 10 percobaan/menit per IP tetap berlaku. |
| Aturan kata sandi di semua tempat | Daftar, atur ulang, ganti sandi, sandi sementara, dan halaman baru **Akun → Keamanan**. Min. 8 karakter + huruf + angka, bukan sandi umum, tidak memuat bagian email. Akun admin: min. 12 karakter + huruf besar/kecil. |
| Sandi lemah lama | Di production, pengguna yang masuk dengan sandi yang tidak memenuhi aturan dipaksa menggantinya (`FORCE_STRONG_PASSWORD_ON_LOGIN`). |
| Verifikasi email | Pendaftar baru harus klik tautan di email. Akun lama otomatis dianggap terverifikasi. Hanya aktif jika email benar-benar terkirim, supaya pendaftar tidak terkunci saat SMTP belum siap. |
| 2FA (TOTP) | **Wajib** untuk superadmin dan admin sekolah; opsional untuk peran lain (Akun → Keamanan). Kompatibel dengan Google Authenticator/Authy/Microsoft Authenticator. 8 kode pemulihan sekali pakai. Kode tidak bisa dipakai ulang. "Ingat saya" dinonaktifkan untuk akun 2FA wajib. |
| Kunci 2FA hilang | Superadmin mereset admin sekolah dari **Pengguna → Reset 2FA**. Untuk superadmin sendiri: `php artisan two-factor:reset email@anda` dari terminal. |

Pemasangan 2FA: masuk → halaman pemasangan → di aplikasi autentikator pilih **Masukkan kunci setelan** → ketik kunci yang tampil → masukkan kode 6 digit → **simpan kode pemulihan**. (Tidak ada kode QR agar tidak menambah dependensi; di ponsel tersedia tautan otomatis.)

Catatan desain: penguncian per akun bisa dipakai orang iseng untuk mengunci akun korban selama 15 menit. Ini kompromi yang disengaja (akun tetap aman); sesuaikan `LOGIN_LOCKOUT_MINUTES` bila perlu.

## 4. Privasi data siswa (UU PDP)

- **Kebijakan Privasi** di `/kebijakan-privasi` (tautan di footer dan saat daftar). Isi `PRIVACY_CONTROLLER_NAME` dan `PRIVACY_CONTACT_EMAIL`. **Teks ini templat teknis; minta tinjauan hukum/DPO sebelum dipakai resmi.** Naikkan `PRIVACY_POLICY_VERSION` bila isinya berubah: semua pengguna diminta setuju ulang.
- **Persetujuan saat daftar**: wajib mencentang kebijakan; siswa mengisi tanggal lahir; guru/orang tua menyatakan berusia 18+.
- **Persetujuan orang tua**: siswa di bawah `PRIVACY_CONSENT_AGE` (18) tertahan di halaman "Menunggu persetujuan" sampai orang tua menautkan akunnya (Pantau Anak) **dengan mencentang pernyataan persetujuan**. Persetujuan tercatat (waktu dan wali). Akun lama diminta setuju ulang dan mengisi tanggal lahir saat masuk berikutnya.
- **Hak pengguna** (menu 🔒 Akun → Privasi & data saya): unduh salinan data (JSON) dan hapus akun permanen (kata sandi + ketik HAPUS). Catatan aktivitas lama dianonimkan.
- **Hak wali**: wali yang memberi persetujuan dapat mengunduh data anak, **mencabut persetujuan** (akun anak terkunci), atau menghapus akun anak.
- Admin tidak bisa menghapus akunnya sendiri lewat menu ini (hindari sistem tanpa admin).
- Percobaan menebak kode tautan orang tua dan kode kelas dibatasi 10/menit.

Yang belum dicakup (perlu keputusan Anda): nama siswa tampil di papan peringkat (anjuran memakai nama panggilan sudah ada di kebijakan); masa simpan cadangan database; prosedur pemberitahuan insiden kebocoran; kode tautan yang bocor memungkinkan pihak lain "menyetujui" atas nama anak (verifikasi identitas wali di luar cakupan aplikasi).

## 5. Pengujian otomatis

136 tes (SQLite in-memory, tidak menyentuh database Anda): `php artisan test`

Cakupan: login dan penguncian, atur ulang sandi, aturan kata sandi, pendaftaran, verifikasi email, 2FA (termasuk vektor RFC 6238), matriks hak akses 5 peran × 8 halaman, kepemilikan percobaan misi, skor/XP/lencana, persetujuan orang tua, unduh dan hapus data, seeder production, dan perintah artisan.

## Langkah deploy

1. Timpa file project dengan isi zip (jangan timpa `.env` / `.env.aiven` Anda).
2. `php artisan migrate --env=aiven --force` (menambah kolom 2FA/privasi; akun lama otomatis terverifikasi email).
3. Tambahkan variabel baru di Vercel: `MAIL_*`, `PRIVACY_*`, `SESSION_SECURE_COOKIE=true` (lihat `.env.example`).
4. Commit, push, tunggu deploy, lalu masuk sebagai superadmin dan pasang 2FA.
5. Jalankan `php artisan security:check` dan pantau halaman **Keamanan & Sistem**.
