@extends('layouts.app')
@section('title', 'Kelas Saya')

@section('content')
<div class="rise mx-auto max-w-4xl">
    <h1 class="h1">🧑‍🏫 Kelas Saya</h1>
    <p class="mb-6 text-sm text-slate-400">Buat kelas, bagikan kode kelas kepada siswa, lalu pantau progres mereka.</p>

    <div class="card mb-6">
        <h2 class="h2">Buat kelas baru</h2>
        <form method="POST" action="{{ route('teacher.classes.store') }}" class="mt-3 flex flex-wrap gap-2">
            @csrf
            <input class="input max-w-sm" name="name" placeholder="Contoh: XI IPA 1" required maxlength="80">
            <button class="btn btn-primary">Buat kelas</button>
        </form>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($classrooms as $class)
            <div class="card">
                <div class="h2">{{ $class->name }}</div>
                <div class="text-xs text-slate-400">{{ $class->students_count }} siswa</div>
                <div class="mt-3 flex items-center justify-between rounded-xl bg-guard-900 px-4 py-2">
                    <span class="font-mono tracking-widest text-guard-400">{{ $class->join_code }}</span>
                    <button type="button" class="btn btn-ghost !px-3 !py-1 text-xs" data-copy="{{ $class->join_code }}">Salin kode</button>
                </div>
                <a href="{{ route('teacher.classes.show', $class) }}" class="btn btn-ghost mt-3 w-full">Buka kelas →</a>
            </div>
        @empty
            <p class="card text-sm text-slate-400 md:col-span-2">Belum ada kelas. Buat kelas pertamamu di atas.</p>
        @endforelse
    </div>
</div>
@endsection
