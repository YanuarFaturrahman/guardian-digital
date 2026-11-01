@extends('layouts.app')
@section('title', $attempt->scenario->title)

@section('content')
@php
    $node = $attempt->currentNode;
    $category = $attempt->scenario->category;
    $choices = $node->choices->sortBy(fn ($c) => crc32($attempt->id . '-' . $c->id))->values();
    $feedback = session('feedback');

    // SUARA 1: pilih efek suara sesuai poin pilihan sebelumnya
    $sfx = $feedback
        ? ($feedback['points'] > 0 ? 'good' : ($feedback['points'] < 0 ? 'bad' : 'neutral'))
        : '';
@endphp

{{-- SUARA 2: data-game dan data-sfx dibaca oleh sound.js --}}
<div class="rise mx-auto max-w-2xl space-y-6" data-game data-sfx="{{ $sfx }}">

    <!-- Header / Navigasi -->
    <div class="flex items-center justify-between">
        <a href="{{ route('student.scenarios.index') }}" class="text-sm text-slate-400 transition hover:text-white">
            ← Keluar (progres tersimpan)
        </a>

        {{-- SUARA 3: tombol nyala/mati suara --}}
        <div class="flex items-center gap-3">
            @include('partials.sound-toggle')
            <span class="text-xs text-slate-400">Keputusan ke-{{ $stepNo }}</span>
        </div>
    </div>

    <!-- Feedback Section (jika ada) -->
    @if ($feedback)
        @php
            $isPositive = $feedback['points'] > 0;
            $isNegative = $feedback['points'] < 0;
        @endphp
        <div class="card p-4 {{ $isPositive ? 'bg-emerald-500/10 border-emerald-500/30' : ($isNegative ? 'bg-rose-500/10 border-rose-500/30' : 'bg-slate-800/50') }}">
            <div class="mb-1 flex items-center gap-2 font-semibold">
                <span>
                    @if ($isPositive)
                        🎉 Pilihan bagus
                    @elseif ($isNegative)
                        ⚠️ Hati-hati
                    @else
                        😐 Netral
                    @endif
                </span>
                <span class="rounded px-2 py-0.5 text-xs {{ $isPositive ? 'bg-emerald-500/20 text-emerald-300' : ($isNegative ? 'bg-rose-500/20 text-rose-300' : 'bg-slate-700 text-slate-300') }}">
                    ({{ $isPositive ? '+' : '' }}{{ $feedback['points'] }} poin)
                </span>
            </div>
            <p class="text-sm text-slate-300">{{ $feedback['text'] }}</p>
        </div>
    @endif

    <!-- Node / Konten Cerita -->
    <div class="card space-y-4 p-6" style="border-color: {{ $category->color }}66">
        <div class="flex items-center gap-2 text-xs text-slate-400">
            <span>{{ $category->icon }}</span>
            <span class="uppercase tracking-wider">{{ $attempt->scenario->channel }}</span>
        </div>

        @if ($node->title)
            <h2 class="text-xl font-bold text-white">{{ $node->title }}</h2>
        @endif

        @if ($node->speaker)
            <div class="text-xs text-slate-400">Dari: <span class="font-medium text-slate-200">{{ $node->speaker }}</span></div>
        @endif

        <div class="whitespace-pre-line leading-relaxed text-slate-200">{{ $node->body }}</div>
    </div>

    <!-- Pilihan Aksi (Choices) -->
    <div class="space-y-3">
        <h3 class="text-sm font-semibold text-slate-300">Apa yang akan kamu lakukan?</h3>

        <div class="grid gap-3">
            @foreach ($choices as $choice)
                <form action="{{ route('student.choose', [$attempt, $choice]) }}" method="POST" data-choice-form>
                    @csrf
                    {{-- SUARA 4: atribut data-choice memicu bunyi klik --}}
                    <button type="submit" data-choice class="card group flex w-full cursor-pointer items-start gap-3 p-4 text-left transition hover:bg-white/10">
                        <span class="font-semibold text-emerald-400">{{ chr(65 + $loop->index) }}.</span>
                        <span class="text-slate-200 group-hover:text-white">{{ $choice->label }}</span>
                    </button>
                </form>
            @endforeach
        </div>
    </div>

    <!-- Tip / Footer Catatan -->
    <div class="flex items-start gap-2 rounded-lg border border-slate-800 bg-slate-900/50 p-3 text-xs text-slate-500">
        <span class="text-base">💡</span>
        <p>Merasa janggal? Ingat <strong>JEDA</strong> (berhenti dulu), <strong>CEK</strong> (verifikasi lewat jalur resmi), <strong>LAPOR</strong> (ceritakan dan laporkan).</p>
    </div>
</div>
@endsection