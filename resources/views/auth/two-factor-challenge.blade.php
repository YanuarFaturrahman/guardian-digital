@extends('layouts.app')
@section('title', 'Verifikasi Dua Langkah')

@section('content')
<div class="rise mx-auto max-w-md">
    <div class="card">
        <h1 class="h1 mb-1">Verifikasi dua langkah 🔐</h1>
        <p class="mb-5 text-sm text-slate-400">Buka aplikasi autentikator di ponselmu, lalu masukkan kode 6 digit untuk akun ini.</p>

        <form method="POST" action="{{ route('two-factor.challenge.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="label" for="code">Kode dari aplikasi</label>
                <input class="input text-center text-xl tracking-widest" id="code" name="code" inputmode="numeric"
                       autocomplete="one-time-code" maxlength="8" autofocus placeholder="123456">
            </div>
            <button class="btn btn-primary w-full">Verifikasi</button>
        </form>

        <details class="mt-6 text-sm">
            <summary class="cursor-pointer text-guard-400">Kehilangan ponsel? Pakai kode pemulihan</summary>
            <form method="POST" action="{{ route('two-factor.challenge.store') }}" class="mt-3 space-y-3">
                @csrf
                <input class="input font-mono" name="recovery_code" placeholder="xxxxx-xxxxx" maxlength="16" autocomplete="off">
                <button class="btn btn-ghost w-full">Masuk dengan kode pemulihan</button>
                <p class="text-xs text-slate-400">Setiap kode pemulihan hanya bisa dipakai sekali.</p>
            </form>
        </details>

        <p class="mt-5 text-center text-sm text-slate-400">
            <a href="{{ route('login') }}" class="text-guard-400 hover:underline">← Kembali ke halaman masuk</a>
        </p>
    </div>
</div>
@endsection
