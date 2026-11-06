@extends('layouts.app')
@section('title', 'Ganti Kata Sandi')

@section('content')
<div class="rise mx-auto max-w-md">
    <div class="mb-4 rounded-xl border border-warn/50 bg-warn/15 px-4 py-3 text-sm text-amber-100">
        <strong>⚠️ Kata sandimu perlu diperbarui.</strong><br>
        Kata sandi ini sementara atau belum memenuhi aturan keamanan terbaru. Demi keamanan akunmu, buat kata sandi baru sekarang. Halaman lain baru bisa dibuka setelah kata sandi diganti.
    </div>

    <div class="card">
        <h1 class="h1 mb-1">Buat kata sandi baru 🔐</h1>
        <p class="mb-5 text-sm text-slate-400">
            Minimal {{ auth()->user()->requiresTwoFactor() || \App\Rules\StrongPassword::privileged(auth()->user()->role) ? 12 : 8 }} karakter, mengandung huruf dan angka{{ \App\Rules\StrongPassword::privileged(auth()->user()->role) ? ' (huruf besar dan kecil)' : '' }}. Jangan bagikan kata sandi ini ke siapa pun.
        </p>

        <form method="POST" action="{{ route('password.force.update') }}" class="space-y-4">
            @csrf
            <div>
                <label class="label" for="password">Kata sandi baru</label>
                <input class="input" id="password" type="password" name="password" required autofocus autocomplete="new-password">
            </div>
            <div>
                <label class="label" for="password_confirmation">Ulangi kata sandi baru</label>
                <input class="input" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            </div>
            <button class="btn btn-primary w-full">Simpan kata sandi baru</button>
        </form>
    </div>
</div>
@endsection