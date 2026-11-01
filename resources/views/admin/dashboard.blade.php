@extends('layouts.app')
@section('title', 'Admin')

@section('content')
@php
    $roleLabels = [
        'student' => 'siswa', 'parent' => 'orang tua', 'teacher' => 'guru',
        'school_admin' => 'admin sekolah', 'superadmin' => 'superadmin',
    ];
@endphp
<div class="rise mx-auto max-w-6xl">
    <h1 class="h1">🛠️ Ringkasan Platform</h1>

    <div class="my-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <x-stat label="Siswa" :value="$counts->get('student', 0)" icon="🎓" />
        <x-stat label="Orang tua" :value="$counts->get('parent', 0)" icon="👪" />
        <x-stat label="Guru" :value="$counts->get('teacher', 0)" icon="🧑‍🏫" />
        <a href="{{ route('users.index', ['role' => 'school_admin']) }}" class="block transition hover:-translate-y-0.5">
            <x-stat label="Admin sekolah" :value="$counts->get('school_admin', 0)" icon="🏫" />
        </a>
        <x-stat label="Misi diselesaikan" :value="$plays" icon="🎯" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card">
            <h2 class="h2">Rata-rata skor semua misi</h2>
            <div class="mt-3 text-4xl font-bold text-guard-400">{{ $avgScore }}<span class="text-lg text-slate-400">/100</span></div>
            <a href="{{ route('admin.scenarios.index') }}" class="btn btn-ghost mt-4">Kelola skenario →</a>
        </div>

        <div class="card">
            <h2 class="h2 mb-2">Pengguna Terbaru</h2>
            @foreach ($recentUsers as $u)
                <div class="flex justify-between border-b border-white/10 py-2 text-sm last:border-0">
                    <span>{{ $u->avatar }} {{ $u->name }}</span>
                    <span class="text-xs text-slate-400">{{ $roleLabels[$u->role] ?? $u->role }} · {{ $u->created_at->diffForHumans() }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection