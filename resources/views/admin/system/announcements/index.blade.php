@extends('layouts.app')
@section('title', 'Pengumuman')

@section('content')
@php
    $levelStyle = ['info' => 'text-guard-400', 'warning' => 'text-amber-300', 'danger' => 'text-rose-300'];
@endphp
<div class="rise mx-auto max-w-5xl">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="h1">📣 Pengumuman</h1>
            <p class="mt-1 text-sm text-slate-400">Banner di bagian atas halaman untuk memberi tahu pengguna: jadwal pemeliharaan, gangguan, atau info penting.</p>
        </div>
        <a href="{{ route('admin.system.announcements.create') }}" class="btn btn-primary">+ Buat pengumuman</a>
    </div>

    <div class="mt-4">@include('admin.system._tabs')</div>

    <div class="card overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="text-xs uppercase text-slate-400">
                <tr><th class="pb-2">Pesan</th><th class="pb-2">Tingkat</th><th class="pb-2">Untuk</th><th class="pb-2">Jadwal</th><th class="pb-2">Status</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @forelse ($announcements as $a)
                    <tr>
                        <td class="max-w-xs py-2 text-sm">{{ $a->message }}</td>
                        <td><span class="chip {{ $levelStyle[$a->level] ?? '' }}">{{ \App\Models\Announcement::LEVELS[$a->level] ?? $a->level }}</span></td>
                        <td class="text-xs">{{ \App\Models\Announcement::AUDIENCES[$a->audience] ?? $a->audience }}</td>
                        <td class="text-xs text-slate-300">
                            {{ $a->starts_at ? $a->starts_at->format('d M Y H:i') : 'Langsung' }}<br>
                            <span class="text-slate-500">s/d {{ $a->ends_at ? $a->ends_at->format('d M Y H:i') : 'tanpa batas' }}</span>
                        </td>
                        <td>
                            @if ($a->isLive())
                                <span class="chip text-emerald-300">Tampil</span>
                            @elseif (! $a->is_active)
                                <span class="chip text-slate-400">Nonaktif</span>
                            @elseif ($a->starts_at && $a->starts_at->isFuture())
                                <span class="chip text-amber-300">Terjadwal</span>
                            @else
                                <span class="chip text-slate-400">Berakhir</span>
                            @endif
                        </td>
                        <td class="space-x-3 whitespace-nowrap text-right text-xs">
                            <a href="{{ route('admin.system.announcements.edit', $a) }}" class="text-guard-400 hover:underline">Ubah</a>
                            <form method="POST" action="{{ route('admin.system.announcements.toggle', $a) }}" class="inline">
                                @csrf
                                <button class="text-amber-300 hover:underline">{{ $a->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.system.announcements.destroy', $a) }}" class="inline" onsubmit="return confirm('Hapus pengumuman ini?')">
                                @csrf @method('DELETE')
                                <button class="text-rose-300 hover:underline">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-6 text-center text-slate-400">Belum ada pengumuman. Contoh: "Pemeliharaan terjadwal Minggu pukul 01.00–02.00 WIB. Aplikasi tidak bisa diakses sementara."</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $announcements->links() }}</div>
</div>
@endsection
