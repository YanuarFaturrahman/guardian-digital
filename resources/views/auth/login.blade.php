@extends('layouts.app')
@section('title', 'Masuk')

@section('content')
<div class="rise mx-auto max-w-md">
    <div class="card">
        <h1 class="h1 mb-1">Selamat datang kembali 👋</h1>
        <p class="mb-5 text-sm text-slate-400">Masuk untuk melanjutkan misimu.</p>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="label" for="email">Email</label>
                <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div>
                <label class="label" for="password">Kata sandi</label>
                <input class="input" id="password" type="password" name="password" required>
            </div>
            <div class="flex items-center justify-between">
    <label class="flex items-center gap-2 text-sm text-slate-300">
        <input type="checkbox" name="remember" class="rounded"> Ingat saya
    </label>
    <a href="{{ route('password.request') }}" class="text-sm text-guard-400 hover:underline">Lupa kata sandi?</a>
</div>
            <button class="btn btn-primary w-full">Masuk</button>
        </form>

        <p class="mt-5 text-center text-sm text-slate-400">
            Belum punya akun? <a href="{{ route('register') }}" class="text-guard-400 hover:underline">Daftar</a>
        </p>
    </div>
</div>
@endsection
