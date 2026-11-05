@extends('layouts.app')
@section('title', 'Persetujuan Privasi')

@section('content')
<div class="rise mx-auto max-w-xl">

    @if (in_array($gate, ['policy', 'birth_date'], true))
        <div class="card">
            <h1 class="h1 mb-1">🔒 Sebelum lanjut</h1>
            <p class="mb-4 text-sm text-slate-400">
                Kami memperbarui cara kami menjelaskan penggunaan datamu. Baca ringkasannya, lalu setujui untuk melanjutkan.
            </p>

            <ul class="mb-4 list-disc space-y-1 pl-6 text-sm text-slate-300">
                <li>Kami menyimpan data akun dan progres belajarmu untuk menjalankan Guardian Digital.</li>
                <li>Nama tampilan dan skormu bisa terlihat di papan peringkat. Pakai nama panggilan.</li>
                <li>Data tidak dijual dan tidak dipakai untuk iklan.</li>
                <li>Kamu bisa mengunduh atau menghapus datamu kapan saja di menu <strong>Privasi Akun</strong>.</li>
            </ul>

            <a href="{{ route('privacy.policy') }}" target="_blank" rel="noopener" class="text-sm text-guard-400 hover:underline">
                Baca Kebijakan Privasi lengkap (versi {{ config('privacy.policy_version') }}) ↗
            </a>

            <form method="POST" action="{{ route('consent.accept') }}" class="mt-5 space-y-4">
                @csrf

                @if ($user->role === 'student' && $user->birth_date === null)
                    <div>
                        <label class="label" for="birth_date">Tanggal lahirmu</label>
                        <input class="input max-w-xs" type="date" id="birth_date" name="birth_date" required
                               value="{{ old('birth_date') }}" max="{{ now()->subDay()->toDateString() }}">
                        <p class="mt-1 text-xs text-slate-400">
                            Hanya untuk menentukan apakah orang tua / walimu perlu ikut menyetujui (jika usiamu di bawah 18 tahun).
                            Tanggal lahir tidak bisa diubah sendiri setelah disimpan.
                        </p>
                    </div>
                @endif

                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" name="accept" value="1" class="mt-1 h-4 w-4 accent-cyan-400" required>
                    <span>Saya sudah membaca dan menyetujui Kebijakan Privasi Guardian Digital.</span>
                </label>

                <button class="btn btn-primary w-full">Setuju dan lanjutkan</button>
            </form>
        </div>

    @else
        {{-- gate = parent: menunggu persetujuan orang tua / wali --}}
        <div class="card">
            <h1 class="h1 mb-1">👪 Menunggu persetujuan orang tua / wali</h1>
            <p class="mb-4 text-sm text-slate-400">
                Karena usiamu di bawah 18 tahun, akunmu baru bisa dipakai setelah orang tua atau walimu menyetujui. Pilih salah satu cara:
            </p>

            <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                <h2 class="h2 mb-1 text-base">Cara 1: Kirim tautan ke email orang tua</h2>
                <p class="mb-3 text-xs text-slate-400">Orang tuamu tidak perlu punya akun. Tautan berlaku {{ config('privacy.consent_link_days') }} hari.</p>
                <form method="POST" action="{{ route('consent.request-parent') }}" class="flex flex-wrap gap-2">
                    @csrf
                    <input class="input max-w-xs flex-1" type="email" name="parent_email" required maxlength="120"
                           placeholder="email.orangtua@contoh.com" value="{{ old('parent_email', $user->parent_email) }}">
                    <button class="btn btn-primary">{{ $user->parent_email ? 'Kirim ulang' : 'Kirim tautan' }}</button>
                </form>
                @if ($user->parent_email)
                    <p class="mt-2 text-xs text-slate-400">Terakhir dikirim ke {{ $user->parent_email }}.</p>
                @endif
            </div>

            <div class="mt-3 rounded-xl border border-white/10 bg-white/5 p-4">
                <h2 class="h2 mb-1 text-base">Cara 2: Orang tua memakai akun sendiri</h2>
                <p class="text-xs text-slate-400">
                    Minta orang tuamu mendaftar sebagai <strong>Orang Tua</strong>, lalu memasukkan Kode Tautanmu di menu Pantau Anak.
                    Setelah itu ia bisa menyetujui dari halaman laporanmu.
                </p>
                <div class="mt-2 flex items-center gap-2">
                    <span class="select-all font-mono text-lg tracking-wider text-guard-400">{{ $user->link_code }}</span>
                    <button type="button" class="btn btn-ghost !px-3 !py-1 text-xs" data-copy="{{ $user->link_code }}">Salin</button>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <a href="{{ route('consent.show') }}" class="btn btn-ghost">Sudah disetujui? Cek lagi</a>
            </div>
        </div>
    @endif

    <div class="mt-4 flex flex-wrap items-center justify-center gap-4 text-xs text-slate-400">
        <a href="{{ route('privacy.policy') }}" class="hover:text-white">Kebijakan Privasi</a>
        <a href="{{ route('privacy.account') }}" class="hover:text-white">Privasi Akun (unduh / hapus data)</a>
        <form method="POST" action="{{ route('logout') }}" class="inline">@csrf <button class="hover:text-white">Keluar</button></form>
    </div>
</div>
@endsection
