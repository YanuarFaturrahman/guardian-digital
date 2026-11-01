@extends('layouts.app')
@section('title', 'Papan Peringkat')

@section('content')
<div class="rise mx-auto max-w-3xl">
    <h1 class="h1">🏆 Papan Peringkat Guardian</h1>
    <p class="mb-4 text-sm text-slate-400">Siapa yang paling gigih mengamankan ruang digitalnya? Peringkat berdasarkan XP.</p>

    {{-- Tab cakupan --}}
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('leaderboard', ['scope' => 'global']) }}"
           class="btn {{ $scope === 'global' ? 'btn-primary' : 'btn-ghost' }}">🌏 Global</a>

        @if ($schools->isNotEmpty())
            <a href="{{ route('leaderboard', ['scope' => 'school', 'school' => $schoolId]) }}"
               class="btn {{ $scope === 'school' ? 'btn-primary' : 'btn-ghost' }}">🏫 Sekolah</a>
        @endif

        @if ($classrooms->isNotEmpty())
            <a href="{{ route('leaderboard', ['scope' => 'class', 'class' => $classId]) }}"
               class="btn {{ $scope === 'class' ? 'btn-primary' : 'btn-ghost' }}">👥 Kelas</a>
        @endif
    </div>

    {{-- Pilihan kelas (hanya saat tab Kelas aktif) --}}
    @if ($scope === 'class')
        <form method="GET" action="{{ route('leaderboard') }}" class="mb-2 flex flex-wrap items-center gap-2">
            <input type="hidden" name="scope" value="class">
            <label for="class" class="text-sm text-slate-300">Lihat peringkat kelas:</label>
            <select id="class" name="class" class="input max-w-md" onchange="this.form.submit()">
                @foreach ($classrooms as $item)
                    @php $kids = $childNamesByClass[$item->id] ?? []; @endphp
                    <option value="{{ $item->id }}" {{ (int) $item->id === $classId ? 'selected' : '' }}>
                        {{ $kids ? '⭐ ' : '' }}{{ $item->name }}
                        @if (auth()->user()->role !== 'teacher') · {{ $item->teacher?->name }}@endif
                        @if ($kids) ({{ implode(', ', $kids) }})@endif
                    </option>
                @endforeach
            </select>
            <noscript><button class="btn btn-primary">Tampilkan</button></noscript>
        </form>
        @if ($childNamesByClass->isNotEmpty())
            <p class="mb-4 text-xs text-slate-400">⭐ menandai kelas anakmu, nama anak ada di dalam kurung.</p>
        @else
            <div class="mb-4"></div>
        @endif
    @endif

    {{-- Pilihan sekolah (hanya saat tab Sekolah aktif) --}}
    @if ($scope === 'school')
        <form method="GET" action="{{ route('leaderboard') }}" class="mb-2 flex flex-wrap items-center gap-2">
            <input type="hidden" name="scope" value="school">
            <label for="school" class="text-sm text-slate-300">Lihat peringkat sekolah:</label>
            <select id="school" name="school" class="input max-w-sm" onchange="this.form.submit()">
                @foreach ($schools as $item)
                    <option value="{{ $item->id }}" {{ $item->id === $schoolId ? 'selected' : '' }}>
                        {{ $mySchoolIds->contains($item->id) ? '⭐ ' : '' }}{{ $item->name }}
                    </option>
                @endforeach
            </select>
            <noscript><button class="btn btn-primary">Tampilkan</button></noscript>
        </form>
        <p class="mb-4 text-xs text-slate-400">⭐ menandai sekolah kamu atau sekolah anakmu.</p>
    @endif

    <div class="card !p-0 overflow-hidden">
        @forelse ($students as $student)
            @php
                $level = $student->levelData();
                $medal = [1 => '🥇', 2 => '🥈', 3 => '🥉'][$loop->iteration] ?? null;
                $me = $student->id === auth()->id();
                $mine = $childIds->contains($student->id);
            @endphp
            <div class="flex items-center gap-4 border-b border-white/10 px-5 py-3 last:border-0 {{ $me || $mine ? 'bg-guard-500/15' : '' }}">
                <div class="w-8 text-center text-lg font-bold text-slate-300">{{ $medal ?? $loop->iteration }}</div>
                <div class="text-3xl">{{ $student->avatar }}</div>
                <div class="grow">
                    <div class="font-semibold">
                        {{ $student->name }}
                        @if ($me)<span class="chip ml-1">Kamu</span>@elseif ($mine)<span class="chip ml-1">Anakmu</span>@endif
                    </div>
                    <div class="text-xs text-slate-400">{{ $student->school ?? '-' }} · Lv {{ $level['level'] }} {{ $level['title'] }}</div>
                </div>
                <div class="text-right">
                    <div class="font-bold text-guard-400">{{ $student->xp }} XP</div>
                    <div class="text-xs text-slate-400">🏅 {{ $student->badges_count }}</div>
                </div>
            </div>
        @empty
            <p class="p-6 text-center text-sm text-slate-400">Belum ada siswa di peringkat ini.</p>
        @endforelse
    </div>

    <p class="mt-3 text-xs text-slate-400">Menampilkan maksimal 50 siswa teratas. Nama yang tampil adalah nama tampilan yang dipilih siswa.</p>
</div>
@endsection