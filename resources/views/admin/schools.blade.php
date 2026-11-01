@extends('layouts.app')
@section('title', 'Kelola Sekolah')

@section('content')
<div class="rise mx-auto max-w-5xl">
    <h1 class="h1">🏫 Daftar Sekolah</h1>
    <p class="mb-4 text-sm text-slate-400">
        Nama sekolah dibakukan di sini. Jika dua nama ternyata sekolah yang sama (misalnya salah ketik),
        pakai <strong>Gabungkan</strong>: semua guru dan siswanya dipindahkan ke sekolah tujuan.
    </p>

    <div class="space-y-3">
        @forelse ($schools as $school)
            <div class="card">
                <div class="flex flex-wrap items-center gap-3">
                    <form method="POST" action="{{ route('admin.schools.update', $school) }}" class="flex grow gap-2">
                        @csrf @method('PUT')
                        <input class="input" name="name" value="{{ $school->name }}" required minlength="4" maxlength="120">
                        <button class="btn btn-primary">Simpan</button>
                    </form>
                    <span class="chip">🎓 {{ $school->students_count }} siswa</span>
                    <span class="chip">🧑‍🏫 {{ $school->teachers_count }} guru</span>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-3 border-t border-white/10 pt-3">
                    @if ($schools->count() > 1)
                        <form method="POST" action="{{ route('admin.schools.merge', $school) }}"
                              class="flex flex-wrap items-center gap-2"
                              onsubmit="return confirm('Pindahkan semua pengguna ke sekolah tujuan, lalu hapus sekolah ini?')">
                            @csrf
                            <span class="text-xs text-slate-400">Gabungkan ke:</span>
                            <select name="target_id" class="input !w-auto" required>
                                <option value="">— pilih sekolah tujuan —</option>
                                @foreach ($schools->where('id', '!=', $school->id) as $other)
                                    <option value="{{ $other->id }}">{{ $other->name }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-ghost text-xs">Gabungkan</button>
                        </form>
                    @endif

                    @if ($school->users_count === 0)
                        <form method="POST" action="{{ route('admin.schools.destroy', $school) }}"
                              onsubmit="return confirm('Hapus sekolah ini?')">
                            @csrf @method('DELETE')
                            <button class="text-xs text-danger hover:underline">Hapus</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="card text-sm text-slate-400">Belum ada sekolah. Sekolah dibuat otomatis saat guru mendaftar.</p>
        @endforelse
    </div>
</div>
@endsection