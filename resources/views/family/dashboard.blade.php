@extends('layouts.app')
@section('title', 'Pantau Anak')

@section('content')
<div class="rise mx-auto max-w-4xl">
    <h1 class="h1">👪 Pantau Perjalanan Anak</h1>
    <p class="mb-6 text-sm text-slate-400">Lihat progres belajar anak sebagai bahan obrolan hangat, bukan untuk menghakimi.</p>

    <div class="card mb-6">
        <h2 class="h2">Hubungkan akun anak</h2>
        <p class="mt-1 text-sm text-slate-400">Minta anakmu membuka Beranda Siswa dan menunjukkan <strong>Kode Tautan</strong> (contoh: GD-AB12CD).</p>
        <form method="POST" action="{{ route('family.link') }}" class="mt-3 flex flex-wrap gap-2">
            @csrf
            <input class="input max-w-xs" name="code" placeholder="GD-XXXXXX" required maxlength="12">
            <button class="btn btn-primary">Hubungkan</button>
        </form>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($rows as $row)
            @php
                $child = $row['child'];
                $summary = $row['summary'];
                $level = $row['level'];
            @endphp
            <a href="{{ route('family.child', $child) }}" class="card block transition hover:bg-white/10">
                <div class="flex items-center gap-4">
                    <div class="text-5xl">{{ $child->avatar }}</div>
                    <div>
                        <div class="h2">{{ $child->name }}</div>
                        <div class="text-xs text-slate-400">Level {{ $level['level'] }} ·
{{ $level['title'] }} · ⭐ {{ $child->xp }} XP</div>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-3 gap-2 text-center text-sm">
                    <div class="rounded-xl bg-white/5 p-2"><div class="font-bold">{{ $summary['scenarios_done'] }}</div><div class="text-xs text-slate-400">Misi</div></div>
                    <div class="rounded-xl bg-white/5 p-2"><div class="font-bold">{{ $summary['avg_score'] }}</div><div class="text-xs text-slate-400">Rata-rata</div></div>
                    <div class="rounded-xl bg-white/5 p-2"><div class="font-bold">{{ $summary['badges'] }}</div><div class="text-xs text-slate-400">Lencana</div></div>
                </div>
                <div class="mt-3 text-xs text-slate-400">
                    Terakhir aktif: {{ $summary['last_active'] ? \Illuminate\Support\Carbon::parse($summary['last_active'])->diffForHumans() : 'belum pernah' }}
                </div>
            </a>
        @empty
            <p class="card text-sm text-slate-400 md:col-span-2">Belum ada anak yang tertaut. Masukkan kode tautan di atas.</p>
        @endforelse
    </div>
</div>
@endsection
