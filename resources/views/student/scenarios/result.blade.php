@extends('layouts.app')
@section('title', 'Hasil Misi')
 
@section('content')
@php
    $ending = $attempt->currentNode;
    $theme = match ($attempt->ending_kind) {
        'good' => ['🏆', 'border-emerald-500/50 bg-emerald-500/10', 'text-emerald-300'],
        'neutral' => ['🙂', 'border-amber-500/50 bg-amber-500/10', 'text-amber-300'],
        default => ['💥', 'border-rose-500/50 bg-rose-500/10', 'text-rose-300'],
    };
@endphp

<div class="rise space-y-6 max-w-2xl mx-auto">
    <!-- Kartu Hasil Utama / Ending -->
    <div class="card p-6 md:p-8 space-y-6 border {{ $theme[1] }} rounded-2xl shadow-xl">
        <div class="text-center space-y-3">
            <span class="text-5xl">{{ $theme[0] }}</span>
            <h1 class="text-2xl md:text-3xl font-bold text-white">{{ $ending->title }}</h1>
            <p class="text-slate-300 leading-relaxed">{{ $ending->body }}</p>
        </div>
 
        <!-- Statistik Skor & XP -->
        <div class="grid grid-cols-3 gap-4 pt-4 border-t border-white/10 text-center">
            <div class="bg-slate-900/50 p-3 rounded-xl border border-slate-800">
                <div class="text-2xl font-bold {{ $theme[2] }}">{{ $attempt->score }}</div>
                <div class="text-xs text-slate-400 mt-1">Skor /100</div>
            </div>
            <div class="bg-slate-900/50 p-3 rounded-xl border border-slate-800">
                <div class="text-2xl font-bold text-amber-400">+{{ $attempt->xp_gained }}</div>
                <div class="text-xs text-slate-400 mt-1">XP Didapat</div>
            </div>
            <div class="bg-slate-900/50 p-3 rounded-xl border border-slate-800">
                <div class="text-2xl font-bold text-slate-200">{{ $attempt->steps->count() }}</div>
                <div class="text-xs text-slate-400 mt-1">Keputusan</div>
            </div>
        </div>
    </div>
 
    <!-- Pelajaran Utama (Jika ada) -->
    @if ($ending->lesson)
        <div class="card p-6 bg-slate-900/80 border border-slate-800 rounded-2xl space-y-2">
            <div class="flex items-center gap-2 text-emerald-400 font-semibold text-sm">
                <span>📌</span> Pelajaran Utama
            </div>
            <p class="text-slate-300 text-sm leading-relaxed">{{ $ending->lesson }}</p>
        </div>
    @endif
 
    <!-- Ulasan Keputusanmu -->
    <div class="card p-6 bg-slate-900/80 border border-slate-800 rounded-2xl space-y-4">
        <div class="flex items-center gap-2 text-slate-200 font-semibold">
            <span>🧭</span> Ulasan Keputusanmu
        </div>

        <div class="space-y-3">
            @foreach ($attempt->steps as $step)
                <div class="p-4 rounded-xl bg-slate-800/50 border border-slate-700/50 space-y-2">
                    <div class="text-xs text-slate-400 font-medium">
                        {{ $step->node->title ?? 'Adegan ' . $loop->iteration }}
                    </div>
                    <div class="text-sm font-semibold text-white">
                        Kamu memilih: "{{ $step->choice->label }}"
                    </div>
                    <div class="text-xs flex items-center gap-2 pt-1 border-t border-slate-700/40">
                        <span class="font-medium {{ $step->points > 0 ? 'text-emerald-400' : ($step->points < 0 ? 'text-rose-400' : 'text-slate-400') }}">
                            {{ $step->points > 0 ? '+' : '' }}{{ $step->points }} poin
                        </span>
                        <span class="text-slate-500">·</span>
                        <span class="text-slate-300">{{ $step->choice->feedback }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
 
    <!-- Red Flag yang Harus Dikenali -->
    @if ($attempt->scenario->redFlags->isNotEmpty())
        <div class="card p-6 bg-rose-950/20 border border-rose-900/30 rounded-2xl space-y-4">
            <div class="flex items-center gap-2 text-rose-400 font-semibold">
                <span>🚩</span> Red Flag yang Harus Dikenali
            </div>

            <div class="space-y-3">
                @foreach ($attempt->scenario->redFlags as $flag)
                    <div class="p-3 rounded-xl bg-rose-950/30 border border-rose-950 space-y-1">
                        <div class="text-sm font-semibold text-rose-200">{{ $flag->title }}</div>
                        <div class="text-xs text-rose-300/80 leading-relaxed">{{ $flag->explanation }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
 
    <!-- Tombol Aksi / Navigasi Akhir -->
    <div class="flex flex-col sm:flex-row gap-3 pt-2">
        <a href="{{ route('student.scenarios.show', $attempt->scenario) }}" class="flex-1 px-5 py-3 bg-slate-800 hover:bg-slate-700 text-white font-medium rounded-xl transition text-center border border-slate-700 flex items-center justify-center gap-2">
            <span>🔁</span> Main lagi
        </a>

        @if ($nextScenario)
            <a href="{{ route('student.scenarios.show', $nextScenario) }}" class="flex-1 px-5 py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-medium rounded-xl transition text-center shadow-lg shadow-emerald-600/20 flex items-center justify-center gap-2">
                <span>▶</span> Misi berikutnya
            </a>
        @else
            <a href="{{ route('student.scenarios.index') }}" class="flex-1 px-5 py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-medium rounded-xl transition text-center shadow-lg shadow-emerald-600/20 flex items-center justify-center gap-2">
                <span>🏠</span> Semua misi
            </a>
        @endif
    </div>
</div>
@endsection