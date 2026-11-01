@extends('layouts.app')
@section('title', $action->exists ? 'Ubah Aksi' : 'Aksi Baru')

@section('content')
<div class="rise mx-auto max-w-2xl">
    <a href="{{ route('admin.actions.index') }}" class="text-sm text-slate-400 hover:text-white">← Semua aksi</a>
    <h1 class="h1 mb-4 mt-2">{{ $action->exists ? 'Ubah Aksi' : 'Aksi Baru' }}</h1>

    <form method="POST" class="card space-y-4"
          action="{{ $action->exists ? route('admin.actions.update', $action) : route('admin.actions.store') }}">
        @csrf
        @if ($action->exists) @method('PUT') @endif

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
            </div>
            <div>
                <label class="label" for="position">Urutan</label>
                <input class="input" id="position" name="position" type="number" min="0" max="250"
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
    document.querySelectorAll('[data-emoji]').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById('icon').value = button.dataset.emoji;
        });
    });
</script>
@endsection