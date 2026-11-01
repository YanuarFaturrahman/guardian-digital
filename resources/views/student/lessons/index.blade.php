@extends('layouts.app')
@section('title', 'Belajar')

@section('content')
<div class="rise">
    <h1 class="h1">Ruang Belajar</h1>
    <p class="mb-6 text-sm text-slate-400">Materi singkat 3-4 menit. Setiap materi yang selesai memberi XP.</p>

    @foreach ($categories as $category)
        <section class="mb-8">
            <h2 class="h2 mb-3" style="color: {{ $category->color }}">{{ $category->icon }} {{ $category->name }}</h2>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($category->lessons as $lesson)
                    <a href="{{ route('student.lessons.show', $lesson) }}" class="card block transition hover:bg-white/10">
                        <div class="flex items-start justify-between gap-2">
                            <div class="font-semibold">{{ $lesson->title }}</div>
                            @if ($readIds->contains($lesson->id))
                                <span class="chip text-safe">✓ Selesai</span>
                            @else
                                <span class="chip">+{{ $lesson->xp_reward }} XP</span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-slate-300">{{ $lesson->summary }}</p>
                        <div class="mt-2 text-xs text-slate-400">⏱ {{ $lesson->read_minutes }} menit baca</div>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
@endsection
