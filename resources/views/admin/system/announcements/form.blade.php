@extends('layouts.app')
@section('title', $announcement->exists ? 'Ubah Pengumuman' : 'Pengumuman Baru')

@section('content')
<div class="rise mx-auto max-w-2xl">
    <h1 class="h1">📣 {{ $announcement->exists ? 'Ubah pengumuman' : 'Pengumuman baru' }}</h1>

    <div class="mt-4">@include('admin.system._tabs')</div>

    <form method="POST" class="card space-y-4"
          action="{{ $announcement->exists ? route('admin.system.announcements.update', $announcement) : route('admin.system.announcements.store') }}">
        @csrf
        @if ($announcement->exists) @method('PUT') @endif

        <div>
            <label class="label" for="message">Isi pengumuman</label>
            <textarea id="message" name="message" rows="3" maxlength="300" required class="input"
                      placeholder="Contoh: Pemeliharaan terjadwal Minggu 09 Nov pukul 01.00–02.00 WIB. Aplikasi tidak bisa diakses sementara.">{{ old('message', $announcement->message) }}</textarea>
            <p class="mt-1 text-xs text-slate-400">Maksimal 300 karakter, teks biasa (tanpa HTML).</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="level">Tingkat</label>
                <select id="level" name="level" class="input">
                    @foreach (\App\Models\Announcement::LEVELS as $value => $label)
                        <option value="{{ $value }}" @selected(old('level', $announcement->level) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">Tingkat "Penting / darurat" tidak bisa ditutup pengguna.</p>
            </div>
            <div>
                <label class="label" for="audience">Ditampilkan untuk</label>
                <select id="audience" name="audience" class="input">
                    @foreach (\App\Models\Announcement::AUDIENCES as $value => $label)
                        <option value="{{ $value }}" @selected(old('audience', $announcement->audience) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="starts_at">Mulai tampil (opsional)</label>
                <input id="starts_at" type="datetime-local" name="starts_at" class="input" value="{{ old('starts_at', $announcement->starts_at?->format('Y-m-d\TH:i')) }}">
            </div>
            <div>
                <label class="label" for="ends_at">Berakhir (opsional)</label>
                <input id="ends_at" type="datetime-local" name="ends_at" class="input" value="{{ old('ends_at', $announcement->ends_at?->format('Y-m-d\TH:i')) }}">
            </div>
        </div>
        <p class="text-xs text-slate-500">Waktu memakai zona {{ config('app.timezone') }}. Kosongkan keduanya agar tampil langsung tanpa batas waktu.</p>

        <label class="flex items-center gap-3 text-sm">
            <input type="checkbox" name="is_active" value="1" class="h-4 w-4 accent-cyan-400" @checked(old('is_active', $announcement->is_active))>
            Aktif
        </label>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.system.announcements.index') }}" class="btn btn-ghost">Batal</a>
            <button class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>
@endsection
