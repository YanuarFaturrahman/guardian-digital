@extends('layouts.app')
@section('title', 'Verifikasi Email')

@section('content')
<div class="rise mx-auto max-w-md">
    <div class="card">
        <h1 class="h1 mb-1">Periksa emailmu 📬</h1>
        <p class="mb-4 text-sm text-slate-300">
            Kami mengirim tautan verifikasi ke <strong>{{ auth()->user()->email }}</strong>.
            Klik tautan itu untuk mulai memakai Guardian Digital. Cek juga folder spam.
        </p>

        <form method="POST" action="{{ route('verification.send') }}" class="mb-3">
            @csrf
            <button class="btn btn-primary w-full">Kirim ulang tautan verifikasi</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-ghost w-full">Keluar</button>
        </form>

        <p class="mt-4 text-xs text-slate-400">
            Salah memasukkan email? Keluar lalu daftar ulang dengan email yang benar, atau minta bantuan admin sekolahmu.
        </p>
    </div>
</div>
@endsection
