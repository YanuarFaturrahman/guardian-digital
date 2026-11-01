@extends('layouts.app')
@section('title', 'Ringkasan Sekolah')

@section('content')
<div class="rise mx-auto max-w-5xl">
    <h1 class="h1">🏫 {{ $school->name }}</h1>
    <p class="text-sm text-slate-400">Ringkasan sekolah yang kamu koordinasi.</p>

    <div class="my-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Siswa" :value="$studentCount" icon="🎓" />
        <x-stat label="Guru" :value="$teacherCount" icon="🧑‍🏫" />
        <x-stat label="Orang tua" :value="$parentCount" icon="👪" />
        <x-stat label="Kelas" :value="$classCount" icon="👥" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card">
            <h2 class="h2">Rata-rata skor siswa sekolah</h2>
            <div class="mt-3 text-4xl font-bold text-guard-400">{{ $avgScore }}<span class="text-lg text-slate-400">/100</span></div>
            <p class="mt-1 text-sm text-slate-400">{{ $plays }} misi diselesaikan</p>
            <a href="{{ route('users.index') }}" class="btn btn-ghost mt-4">Kelola pengguna →</a>
        </div>

        <div class="card">
            <h2 class="h2 mb-2">Pengguna Terbaru</h2>
            @forelse ($recentUsers as $u)
                <div class="flex justify-between border-b border-white/10 py-2 text-sm last:border-0">
                    <span>{{ $u->avatar }} {{ $u->name }}</span>
                    <span class="text-xs text-slate-400">{{ $u->role }} · {{ $u->created_at->diffForHumans() }}</span>
                </div>
            @empty
                <p class="text-sm text-slate-400">Belum ada pengguna di sekolah ini.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection