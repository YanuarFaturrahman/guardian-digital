@extends('layouts.app')
@section('title', 'Persetujuan Orang Tua / Wali')

@section('content')
<div class="rise mx-auto max-w-xl">
    <div class="card">
        <h1 class="h1 mb-1">👪 Persetujuan Orang Tua / Wali</h1>
        <p class="mb-4 text-sm text-slate-400">
            <strong class="text-slate-200">{{ $student->avatar }} {{ $student->name }}</strong> mendaftar di Guardian Digital,
            platform belajar keamanan digital berbentuk simulasi. Karena berusia di bawah 18 tahun, akunnya memerlukan persetujuan Anda.
        </p>

        <div class="mb-4 rounded-xl border border-white/10 bg-white/5 p-4 text-sm text-slate-300">
            <div class="mb-2 font-semibold text-slate-100">Data anak yang kami simpan</div>
            <ul class="list-disc space-y-1 pl-5">
                <li>Nama panggilan, email, avatar, sekolah, dan tanggal lahir</li>
                <li>Progres belajar: misi, pilihan, skor, XP, lencana, dan materi yang dibaca</li>
                <li>Catatan teknis keamanan (alamat IP dan perangkat) saat masuk</li>
            </ul>
            <p class="mt-2 text-xs text-slate-400">Data tidak dijual dan tidak dipakai untuk iklan. Anda dapat mencabut persetujuan, mengunduh, atau menghapus data anak kapan saja lewat akun Orang Tua.</p>
        </div>

        <a href="{{ route('privacy.policy') }}" target="_blank" rel="noopener" class="text-sm text-guard-400 hover:underline">
            Baca Kebijakan Privasi lengkap ↗
        </a>

        @if ($already)
            <div class="mt-5 rounded-xl border border-safe/40 bg-safe/15 px-4 py-3 text-sm text-emerald-200">
                ✅ Persetujuan untuk {{ $student->name }} sudah diberikan. Anda tidak perlu melakukan apa pun lagi.
            </div>
            <p class="mt-3 text-xs text-slate-400">
                Untuk mencabut persetujuan, daftar sebagai <strong>Orang Tua</strong>, tautkan akun anak dengan Kode Tautannya,
                lalu cabut dari halaman laporan anak. Atau hubungi {{ config('privacy.contact_email') }}.
            </p>
        @else
            <form method="POST" action="{{ request()->fullUrl() }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="label" for="parent_name">Nama lengkap Anda</label>
                    <input class="input" id="parent_name" name="parent_name" required maxlength="80" value="{{ old('parent_name') }}">
                </div>

                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" name="accept" value="1" class="mt-1 h-4 w-4 accent-cyan-400" required>
                    <span>
                        Saya adalah orang tua atau wali sah dari {{ $student->name }}, telah membaca Kebijakan Privasi,
                        dan menyetujui pemrosesan data pribadi anak saya untuk layanan Guardian Digital.
                    </span>
                </label>

                <button class="btn btn-primary w-full">Saya setuju</button>
            </form>
        @endif
    </div>
</div>
@endsection
