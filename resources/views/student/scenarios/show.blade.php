@extends('layouts.app')
@section('title', $scenario->title)
 
@section('content')
<div class="rise space-y-6 max-w-2xl mx-auto">
    <!-- Tombol Kembali -->
    <div>
        <a href="{{ route('student.scenarios.index') }}" class="text-sm text-slate-400 hover:text-white transition inline-flex items-center gap-1">
            ← Semua misi
        </a>
    </div>
 
    <!-- Kartu Detail Misi -->
    <div class="card p-6 md:p-8 space-y-6 bg-slate-900/80 border border-slate-800 rounded-2xl shadow-xl">
        <!-- Header Misi -->
        <div class="space-y-3">
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <span>{{ $scenario->category->icon }}</span>
                <span class="uppercase tracking-wider font-medium">{{ $scenario->category->name }}</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-white">{{ $scenario->title }}</h1>
            <p class="text-slate-300 leading-relaxed">{{ $scenario->synopsis }}</p>
        </div>
 
        <!-- Informasi Metadata Misi (Platform, Kesulitan, Hadiah) -->
        <div class="grid grid-cols-3 gap-3 py-4 border-y border-slate-800 text-center">
            <div class="bg-slate-800/40 p-3 rounded-xl border border-slate-700/50">
                <div class="text-xs text-slate-400">Platform</div>
                <div class="text-sm font-semibold text-slate-200 mt-1 capitalize">{{ $scenario->channel }}</div>
            </div>
            <div class="bg-slate-800/40 p-3 rounded-xl border border-slate-700/50">
                <div class="text-xs text-slate-400">Kesulitan</div>
                <div class="text-sm font-semibold text-slate-200 mt-1">{{ $scenario->difficultyLabel() }}</div>
            </div>
            <div class="bg-slate-800/40 p-3 rounded-xl border border-slate-700/50">
                <div class="text-xs text-slate-400">Hadiah</div>
                <div class="text-sm font-semibold text-amber-400 mt-1">{{ $scenario->xp_reward }} XP ⭐</div>
            </div>
        </div>
 
        <!-- Panduan Singkat / Cara Bermain -->
        <div class="p-4 rounded-xl bg-slate-800/30 border border-slate-800 text-xs text-slate-400 leading-relaxed">
            <strong class="text-slate-300">Cara bermain:</strong> Baca situasi, pilih tindakanmu, dan lihat akibatnya. Tidak ada jawaban "dari buku"; pilih seperti kamu akan bertindak di dunia nyata. Di akhir, kamu akan melihat red flag yang seharusnya dikenali.
        </div>
 
        <!-- Skor Terbaik (Jika ada) -->
        @if ($best !== null)
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-sm flex items-center justify-between">
                <span>Skor terbaikmu: <strong>{{ $best }}/100</strong></span>
                <span class="text-xs text-emerald-400/80">Mengulang hanya menambah XP jika skormu naik</span>
            </div>
        @endif
 
        <!-- Tombol Aksi (Mulai / Lanjutkan / Reset) -->
        <div class="space-y-3 pt-2">
            <form action="{{ route('student.scenarios.start', $scenario) }}" method="POST">
                @csrf
                <button type="submit" class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-xl transition shadow-lg shadow-emerald-600/20 text-center">
                    {{ $playing ? 'Lanjutkan misi ▶' : 'Mulai misi ▶' }}
                </button>
            </form>
 
            @if ($playing)
                <form action="{{ route('student.scenarios.start', $scenario) }}" method="POST">
                    @csrf
                    <input type="hidden" name="reset" value="1">
                    <button type="submit" class="w-full py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium rounded-xl transition text-xs border border-slate-700">
                        Mulai dari awal
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection