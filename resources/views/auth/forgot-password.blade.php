@extends('layouts.app')
@section('title', 'Lupa Kata Sandi')

@section('content')
<div class="rise mx-auto max-w-md">
    <div class="card">
        <h1 class="h1 mb-1">Lupa kata sandi? 🔑</h1>
        <p class="mb-5 text-sm text-slate-400">
            Masukkan email akunmu. Kami akan mengirim tautan untuk membuat kata sandi baru.
        </p>

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label class="label" for="email">Email</label>
                <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            <button class="btn btn-primary w-full">Kirim tautan reset</button>
        </form>

        <p class="mt-5 text-center text-sm text-slate-400">
            <a href="{{ route('login') }}" class="text-guard-400 hover:underline">← Kembali ke halaman masuk</a>
        </p>
    </div>
</div>
@endsection