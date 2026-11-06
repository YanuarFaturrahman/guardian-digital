@extends('layouts.app')
@section('title', 'Menunggu Persetujuan Orang Tua')

@section('content')
<div class="rise mx-auto max-w-lg">
    <div class="card">
        <h1 class="h1 mb-1">Menunggu persetujuan orang tua/wali 👪</h1>
        <p class="mb-4 text-sm text-slate-300">
            Karena kamu belum berusia {{ config('privacy.consent_age') }} tahun, data pribadimu hanya boleh diproses
            setelah orang tua atau walimu menyetujuinya. Caranya:
        </p>

        <ol class="mb-4 list-decimal space-y-2 pl-5 text-sm text-slate-200">
            <li>Minta orang tua/walimu mendaftar di Guardian Digital sebagai <strong>Orang Tua</strong>.</li>
            <li>Di halaman <strong>Pantau Anak</strong>, mereka memasukkan Kode Tautanmu dan mencentang pernyataan persetujuan.</li>
            <li>Setelah itu halaman ini otomatis terbuka ke misi-misimu.</li>
        </ol>

        <div class="mb-4 rounded-xl bg-white/5 p-4 text-center">
            <div class="text-xs text-slate-400">Kode Tautanmu</div>
            <div class="select-all font-mono text-2xl font-bold tracking-widest">{{ $user->link_code }}</div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Cek lagi</a>
            <a href="{{ route('account.privacy') }}" class="btn btn-ghost">Privasi &amp; data saya</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost">Keluar</button></form>
        </div>
    </div>
</div>
@endsection
