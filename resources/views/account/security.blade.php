@extends('layouts.app')
@section('title', 'Keamanan Akun')

@section('content')
<div class="rise mx-auto max-w-2xl space-y-6">
    <div>
        <h1 class="h1">🔒 Keamanan Akun</h1>
        <p class="mt-1 text-sm text-slate-400">
            Kelola kata sandi dan verifikasi dua langkah.
            <a href="{{ route('account.privacy') }}" class="text-guard-400 hover:underline">Privasi &amp; data saya →</a>
        </p>
    </div>

    <div class="card">
        <h2 class="h2">Ganti kata sandi</h2>
        <p class="mt-1 text-sm text-slate-400">
            Minimal {{ $min }} karakter, mengandung huruf dan angka{{ \App\Rules\StrongPassword::privileged($user->role) ? ', serta huruf besar dan kecil' : '' }}.
            Perangkat lain akan dikeluarkan setelah kata sandi diganti.
        </p>
        <form method="POST" action="{{ route('account.password.update') }}" class="mt-4 space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="label" for="current_password">Kata sandi sekarang</label>
                <input class="input" id="current_password" type="password" name="current_password" required autocomplete="current-password">
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="password">Kata sandi baru</label>
                    <input class="input" id="password" type="password" name="password" required autocomplete="new-password">
                </div>
                <div>
                    <label class="label" for="password_confirmation">Ulangi kata sandi baru</label>
                    <input class="input" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                </div>
            </div>
            <button class="btn btn-primary">Simpan kata sandi baru</button>
        </form>
    </div>

    <div class="card">
        <h2 class="h2">Verifikasi dua langkah</h2>
        <p class="mt-1 text-sm text-slate-400">
            Selain kata sandi, kamu memasukkan kode 6 digit dari aplikasi autentikator (Google Authenticator, Authy, Microsoft Authenticator).
            @if ($twoFactorRequired) <strong>Wajib untuk akun {{ $user->role === 'superadmin' ? 'superadmin' : 'admin sekolah' }}.</strong> @endif
        </p>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            @if ($twoFactorEnabled)
                <span class="chip text-emerald-300">✅ Aktif</span>
                <a href="{{ route('two-factor.setup') }}" class="btn btn-ghost">Kelola &amp; kode pemulihan</a>
            @else
                <span class="chip text-amber-300">Belum aktif</span>
                <a href="{{ route('two-factor.setup') }}" class="btn btn-primary">Aktifkan sekarang</a>
            @endif
        </div>
    </div>
</div>
@endsection
