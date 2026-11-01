@extends('layouts.app')
@section('title', 'Misi')
 
@section('content')
<div class="rise">
    <h1 class="h1">Pilih Misimu</h1>
    <p class="mb-6 text-sm text-slate-400">Setiap misi adalah cerita pendek. Tidak ada risiko nyata, jadi berani mencoba!</p>
 
    @foreach ($categories as $category)
        @if ($category->scenarios->isNotEmpty())
            <section class="mb-8">
                <div class="mb-3 flex items-center gap-3">
                    <span class="text-3xl">{{ $category->icon }}</span>
                    <div>
                        <h2 class="h2" style="color: {{ $category->color }}">{{ $category->name }}</h2>
                        <div class="text-xs text-slate-400">{{ $category->tagline }}</div>
                    </div>
                </div>
 
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($category->scenarios as $scenario)
                        @php $score = $best->get($scenario->id); @endphp
                        <a href="{{ route('student.scenarios.show', $scenario) }}"
                           class="card block transition hover:bg-white/10" 
                           style="border-color: {{ $category->color }}44">
                            
                            <div class="flex items-start justify-between gap-2">
                                <div class="font-semibold">{{ $scenario->title }}</div>
                                @if ($score !== null)
                                    <span class="chip">{{ $score }}/100</span>
                                @elseif ($playing->has($scenario->id))
                                    <span class="chip text-warn">Sedang main</span>
                                @else
                                    <span class="chip">Baru</span>
                                @endif
                            </div>

                            <p class="mt-1 text-sm text-slate-300">{{ $scenario->synopsis }}</p>

                            <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-400">
                                <span class="chip">{{ $scenario->channel }} 💬</span>
                                <span class="chip">{{ str_repeat('★', $scenario->difficulty) }} {{ $scenario->difficultyLabel() }}</span>
                                <span class="chip">{{ $scenario->xp_reward }} XP ⭐</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach
</div>
@endsection