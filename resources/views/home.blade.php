@extends('layouts.app')
@section('title', 'Jadilah Guardian Digital')

@section('content')
<section class="rise py-10 text-center md:py-16">
    <span class="chip mb-4">🛡️ Simulasi aman untuk siswa SMA</span>
    <h1 class="mx-auto max-w-3xl text-4xl font-extrabold leading-tight md:text-6xl">
        Jadilah <span class="text-guard-500">Guardian</span> di Ruang Digital
    </h1>
    <p class="mx-auto mt-4 max-w-2xl text-slate-300">
        Hadapi penipuan, investasi bodong, hoaks, dan kebocoran data lewat misi bergaya game.
        Pilih tindakanmu, rasakan akibatnya tanpa risiko nyata, lalu belajar mengenali tanda bahaya.
    </p>
    <div class="mt-8 flex flex-wrap justify-center gap-3">
        @auth
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Masuk ke Dashboard</a>
        @else
            <a href="{{ route('register') }}" class="btn btn-primary">Mulai Petualangan</a>
            <a href="{{ route('login') }}" class="btn btn-ghost">Saya sudah punya akun</a>
        @endauth
    </div>
    <p class="mt-4 text-xs text-slate-400">{{ $scenarioCount }} misi tersedia ·
{{ $studentCount }} siswa bergabung</p>
</section>

<section class="grid gap-4 md:grid-cols-3">
    @foreach ([
        ['👁️', 'Recognize', 'Kenali', 'Mainkan misi cerita bercabang dan temukan tanda bahaya (red flag) di setiap situasi.'],
        ['📚', 'Learn', 'Pelajari', 'Pahami cara kerja jebakan lewat materi singkat, ulasan keputusan, dan daftar red flag.'],
        ['⚡', 'Act', 'Lindungi', 'Tahu langkah JEDA-CEK-LAPOR-BAGIKAN dan lakukan aksi keamanan nyata.'],
    ] as [$icon, $en, $id, $text])
        <div class="card text-center">
            <div class="text-4xl">{{ $icon }}</div>
            <div class="mt-2 text-xs uppercase tracking-widest text-guard-400">{{ $en }}</div>
            <div class="h2">{{ $id }}</div>
            <p class="mt-2 text-sm text-slate-300">{{ $text }}</p>
        </div>
    @endforeach
</section>

@php
    $numberWords = [
        1 => 'Satu', 2 => 'Dua', 3 => 'Tiga', 4 => 'Empat', 5 => 'Lima',
        6 => 'Enam', 7 => 'Tujuh', 8 => 'Delapan', 9 => 'Sembilan', 10 => 'Sepuluh',
    ];
    $total = $categories->count();
    $totalText = $numberWords[$total] ?? $total;
@endphp

@if ($total > 0)
<section class="mt-12">
    <h2 class="h1 mb-1 text-center">{{ $totalText }} Jebakan yang Akan Kamu Hadapi</h2>
    <p class="mb-6 text-center text-sm text-slate-400">Setiap kategori punya misi dan materi sendiri.</p>

    <div class="flex flex-wrap justify-center gap-4">
        @foreach ($categories as $category)
            <div class="card w-full sm:w-[calc(50%-0.5rem)] lg:w-[calc(25%-0.75rem)]"
                 style="border-color: {{ $category->color }}55">
                <div class="text-3xl">{{ $category->icon }}</div>
                <div class="mt-2 font-semibold">{{ $category->name }}</div>
                <div class="text-xs" style="color: {{ $category->color }}">{{ $category->tagline }}</div>
                <p class="mt-2 text-sm text-slate-300">{{ \Illuminate\Support\Str::limit($category->description, 180) }}</p>
            </div>
        @endforeach
    </div>
</section>
@endif

<section class="mt-12 grid gap-4 md:grid-cols-3">
    <div class="card"><div class="text-2xl">🏅</div><div class="mt-1 font-semibold">Guardian Badge dan Peringkat</div>
        <p class="mt-1 text-sm text-slate-300">Kumpulkan XP, naik level, dan berlomba menjadi pelindung terbaik di sekolahmu.</p></div>
    <div class="card"><div class="text-2xl">👨‍👩‍👧</div><div class="mt-1 font-semibold">Terhubung dengan Keluarga</div>
        <p class="mt-1 text-sm text-slate-300">Orang tua dan guru memantau progres dan mendapat tips untuk mengobrol, bukan menghakimi.</p></div>
    <div class="card"><div class="text-2xl">🧪</div><div class="mt-1 font-semibold">Aman untuk Salah</div>
        <p class="mt-1 text-sm text-slate-300">Semua skenario adalah simulasi. Boleh salah di sini supaya tidak salah di dunia nyata.</p></div>
</section>
@endsection
