@extends('layouts.app')
@section('title', 'Atur Ulang Kata Sandi')

@section('content')
<div class="rise mx-auto max-w-md">
    <div class="card">
        <h1 class="h1 mb-1">Buat kata sandi baru 🔒</h1>
        <p class="mb-5 text-sm text-slate-400">Pilih kata sandi yang kuat dan belum pernah kamu pakai di tempat lain.</p>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label class="label" for="email">Email</label>
                <input class="input opacity-70" id="email" type="email" name="email"
                       value="{{ old('email', $email) }}" required readonly>
            </div>
            <div>
                <label class="label" for="password">Kata sandi baru</label>
                <input class="input" id="password" type="password" name="password" required autofocus>
            </div>
            <div>
                <label class="label" for="password_confirmation">Ulangi kata sandi baru</label>
                <input class="input" id="password_confirmation" type="password" name="password_confirmation" required>
            </div>
            <p class="text-xs text-slate-400">Minimal 8 karakter, mengandung huruf dan angka.</p>

            <button class="btn btn-primary w-full">Simpan kata sandi baru</button>
        </form>
    </div>
</div>
@endsection