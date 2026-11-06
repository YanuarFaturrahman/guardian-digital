@extends('layouts.app')
@section('title', 'Verifikasi Dua Langkah')

@section('content')
<div class="rise mx-auto max-w-2xl space-y-6">
    <div>
        <a href="{{ route('account.security') }}" class="text-sm text-slate-400 hover:text-white">← Keamanan akun</a>
        <h1 class="h1 mt-2">🔐 Verifikasi dua langkah</h1>
    </div>

    @if ($recoveryCodes)
        <div class="card border-warn/50">
            <h2 class="h2">Simpan kode pemulihan ini</h2>
            <p class="mt-1 text-sm text-amber-100">
                Kode ini hanya tampil sekali. Pakai kalau ponselmu hilang. Setiap kode hanya berlaku satu kali.
                Simpan di tempat aman (pengelola kata sandi atau dicetak), jangan di ponsel yang sama.
            </p>
            <div class="mt-3 grid grid-cols-2 gap-2 font-mono text-sm">
                @foreach ($recoveryCodes as $code)
                    <div class="rounded-lg bg-white/5 px-3 py-2">{{ $code }}</div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($enabled)
        <div class="card">
            <div class="flex flex-wrap items-center gap-3">
                <span class="chip text-emerald-300">✅ Aktif</span>
                <span class="text-sm text-slate-300">Sisa kode pemulihan: <strong>{{ $left }}</strong></span>
            </div>

            <form method="POST" action="{{ route('two-factor.recovery') }}" class="mt-5 space-y-3">
                @csrf
                <h2 class="h2">Buat ulang kode pemulihan</h2>
                <p class="text-sm text-slate-400">Kode lama langsung tidak berlaku.</p>
                <input class="input max-w-xs" type="password" name="password" placeholder="Kata sandimu" required autocomplete="current-password">
                <div><button class="btn btn-ghost">Buat kode baru</button></div>
            </form>

            @unless ($required)
                <form method="POST" action="{{ route('two-factor.disable') }}" class="mt-6 space-y-3 border-t border-white/10 pt-5"
                      onsubmit="return confirm('Matikan verifikasi dua langkah?')">
                    @csrf @method('DELETE')
                    <h2 class="h2">Matikan verifikasi dua langkah</h2>
                    <input class="input max-w-xs" type="password" name="password" placeholder="Kata sandimu" required autocomplete="current-password">
                    <div><button class="btn btn-ghost text-danger">Matikan</button></div>
                </form>
            @endunless
        </div>
    @else
        <div class="card">
            @if ($required)
                <div class="mb-4 rounded-xl border border-warn/50 bg-warn/15 px-4 py-3 text-sm text-amber-100">
                    Akun admin wajib memakai verifikasi dua langkah. Selesaikan pemasangan ini untuk membuka menu lainnya.
                </div>
            @endif

            <ol class="list-decimal space-y-4 pl-5 text-sm text-slate-200">
                <li>
                    Pasang aplikasi autentikator di ponselmu (Google Authenticator, Authy, atau Microsoft Authenticator).
                </li>
                <li>
                    Tambahkan akun baru lewat <strong>"Masukkan kunci setelan"</strong> (setup key), lalu ketik kunci ini
                    (jenis: berbasis waktu):
                    <div class="mt-2 select-all break-all rounded-xl bg-white/5 px-4 py-3 font-mono text-base tracking-wider">{{ $secret }}</div>
                    <p class="mt-2 text-xs text-slate-400">
                        Membuka halaman ini di ponsel yang sama? <a class="text-guard-400 underline" href="{{ $uri }}">Tambahkan otomatis ke aplikasi autentikator</a>.
                        Jangan bagikan kunci ini ke siapa pun.
                    </p>
                </li>
                <li>Masukkan kode 6 digit yang tampil di aplikasi untuk mengonfirmasi:</li>
            </ol>

            <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-3 flex flex-wrap items-center gap-3">
                @csrf
                <input class="input max-w-[11rem] text-center text-xl tracking-widest" name="code" inputmode="numeric"
                       autocomplete="one-time-code" maxlength="8" placeholder="123456" required>
                <button class="btn btn-primary">Aktifkan</button>
            </form>
        </div>
    @endif
</div>
@endsection
