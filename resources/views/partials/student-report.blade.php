{{-- Variabel: $student, $summary, $mastery, $attempts, $badges, $level --}}
<div class="card mb-6 flex flex-wrap items-center gap-4">
    <div class="text-5xl">{{ $student->avatar }}</div>
    <div class="grow">
        <div class="h1">{{ $student->name }}</div>
        <div class="text-sm text-slate-400">{{ $student->school ?? 'Sekolah belum diisi' }}</div>
        <div class="mt-2 flex flex-wrap gap-2">
            <span class="chip">⭐ {{ $student->xp }} XP</span>
            <span class="chip">Level {{ $level['level'] }} · {{ $level['title'] }}</span>
        </div>
    </div>
</div>

<div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <x-stat label="Misi selesai" :value="$summary['scenarios_done']" icon="🎯" />
    <x-stat label="Rata-rata skor" :value="$summary['avg_score']" icon="📊" />
    <x-stat label="Materi dibaca" :value="$summary['lessons_read']" icon="📚" />
    <x-stat label="Aksi keamanan" :value="$summary['actions_done']" icon="⚡" />
</div>

<div class="grid gap-6 lg:grid-cols-2">
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

    <div class="card">
        <h2 class="h2 mb-4">Misi Terakhir</h2>
        @forelse ($attempts as $attempt)
            <div class="flex items-center justify-between border-b border-white/10 py-2 text-sm last:border-0">
                <div>
                    <div>{{ $attempt->scenario->title }}</div>
                    <div class="text-xs text-slate-400">
                        {{ $attempt->scenario->category->name }} · {{ $attempt->finished_at?->diffForHumans() }}
                    </div>
                </div>
                <span class="chip">{{ $attempt->score }}/100</span>
            </div>
        @empty
            <p class="text-sm text-slate-400">Belum ada misi yang diselesaikan.</p>
        @endforelse
    </div>
</div>

<div class="card mt-6">
    <h2 class="h2 mb-3">Guardian Badge ({{ $badges->count() }})</h2>
    <div class="flex flex-wrap gap-2">
        @forelse ($badges as $badge)
            <span class="chip" title="{{ $badge->description }}">{{ $badge->icon }}
{{ $badge->name }}</span>
        @empty
            <p class="text-sm text-slate-400">Belum ada lencana.</p>
        @endforelse
    </div>
</div>
