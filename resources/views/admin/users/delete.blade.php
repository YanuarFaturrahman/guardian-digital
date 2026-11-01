@extends('layouts.app')
@section('title', 'Hapus Akun Permanen')

@section('content')
<div class="rise mx-auto max-w-2xl">
    <a href="{{ route('users.index') }}" class="text-sm text-slate-400 hover:text-white">← Daftar pengguna</a>
    <h1 class="h1 mb-4 mt-2">Hapus akun permanen</h1>

    <div class="mb-4 rounded-xl border border-danger/40 bg-danger/15 px-4 py-4 text-sm text-rose-200">
        <strong>Tindakan ini tidak bisa dibatalkan.</strong>
        Akun {{ $user->name }} ({{ $user->email }}) dan data berikut akan hilang selamanya:
        <ul class="mt-2 list-disc pl-5">
            @forelse ($impact as $line)
                <li>{{ $line }}</li>
            @empty
                <li>Tidak ada data belajar yang terhubung ke akun ini.</li>
            @endforelse
        </ul>
        <p class="mt-3">Jika akun hanya perlu dihentikan, biarkan saja dinonaktifkan: datanya tetap aman dan bisa diaktifkan lagi.</p>
    </div>

    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="card space-y-4">
        @csrf @method('DELETE')
        <div>
            <label class="label" for="confirm_email">
                Ketik email akun ini untuk memastikan: <span class="font-mono text-white">{{ $user->email }}</span>
            </label>
            <input class="input" id="confirm_email" name="confirm_email" autocomplete="off" required>
        </div>
        <div class="flex flex-wrap gap-3">
            <button class="btn btn-danger">Hapus permanen</button>
            <a href="{{ route('users.index') }}" class="btn btn-ghost">Batal</a>
        </div>
    </form>
</div>
@endsection