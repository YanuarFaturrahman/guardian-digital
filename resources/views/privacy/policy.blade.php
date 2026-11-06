@extends('layouts.app')
@section('title', 'Kebijakan Privasi')

@section('content')
@php
    $controller = config('privacy.controller_name');
    $contact = config('privacy.contact_email');
    $age = config('privacy.consent_age');
@endphp
<div class="rise mx-auto max-w-3xl">
    <h1 class="h1">Kebijakan Privasi</h1>
    <p class="mb-6 mt-1 text-sm text-slate-400">Versi {{ config('privacy.policy_version') }}</p>

    <div class="card space-y-6 text-sm leading-relaxed text-slate-200">
        <section>
            <h2 class="h2">1. Siapa kami</h2>
            <p class="mt-1">Guardian Digital adalah platform belajar keamanan digital berbentuk simulasi. Data pribadimu dikelola oleh
                <strong>{{ $controller }}</strong>@if ($contact), dapat dihubungi di <a class="text-guard-400 underline" href="mailto:{{ $contact }}">{{ $contact }}</a>@else, melalui admin sekolahmu atau pengelola aplikasi ini @endif.</p>
        </section>

        <section>
            <h2 class="h2">2. Data apa yang kami kumpulkan</h2>
            <ul class="mt-1 list-disc space-y-1 pl-6">
                <li><strong>Data akun:</strong> nama tampilan, email, kata sandi (disimpan dalam bentuk hash, tidak bisa dibaca), peran (siswa, orang tua, guru), sekolah, dan avatar.</li>
                <li><strong>Tanggal lahir siswa:</strong> hanya untuk menentukan apakah perlu persetujuan orang tua/wali.</li>
                <li><strong>Riwayat belajar:</strong> misi yang dimainkan, pilihan yang diambil, skor, XP, lencana, materi yang dibaca, dan aksi keamanan yang dicentang.</li>
                <li><strong>Data keamanan:</strong> catatan masuk/keluar, alamat IP, jenis perangkat/peramban, dan percobaan masuk yang gagal.</li>
                <li><strong>Hubungan kelas dan keluarga:</strong> kelas yang diikuti dan akun orang tua yang tertaut.</li>
            </ul>
            <p class="mt-2">Kami <strong>tidak</strong> meminta nama lengkap, nomor telepon, alamat rumah, foto, atau dokumen identitas.
                Semua skenario adalah simulasi: tidak ada uang atau data nyata yang dipakai.</p>
        </section>

        <section>
            <h2 class="h2">3. Untuk apa data dipakai</h2>
            <ul class="mt-1 list-disc space-y-1 pl-6">
                <li>Menjalankan akun, menyimpan progres, dan menampilkan papan peringkat.</li>
                <li>Menampilkan laporan progres kepada <strong>guru</strong> kelasmu dan <strong>orang tua/wali</strong> yang tertaut.</li>
                <li>Menjaga keamanan layanan (mendeteksi penyalahgunaan, mengamankan login, mengirim email verifikasi dan atur ulang kata sandi).</li>
            </ul>
            <p class="mt-2">Dasar pemrosesan adalah <strong>persetujuanmu</strong> (dan persetujuan orang tua/wali untuk pengguna di bawah {{ $age }} tahun).
                Data tidak dijual dan tidak dipakai untuk iklan.</p>
        </section>

        <section>
            <h2 class="h2">4. Anak di bawah {{ $age }} tahun</h2>
            <p class="mt-1">Siswa yang belum berusia {{ $age }} tahun baru bisa memakai Guardian Digital setelah orang tua/wali menautkan akunnya dan
                menyatakan persetujuan. Orang tua/wali yang memberi persetujuan dapat mengunduh data anak, mencabut persetujuan (akun anak terkunci),
                atau menghapus akun anak beserta datanya kapan saja.</p>
        </section>

        <section>
            <h2 class="h2">5. Siapa yang bisa melihat datamu</h2>
            <ul class="mt-1 list-disc space-y-1 pl-6">
                <li>Pengguna lain melihat <strong>nama tampilan, avatar, dan XP</strong> di papan peringkat. Karena itu, pakai nama panggilan.</li>
                <li>Guru melihat progres siswa di kelasnya; orang tua melihat progres anak yang tertaut; admin sekolah mengelola akun di sekolahnya.</li>
                <li>Pengelola sistem dapat mengakses data seperlunya untuk operasional dan keamanan.</li>
                <li>Penyedia infrastruktur (hosting, basis data, dan layanan email) memproses data atas nama kami untuk menjalankan layanan.</li>
            </ul>
        </section>

        <section>
            <h2 class="h2">6. Berapa lama data disimpan</h2>
            <p class="mt-1">Data akun dan riwayat belajar disimpan selama akunmu ada. Catatan aktivitas keamanan dihapus otomatis setelah masa simpan
                yang ditetapkan pengelola (bawaan 180 hari). Saat akun dihapus, data akun dan riwayat belajar ikut terhapus, dan catatan aktivitas lamamu dianonimkan.
                Salinan cadangan sistem dapat memuat data sampai siklus penggantian cadangan berikutnya.</p>
        </section>

        <section>
            <h2 class="h2">7. Hakmu</h2>
            <p class="mt-1">Kamu berhak mengakses, memperbaiki, mengunduh, menarik persetujuan, dan meminta penghapusan datamu. Setelah masuk, buka
                @auth <a class="text-guard-400 underline" href="{{ route('account.privacy') }}">Privasi &amp; Data Saya</a> @else menu <em>Privasi &amp; Data Saya</em> @endauth
                untuk mengunduh salinan data (JSON) atau menghapus akun. Untuk permintaan lain, hubungi kami lewat kontak di atas.</p>
        </section>

        <section>
            <h2 class="h2">8. Keamanan</h2>
            <p class="mt-1">Kami memakai koneksi terenkripsi (HTTPS), kata sandi ter-hash, pembatasan percobaan masuk, verifikasi email, dan verifikasi dua langkah wajib
                untuk akun admin. Tidak ada sistem yang 100% aman; jika terjadi kebocoran yang berdampak pada datamu, kami akan memberi tahu sesuai ketentuan yang berlaku.</p>
        </section>

        <section>
            <h2 class="h2">9. Perubahan kebijakan</h2>
            <p class="mt-1">Jika kebijakan ini berubah, versinya dinaikkan dan kamu akan diminta menyetujui ulang saat masuk berikutnya.</p>
        </section>
    </div>
</div>
@endsection
