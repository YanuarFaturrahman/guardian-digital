@extends('layouts.app')
@section('title', $lesson->title)

@section('content')
<div class="rise mx-auto max-w-2xl">
    <a href="{{ route('student.lessons.index') }}" class="text-sm text-slate-400 hover:text-white">← Semua materi</a>

    <article class="card mt-3">
        <span class="chip" style="color: {{ $lesson->category->color }}">{{ $lesson->category->icon }} {{ $lesson->category->name }}</span>
        <h1 class="h1 mt-3">{{ $lesson->title }}</h1>
        <div class="mb-4 text-xs text-slate-400">⏱ {{ $lesson->read_minutes }} menit baca</div>

        <div class="lesson-body">{!! \Illuminate\Support\Str::markdown($lesson->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
        <div class="mt-6 border-t border-white/10 pt-4">
            @if ($isRead)
                <span class="chip text-safe">✓ Materi ini sudah kamu selesaikan</span>
            @else
                <form method="POST" action="{{ route('student.lessons.complete', $lesson) }}">
                    @csrf
                    <button class="btn btn-primary">Tandai selesai (+{{ $lesson->xp_reward }} XP)</button>
                </form>
            @endif
        </div>
    </article>

    @if ($scenarios->isNotEmpty())
        <div class="card mt-4">
            <h2 class="h2">Praktikkan sekarang</h2>
            <div class="mt-2 space-y-2">
                @foreach ($scenarios as $scenario)
                    <a href="{{ route('student.scenarios.show', $scenario) }}" class="block rounded-xl bg-white/5 p-3 text-sm hover:bg-white/10">
                        🎮 {{ $scenario->title }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
