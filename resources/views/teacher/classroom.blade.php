@extends('layouts.app')
@section('title', $classroom->name)

@section('content')
<div class="rise mx-auto max-w-5xl">
    <a href="{{ route('teacher.dashboard') }}" class="text-sm text-slate-400 hover:text-white">← Semua kelas</a>
    <div class="mb-6 mt-2 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="h1">{{ $classroom->name }}</h1>
            <div class="text-sm text-slate-400">{{ $rows->count() }} siswa · Kode kelas: <span class="font-mono text-guard-400">{{ $classroom->join_code }}</span></div>
        </div>
        <a href="{{ route('leaderboard', ['scope' => 'class', 'class' => $classroom->id]) }}" class="btn btn-ghost">🏆 Peringkat kelas</a>
    </div>

    <div class="card mb-6">
        <h2 class="h2 mb-1">Penguasaan Kelas per Kategori</h2>
        <p class="mb-4 text-xs text-slate-400">Rata-rata skor terbaik seluruh siswa. Kategori yang rendah layak dibahas di kelas.</p>
        <div class="space-y-4">
            @foreach ($categories as $category)
                @php $value = $masteryByCategory->get($category->id, 0); @endphp
                <div>
                    <div class="mb-1 flex justify-between text-sm">
                        <span>{{ $category->icon }} {{ $category->name }}</span><span class="text-slate-300">{{ $value }}%</span>
                    </div>
                    <x-progress :value="$value" :color="$category->color" />
                </div>
            @endforeach
        </div>
    </div>

    <div class="card overflow-x-auto">
        <h2 class="h2 mb-3">Daftar Siswa</h2>
        <table class="w-full min-w-[640px] text-left text-sm">
            <thead class="text-xs uppercase text-slate-400">
                <tr>
                    <th class="pb-2">Siswa</th><th class="pb-2">Level</th><th class="pb-2">XP</th>
                    <th class="pb-2">Misi</th><th class="pb-2">Rata-rata</th><th class="pb-2">Terakhir aktif</th><th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @forelse ($rows as $row)
                    @php $s = $row['student']; $sum = $row['summary']; @endphp
                    <tr>
                        <td class="py-2">{{ $s->avatar }} {{ $s->name }}</td>
                        <td>{{ $row['level']['title'] }}</td>
                        <td>{{ $s->xp }}</td>
                        <td>{{ $sum['scenarios_done'] }}</td>
                        <td>{{ $sum['avg_score'] }}</td>
                        <td class="text-xs text-slate-400">{{ $sum['last_active'] ? \Illuminate\Support\Carbon::parse($sum['last_active'])->diffForHumans() : '-' }}</td>
                        <td><a href="{{ route('teacher.classes.student', [$classroom, $s]) }}" class="text-guard-400 hover:underline">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-4 text-center text-slate-400">Belum ada siswa. Bagikan kode kelas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
