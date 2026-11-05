@extends('layouts.app')
@section('title', 'Persetujuan Tersimpan')

@section('content')
<div class="rise mx-auto max-w-xl py-8 text-center">
    <div class="text-6xl">✅</div>
    <h1 class="h1 mt-4">Terima kasih</h1>
    <p class="mt-3 text-sm text-slate-300">
        Persetujuan Anda untuk akun <strong>{{ $student->name }}</strong> sudah tersimpan.
        Minta anak Anda membuka halaman Guardian Digital dan menekan "Sudah disetujui? Cek lagi".
    </p>
    <p class="mt-4 text-xs text-slate-400">
        Ingin memantau progres belajarnya? Daftar sebagai <strong>Orang Tua</strong> dan masukkan Kode Tautan anak.
    </p>
</div>
@endsection
