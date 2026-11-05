@extends('layouts.app')
@section('title', 'Kebijakan Privasi')

@php
    $name = config('privacy.controller_name');
    $email = config('privacy.contact_email');
    $logDays = \App\Models\Setting::read('log_retention_days');
    $backupKeep = \App\Models\Setting::read('backup_keep');
@endphp

@section('content')
<div class="rise mx-auto max-w-3xl">
    <h1 class="h1">🔒 Kebijakan Privasi</h1>
    <p class="mb-6 mt-1 text-sm text-slate-400">Versi {{ config('privacy.policy_version') }} · berlaku sejak {{ config('privacy.effective_date') }}</p>

    <div class="card space-y-6 text-sm leading-relaxed text-slate-300">

        <p>
            Guardian Digital adalah platform belajar keamanan digital berbentuk simulasi untuk siswa, orang tua, dan guru.
            Kami menyusun kebijakan ini sesuai Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi (UU PDP),
            dengan bahasa sederhana supaya bisa dibaca juga oleh siswa dan orang tua.
        </p>

        <section>
            <h2 class="h2 mb-2">1. Siapa yang mengelola datamu</h2>
            <p>
                Pengendali data pribadi adalah <strong>{{ $name }}</strong>@if (config('privacy.contact_address')), beralamat di {{ config('privacy.contact_address') }}@endif.
                Pertanyaan, permintaan, dan pengaduan soal data pribadi bisa dikirim ke
                <a class="text-guard-400 hover:underline" href="mailto:{{ $email }}">{{ $email }}</a>.
            </p>
        </section>

        <section>
            <h2 class="h2 mb-2">2. Data apa yang kami kumpulkan</h2>
            <ul class="list-disc space-y-1 pl-6">
                <li><strong>Data akun (semua pengguna):</strong> nama tampilan, email, kata sandi (disimpan dalam bentuk acak satu arah, bukan teks asli), peran, sekolah, dan avatar.</li>
                <li><strong>Khusus siswa:</strong> tanggal lahir (hanya untuk menentukan apakah perlu persetujuan orang tua), email orang tua / wali jika diberikan, keanggotaan kelas, serta progres belajar: misi yang dimainkan, pilihan yang diambil, skor, XP, lencana, materi yang dibaca, dan aksi keamanan yang dicentang.</li>
                <li><strong>Khusus guru dan admin sekolah:</strong> kelas yang diampu dan sekolah yang dikelola.</li>
                <li><strong>Data teknis keamanan:</strong> alamat IP, jenis perangkat / peramban, dan waktu saat masuk, keluar, gagal masuk, atau melakukan perubahan penting. Data ini dicatat di log aktivitas untuk mendeteksi penyalahgunaan.</li>
                <li><strong>Catatan persetujuan:</strong> siapa menyetujui kebijakan ini, kapan, dari IP mana, dan versi kebijakan yang mana.</li>
            </ul>
            <p class="mt-2">Kami <strong>tidak</strong> meminta nama lengkap, alamat rumah, nomor telepon, foto, atau lokasi. Gunakan nama panggilan, bukan nama asli.</p>
        </section>

        <section>
            <h2 class="h2 mb-2">3. Untuk apa data dipakai</h2>
            <ul class="list-disc space-y-1 pl-6">
                <li>Menjalankan layanan: membuat akun, menyimpan progres, memberi lencana, dan menampilkan papan peringkat.</li>
                <li>Membantu guru dan orang tua memantau perkembangan belajar.</li>
                <li>Menjaga keamanan akun dan aplikasi, serta memulihkan data jika terjadi gangguan (cadangan database).</li>
                <li>Memenuhi kewajiban hukum dan menanggapi permintaan hakmu atas data.</li>
            </ul>
            <p class="mt-2">Dasar pemrosesan kami adalah <strong>persetujuanmu</strong> (atau persetujuan orang tua / wali untuk anak), serta kebutuhan menjalankan layanan yang kamu minta. Data tidak dijual dan tidak dipakai untuk iklan.</p>
        </section>

        <section>
            <h2 class="h2 mb-2">4. Data anak di bawah 18 tahun</h2>
            <p>
                Data pribadi anak mendapat perlindungan khusus. Siswa yang berusia di bawah 18 tahun hanya bisa memakai akunnya
                setelah <strong>orang tua atau wali menyetujui</strong>, baik lewat tautan yang dikirim ke email orang tua,
                maupun dari akun Orang Tua yang tertaut dengan Kode Tautan anak. Sebelum persetujuan diberikan, akun anak tidak bisa
                membuka fitur belajar.
            </p>
            <p class="mt-2">
                Orang tua / wali dapat kapan saja mencabut persetujuan, mengunduh salinan data anak, atau menghapus akun anak
                dari halaman laporan anak di akun Orang Tua.
            </p>
        </section>

        <section>
            <h2 class="h2 mb-2">5. Siapa yang bisa melihat datamu</h2>
            <ul class="list-disc space-y-1 pl-6">
                <li><strong>Pengguna lain di papan peringkat:</strong> nama tampilan, avatar, dan skor / XP bisa terlihat. Karena itu pakailah nama panggilan.</li>
                <li><strong>Guru:</strong> progres siswa di kelas yang diampunya.</li>
                <li><strong>Admin sekolah:</strong> data akun dan progres pengguna di sekolahnya.</li>
                <li><strong>Orang tua / wali yang tertaut:</strong> laporan belajar anak mereka.</li>
                <li><strong>Pengelola aplikasi (superadmin):</strong> data yang diperlukan untuk mengelola dan mengamankan sistem. Setiap tindakan pengelola dicatat di log aktivitas.</li>
            </ul>
        </section>

        <section>
            <h2 class="h2 mb-2">6. Pihak ketiga</h2>
            <ul class="list-disc space-y-1 pl-6">
                <li><strong>Penyedia hosting dan basis data</strong> yang menyimpan aplikasi dan datamu atas nama kami.</li>
                <li><strong>Layanan pengiriman email</strong> untuk surat atur ulang kata sandi dan permintaan persetujuan orang tua.</li>
                <li><strong>Google Fonts:</strong> huruf aplikasi dimuat dari server Google, sehingga peramban kamu terhubung ke Google dan alamat IP-mu terlihat oleh mereka.</li>
            </ul>
            <p class="mt-2">Kami tidak memasang pelacak iklan atau analitik pihak ketiga. Data hanya dibagikan kepada pihak lain jika diwajibkan hukum.</p>
        </section>

        <section>
            <h2 class="h2 mb-2">7. Cookie</h2>
            <p>Kami hanya memakai cookie yang esensial: cookie sesi agar kamu tetap masuk, cookie perlindungan formulir (CSRF), dan, jika kamu mencentang "Ingat saya", cookie masuk otomatis. Tidak ada cookie pelacak.</p>
        </section>

        <section>
            <h2 class="h2 mb-2">8. Berapa lama data disimpan</h2>
            <ul class="list-disc space-y-1 pl-6">
                <li>Data akun dan progres disimpan selama akunmu ada. Setelah akun dihapus, data tersebut dihapus permanen dari basis data utama.</li>
                <li>Log keamanan (IP dan perangkat) dihapus otomatis setelah {{ $logDays }} hari. Saat akun dihapus, log milikmu juga dibersihkan dari data pribadi.</li>
                <li>Salinan cadangan database disimpan terbatas (sekitar {{ $backupKeep }} cadangan otomatis terbaru). Data akun yang sudah dihapus bisa masih ada di cadangan lama sampai cadangan itu tergantikan secara berkala.</li>
            </ul>
        </section>

        <section>
            <h2 class="h2 mb-2">9. Bagaimana kami mengamankan datamu</h2>
            <p>Kata sandi disimpan terenkripsi satu arah, akses dibatasi menurut peran, sesi login bisa diakhiri oleh pengelola, dan aktivitas penting dicatat. Tidak ada sistem yang 100% aman. Jika terjadi kebocoran data pribadi yang berisiko bagimu, kami akan memberi tahu pengguna terdampak dan pihak berwenang sesuai ketentuan UU PDP.</p>
        </section>

        <section>
            <h2 class="h2 mb-2">10. Hakmu atas data pribadi</h2>
            <ul class="list-disc space-y-1 pl-6">
                <li><strong>Mengakses dan memperoleh salinan:</strong> unduh datamu (format JSON) di
                    @auth <a class="text-guard-400 hover:underline" href="{{ route('privacy.account') }}">Privasi Akun</a>@else halaman Privasi Akun setelah masuk @endauth.</li>
                <li><strong>Menghapus data:</strong> hapus akunmu permanen dari halaman yang sama.</li>
                <li><strong>Mencabut persetujuan:</strong> hapus akun, atau (untuk anak) orang tua mencabutnya dari halaman laporan anak.</li>
                <li><strong>Memperbaiki data yang keliru, membatasi pemrosesan, atau mengajukan keberatan:</strong> hubungi kami di {{ $email }}.</li>
            </ul>
            <p class="mt-2">Jika merasa hakmu belum dipenuhi, kamu juga berhak mengadu kepada lembaga pelindungan data pribadi yang berwenang.</p>
        </section>

        <section>
            <h2 class="h2 mb-2">11. Perubahan kebijakan</h2>
            <p>Jika kebijakan berubah bermakna, kami menaikkan nomor versi di atas dan meminta persetujuan ulang saat kamu masuk berikutnya (dan persetujuan ulang orang tua untuk akun anak).</p>
        </section>
    </div>

    <p class="mt-4 text-center text-xs text-slate-500">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" class="hover:text-white">← Kembali</a>
    </p>
</div>
@endsection
