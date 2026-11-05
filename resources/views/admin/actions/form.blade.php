@extends('layouts.app')
@section('title', $action->exists ? 'Ubah Aksi' : 'Aksi Baru')

@section('content')
@php
    $tiers = \App\Models\SafetyAction::PRIORITIES;
    $currentPriority = (int) old('priority', $action->priority ?? 3);

    // Kelas ditulis lengkap agar terbaca oleh Tailwind
    $tierStyle = [
        1 => 'peer-checked:border-rose-400 peer-checked:bg-rose-500/15',
        2 => 'peer-checked:border-amber-400 peer-checked:bg-amber-500/15',
        3 => 'peer-checked:border-emerald-400 peer-checked:bg-emerald-500/15',
    ];
@endphp

<div class="rise mx-auto max-w-2xl">
    <a href="{{ route('admin.actions.index') }}" class="text-sm text-slate-400 hover:text-white">← Semua aksi</a>
    <h1 class="h1 mb-4 mt-2">{{ $action->exists ? 'Ubah Aksi' : 'Aksi Baru' }}</h1>

    <form method="POST" class="card space-y-4"
          action="{{ $action->exists ? route('admin.actions.update', $action) : route('admin.actions.store') }}">
        @csrf
        @if ($action->exists) @method('PUT') @endif

        {{-- Tingkat urgensi --}}
        <div>
            <span class="label">Tingkat urgensi</span>
            <div class="grid gap-2 sm:grid-cols-3">
                @foreach ($tiers as $priority => $tier)
                    <label class="cursor-pointer">
                        <input type="radio" name="priority" value="{{ $priority }}" class="peer sr-only"
                               data-xp="{{ $tier['xp'] }}" {{ $currentPriority === $priority ? 'checked' : '' }}>
                        <div class="h-full rounded-xl border border-white/15 p-3 text-sm transition {{ $tierStyle[$priority] }}">
                            <div class="font-semibold" style="color: {{ $tier['color'] }}">{{ $tier['icon'] }} {{ $tier['label'] }}</div>
                            <div class="mt-1 text-xs text-slate-300">{{ $tier['when'] }}</div>
                            <div class="mt-1 text-xs text-slate-400">XP standar +{{ $tier['xp'] }}</div>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <label class="label" for="icon">Ikon (satu emoji)</label>
            <input class="input max-w-[8rem] text-center text-2xl" id="icon" name="icon" maxlength="8" required
                   value="{{ old('icon', $action->icon) }}">
            <div class="mt-2 flex flex-wrap gap-1">
                @foreach (['🔑','🧩','🔒','🕵️','📞','📣','🔎','🧹','🗝️','🛡️','📵','🧠','✅','⚡'] as $emoji)
                    <button type="button" data-emoji="{{ $emoji }}"
                            class="rounded-lg bg-white/5 px-2 py-1 text-xl hover:bg-white/15">{{ $emoji }}</button>
                @endforeach
            </div>
            <p class="mt-1 text-xs text-slate-400">Pilih dari daftar, atau di Windows tekan <kbd>Win</kbd> + <kbd>.</kbd> untuk memilih emoji lain.</p>
        </div>

        <div>
            <label class="label" for="title">Judul aksi</label>
            <input class="input" id="title" name="title" maxlength="120" required
                   value="{{ old('title', $action->title) }}"
                   placeholder="Contoh: Aktifkan verifikasi dua langkah (2FA)">
        </div>

        <div>
            <label class="label" for="description">Deskripsi (apa yang harus dilakukan siswa)</label>
            <textarea class="input" id="description" name="description" rows="4" maxlength="400" required>{{ old('description', $action->description) }}</textarea>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="xp_reward">XP saat selesai</label>
                <input class="input" id="xp_reward" name="xp_reward" type="number" min="0" max="200" required
                       value="{{ old('xp_reward', $action->xp_reward) }}">
                <p class="mt-1 text-xs text-slate-400">
                    XP standar tingkat ini: <strong id="xp-hint"></strong>.
                    <button type="button" id="xp-apply" class="text-guard-400 hover:underline">Pakai</button>
                </p>
            </div>
            <div>
                <label class="label" for="position">Urutan</label>
                <input class="input" id="position" name="position" type="number" min="1" max="250"
                       placeholder="Kosongkan = paling akhir"
                       value="{{ old('position', $action->exists ? $action->position : '') }}">
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button class="btn btn-primary">{{ $action->exists ? 'Simpan perubahan' : 'Tambah aksi' }}</button>
            <a href="{{ route('admin.actions.index') }}" class="btn btn-ghost">Batal</a>
        </div>
    </form>
</div>

<script>
    (function () {
        const xp = document.getElementById('xp_reward');
        const hint = document.getElementById('xp-hint');
        const apply = document.getElementById('xp-apply');
        const radios = document.querySelectorAll('input[name="priority"]');
        // Saat membuat aksi baru, XP ikut berubah mengikuti tingkat sampai diketik manual.
        let touched = {{ $action->exists ? 'true' : 'false' }};

        function standardXp() {
            const checked = document.querySelector('input[name="priority"]:checked');
            return checked ? checked.dataset.xp : '';
        }

        function refresh() {
            hint.textContent = '+' + standardXp();
            if (!touched) xp.value = standardXp();
        }

        xp.addEventListener('input', () => { touched = true; });
        apply.addEventListener('click', () => { xp.value = standardXp(); touched = false; });
        radios.forEach((radio) => radio.addEventListener('change', refresh));
        refresh();

        document.querySelectorAll('[data-emoji]').forEach((button) => {
            button.addEventListener('click', () => {
                document.getElementById('icon').value = button.dataset.emoji;
            });
        });
    })();
</script>
@endsection