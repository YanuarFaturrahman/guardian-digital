@extends('layouts.app')
@section('title', 'Kelola Pengguna')

@section('content')
@php $isSuper = auth()->user()->role === 'superadmin'; @endphp
<div class="rise mx-auto max-w-5xl">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="h1">Kelola Pengguna</h1>
            <p class="text-sm text-slate-400">{{ $scopeLabel }} · lihat email akun, buatkan kata sandi sementara, atau nonaktifkan akun.</p>
        </div>
        @if ($isSuper)
            <a href="{{ route('admin.school-admins.create') }}" class="btn btn-primary">+ Admin Sekolah</a>
        @endif
    </div>

    @if (session('temp_password'))
        @php
            $t = session('temp_password');
            $pesan = "Halo {$t['name']}, ini akun Guardian Digital kamu.\nEmail: {$t['email']}\nKata sandi sementara: {$t['password']}\nSetelah masuk, kamu akan diminta membuat kata sandi baru.";
        @endphp
        <div class="mb-4 rounded-xl border border-warn/50 bg-warn/15 px-4 py-4 text-sm text-amber-100">
            <div class="font-semibold">Kata sandi sementara untuk {{ $t['name'] }}</div>
            <div class="mt-2 grid gap-1 font-mono text-base">
                <div>Email: <span class="text-white">{{ $t['email'] }}</span></div>
                <div>Kata sandi: <span class="text-white">{{ $t['password'] }}</span></div>
            </div>
            <p class="mt-2 text-xs">
                Kata sandi ini hanya tampil sekali dan hilang saat halaman dimuat ulang. Sampaikan ke pengguna sekarang.
            </p>
            <button type="button" id="copy-temp" data-text="{{ $pesan }}" class="btn btn-ghost mt-3 !py-1.5 text-xs">Salin pesan</button>
        </div>
    @endif

    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <input name="q" value="{{ $q }}" class="input max-w-xs" placeholder="Cari nama atau email...">
        <select name="role" class="input max-w-[12rem]">
            <option value="">Semua peran</option>
            @foreach ($roles as $value => $text)
                <option value="{{ $value }}" @selected($role === $value)>{{ $text }}</option>
            @endforeach
        </select>
        <select name="status" class="input max-w-[12rem]">
            <option value="">Semua status</option>
            <option value="active" @selected($status === 'active')>Aktif</option>
            <option value="inactive" @selected($status === 'inactive')>Dinonaktifkan</option>
        </select>
        <button class="btn btn-ghost">Cari</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="text-xs uppercase text-slate-400">
                <tr>
                    <th class="pb-2">Pengguna</th><th class="pb-2">Email</th><th class="pb-2">Peran</th>
                    @if ($isSuper)<th class="pb-2">Sekolah</th>@endif
                    <th class="pb-2">Status</th><th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @forelse ($users as $user)
                    <tr class="{{ $user->deactivated_at ? 'opacity-60' : '' }}">
                        <td class="py-2">{{ $user->avatar }} {{ $user->name }}</td>
                        <td class="font-mono text-xs">{{ $user->email }}</td>
                        <td>{{ $roles[$user->role] ?? $user->role }}</td>
                        @if ($isSuper)<td class="text-xs text-slate-300">{{ $user->schoolRef?->name ?? '-' }}</td>@endif
                        <td>
                            @if ($user->deactivated_at)
                                <span class="chip text-danger">Dinonaktifkan</span>
                            @elseif ($user->must_change_password)
                                <span class="chip text-warn">Menunggu ganti sandi</span>
                            @else
                                <span class="text-xs text-slate-400">Aktif</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if ($user->role !== 'superadmin')
                                <div class="flex flex-wrap justify-end gap-x-4 gap-y-1">
                                    @if ($user->deactivated_at)
                                        <form method="POST" action="{{ route('users.activate', $user) }}">
                                            @csrf
                                            <button class="text-guard-400 hover:underline">Aktifkan</button>
                                        </form>
                                        @if ($isSuper)
                                            <a href="{{ route('admin.users.confirm-delete', $user) }}" class="text-danger hover:underline">Hapus permanen</a>
                                        @endif
                                    @else
                                        <form method="POST" action="{{ route('users.temp-password', $user) }}"
                                              onsubmit="return confirm('Buat kata sandi sementara untuk pengguna ini? Kata sandi lamanya langsung tidak berlaku dan ia akan keluar dari semua perangkat.')">
                                            @csrf
                                            <button class="text-guard-400 hover:underline">Atur sandi sementara</button>
                                        </form>
                                        <form method="POST" action="{{ route('users.deactivate', $user) }}"
                                              onsubmit="return confirm('Nonaktifkan akun ini? Pengguna langsung keluar dari semua perangkat dan tidak bisa masuk sampai diaktifkan kembali. Datanya tetap tersimpan.')">
                                            @csrf
                                            <button class="text-warn hover:underline">Nonaktifkan</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $isSuper ? 6 : 5 }}" class="py-6 text-center text-slate-400">Tidak ada pengguna yang cocok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex items-center justify-between text-sm">
        <span class="text-slate-400">Halaman {{ $users->currentPage() }}</span>
        <div class="flex gap-2">
            @if ($users->previousPageUrl())
                <a href="{{ $users->previousPageUrl() }}" class="btn btn-ghost !py-1.5">← Sebelumnya</a>
            @endif
            @if ($users->nextPageUrl())
                <a href="{{ $users->nextPageUrl() }}" class="btn btn-ghost !py-1.5">Berikutnya →</a>
            @endif
        </div>
    </div>
</div>

<script>
    document.getElementById('copy-temp')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget; // simpan dulu, nilainya hilang setelah await
        try {
            await navigator.clipboard.writeText(btn.dataset.text);
            btn.textContent = 'Tersalin ✓';
        } catch {
            btn.textContent = 'Gagal menyalin, salin manual';
        }
    });
</script>
@endsection