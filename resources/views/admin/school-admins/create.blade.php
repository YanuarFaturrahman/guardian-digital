@extends('layouts.app')
@section('title', 'Admin Sekolah Baru')

@section('content')
<div class="rise mx-auto max-w-2xl">
    <a href="{{ route('users.index') }}" class="text-sm text-slate-400 hover:text-white">← Daftar pengguna</a>
    <h1 class="h1 mb-1 mt-2">Admin Sekolah Baru</h1>
    <p class="mb-4 text-sm text-slate-400">
        Akun koordinator untuk satu sekolah. Kata sandi sementara dibuat otomatis dan ditampilkan sekali setelah disimpan.
    </p>

    <form method="POST" action="{{ route('admin.school-admins.store') }}" class="card space-y-4">
        @csrf
        <div>
            <label class="label" for="name">Nama</label>
            <input class="input" id="name" name="name" maxlength="60" value="{{ old('name') }}" required>
        </div>
        <div>
            <label class="label" for="email">Email</label>
            <input class="input" id="email" type="email" name="email" maxlength="120" value="{{ old('email') }}" required>
        </div>
        <div>
            <label class="label" for="school_id">Sekolah yang dikoordinasi</label>
            <select class="input" id="school_id" name="school_id" required>
                <option value="">— pilih sekolah —</option>
                @foreach ($schools as $school)
                    <option value="{{ $school->id }}" @selected((string) old('school_id') === (string) $school->id)>{{ $school->name }}</option>
                @endforeach
            </select>
            @if ($schools->isEmpty())
                <p class="mt-1 text-xs text-slate-400">Belum ada sekolah. Sekolah muncul otomatis saat siswa atau guru mendaftar.</p>
            @endif
        </div>
        <button class="btn btn-primary">Simpan dan buat kata sandi sementara</button>
    </form>
</div>
@endsection