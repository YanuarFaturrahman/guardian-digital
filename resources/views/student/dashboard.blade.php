@extends('layouts.app')
@section('title', 'Beranda Siswa')

@section('content')
<div class="rise grid gap-6 lg:grid-cols-3">

    {{-- Kolom utama --}}
    <div class="space-y-6 lg:col-span-2">

        <div class="card flex flex-wrap items-center gap-5">
            <div class="text-6xl">{{ $user->avatar }}</div>
            <div class="grow">
                <div class="text-sm text-slate-400">Halo, {{ $user->name }}</div>
                <div class="h1">Level {{ $level['level'] }} · {{ $level['title'] }}</div>
                <div class="mt-2">
                    <x-progress :value="$level['percent']" />
                    <div class="mt-1 text-xs text-slate-400">
                        ⭐ {{ $user->xp }} XP
                        @if ($level['next'])
                            · {{ $level['next'] - $user->xp }} XP lagi menuju level berikutnya
                        @else
                            · Level tertinggi tercapai!
                        @endif
                    </div>
                </div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-guard-400">#{{ $rank }}</div>
                <div class="text-xs text-slate-400">peringkat global</div>
            </div>
        </div>

        @if ($playing)
            <div class="card flex flex-wrap items-center justify-between gap-3 border-warn/40">
                <div>
                    <div class="text-xs uppercase tracking-widest text-warn">Misi belum selesai</div>
                    <div class="font-semibold">{{ $playing->scenario->title }}</div>
                </div>
                <a href="{{ route('student.play', $playing) }}" class="btn btn-primary">Lanjutkan ▶</a>
            </div>
        @elseif ($nextScenario)
            <div class="card flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="text-xs uppercase tracking-widest text-guard-400">Misi berikutnya untukmu</div>
                    <div class="font-semibold">{{ $nextScenario->category->icon }}
{{ $nextScenario->title }}</div>
                    <div class="text-xs text-slate-400">{{ $nextScenario->channel }} ·
{{ $nextScenario->difficultyLabel() }}</div>
                </div>
                <a href="{{ route('student.scenarios.show', $nextScenario) }}" class="btn btn-primary">Mulai ▶</a>
            </div>
        @else
            <div class="card text-center">🎉 Semua misi selesai! Ulangi misi untuk menaikkan skormu.</div>
        @endif

        {{-- Tiga jalur --}}
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['recognize', '👁️', 'Kenali', 'Misi', 'student.scenarios.index', '#f43f5e'],
                ['learn', '📚', 'Pelajari', 'Materi', 'student.lessons.index', '#a78bfa'],
                ['act', '⚡', 'Lindungi', 'Aksi', 'student.actions.index', '#10b981'],
            ] as [$key, $icon, $title, $unit, $route, $color])
                @php
                    $done = $pillars[$key]['done'];
                    $total = $pillars[$key]['total'];
                    $percent = $total ? (int) round($done / $total * 100) : 0;
                @endphp
                <a href="{{ route($route) }}" class="card block transition hover:bg-white/10">
                    <div class="text-3xl">{{ $icon }}</div>
                    <div class="mt-1 text-xs uppercase tracking-widest text-slate-400">{{ $key }}</div>
                    <div class="h2">{{ $title }}</div>
                    <div class="my-2"><x-progress :value="$percent" :color="$color" /></div>
                    <div class="text-xs text-slate-300">{{ $done }}/{{ $total }}
{{ $unit }}</div>
                </a>
            @endforeach
        </div>

        <div class="card">
            <h2 class="h2 mb-4">Penguasaan per Kategori</h2>
            <div class="space-y-4">
                @foreach ($mastery as $cat)
                    <div>
                        <div class="mb-1 flex justify-between text-sm">
                            <span>{{ $cat->icon }} {{ $cat->name }}</span>
                            <span class="text-slate-300">{{ $cat->mastery }}%</span>
                        </div>
                        <x-progress :value="$cat->mastery" :color="$cat->color" />
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Kolom samping --}}
    <div class="space-y-6">
        <div class="card">
    <h2 class="h2">👪 Kode Tautan Orang Tua</h2>
    <p class="mt-1 text-xs text-slate-400">Berikan kode ini kepada orang tua agar mereka bisa memantau progresmu. Hanya kamu yang memutuskan.</p>
    <div class="mt-3 flex items-center justify-between rounded-xl bg-guard-900 px-4 py-3">
        <span class="select-all font-mono text-lg tracking-wider text-guard-400">{{ $user->link_code }}</span>
        <button type="button" class="btn btn-ghost !px-3 !py-1 text-xs" data-copy="{{ $user->link_code }}">Salin</button>
    </div>
    <p class="mt-2 text-xs text-slate-400">Tertaut dengan {{ $parentCount }} orang tua.</p>
</div>

<div class="card">
    <h2 class="h2">🎒 Sekolahku</h2>
    @if ($user->school)
        <div class="mt-2 text-sm">{{ $user->school }}</div>
    @else
        <p class="mt-1 text-xs text-slate-400">Belum diatur. Pilih dari daftar, atau gabung kelas dari gurumu agar terisi otomatis.</p>
    @endif

    @if ($schools->isNotEmpty())
        <form method="POST" action="{{ route('student.school.update') }}" class="mt-3 flex gap-2">
            @csrf
            <select name="school_id" class="input" required>
                <option value="">— Pilih sekolah —</option>
                @foreach ($schools as $school)
                    <option value="{{ $school->id }}" {{ $user->school_id === $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary">Simpan</button>
        </form>
    @endif
</div>
        <div class="card">
            <h2 class="h2">🏫 Kelasku</h2>
            @forelse ($classrooms as $class)
                <div class="mt-2 text-sm">
                    {{ $class->name }} <span class="text-slate-400">· {{ $class->teacher->name }}</span>
                </div>
            @empty
                <p class="mt-1 text-xs text-slate-400">Belum bergabung ke kelas.</p>
            @endforelse

            <form method="POST" action="{{ route('student.class.join') }}" class="mt-3 flex gap-2">
                @csrf
                <input class="input" name="code" placeholder="Kode kelas" maxlength="10" required>
                <button class="btn btn-primary">Gabung</button>
            </form>
        </div>

        <div class="card">
            <h2 class="h2">🏅 Lencana Terbaru</h2>
            <div class="mt-2 flex flex-wrap gap-2">
                @forelse ($recentBadges as $badge)
                    <span class="chip" title="{{ $badge->description }}">{{ $badge->icon }} {{ $badge->name }}</span>
                @empty
                    <p class="text-xs text-slate-400">Selesaikan misi pertamamu untuk mendapat lencana.</p>
                @endforelse
            </div>
            <a href="{{ route('student.badges') }}" class="mt-3 inline-block text-xs text-guard-400 hover:underline">Lihat semua lencana →</a>
        </div>
    </div>
</div>
@endsection
