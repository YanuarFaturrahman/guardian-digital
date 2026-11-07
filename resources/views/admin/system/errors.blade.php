@extends('layouts.app')
@section('title', 'Error Aplikasi')

@section('content')
<div class="rise mx-auto max-w-6xl">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="h1">🐞 Error Aplikasi</h1>
            <p class="mt-1 text-sm text-slate-400">Error yang tertangkap saat aplikasi berjalan. Kejadian yang sama digabung dan dihitung, jadi 1.000 kali error yang sama hanya satu baris.</p>
        </div>
        <div class="flex gap-2">
            @if ($openCount)
                <form method="POST" action="{{ route('admin.system.errors.resolve-all') }}" onsubmit="return confirm('Tandai semua error terbuka sebagai selesai?')">
                    @csrf
                    <button class="btn btn-ghost">Tandai semua selesai</button>
                </form>
            @endif
            @if ($resolvedCount)
                <form method="POST" action="{{ route('admin.system.errors.clear-resolved') }}" onsubmit="return confirm('Hapus permanen semua catatan error yang sudah selesai?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-ghost text-rose-300">Hapus yang selesai</button>
                </form>
            @endif
        </div>
    </div>

    <div class="mt-4">@include('admin.system._tabs')</div>

    <div class="mb-4 grid gap-4 sm:grid-cols-3">
        <x-stat label="Belum selesai" :value="$openCount" icon="🔥" />
        <x-stat label="Muncul dalam 24 jam" :value="$last24h" icon="🕒" />
        <x-stat label="Sudah selesai" :value="$resolvedCount" icon="✅" />
    </div>

    <form method="GET" class="card mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="label" for="status">Tampilkan</label>
            <select id="status" name="status" class="input">
                <option value="open" @selected($status === 'open')>Belum selesai</option>
                <option value="resolved" @selected($status === 'resolved')>Sudah selesai</option>
                <option value="all" @selected($status === 'all')>Semua</option>
            </select>
        </div>
        <div class="min-w-[14rem] flex-1">
            <label class="label" for="q">Cari (pesan, kelas, file, alamat)</label>
            <input id="q" name="q" value="{{ $q }}" class="input" maxlength="100">
        </div>
        <button class="btn btn-primary">Terapkan</button>
    </form>

    <div class="space-y-3">
        @forelse ($entries as $e)
            <details class="card {{ $e->isResolved() ? 'opacity-70' : '' }}">
                <summary class="cursor-pointer list-none">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <div class="break-words text-sm font-semibold {{ $e->isResolved() ? 'text-slate-300' : 'text-rose-200' }}">{{ class_basename($e->class) }}</div>
                            <div class="mt-0.5 break-words text-sm text-slate-200">{{ $e->message }}</div>
                            <div class="mt-1 font-mono text-xs text-slate-500">{{ $e->file }}:{{ $e->line }}</div>
                        </div>
                        <div class="flex flex-col items-end gap-1 text-xs text-slate-400">
                            <span class="chip {{ $e->occurrences > 10 ? 'text-rose-300' : 'text-amber-300' }}">{{ number_format($e->occurrences) }}× kejadian</span>
                            <span>terakhir {{ $e->last_seen_at->diffForHumans() }}</span>
                            @if ($e->isResolved())<span class="chip text-emerald-300">selesai</span>@endif
                        </div>
                    </div>
                </summary>

                <div class="mt-4 space-y-3 border-t border-white/10 pt-4 text-xs text-slate-300">
                    <dl class="grid gap-x-6 gap-y-1 sm:grid-cols-2">
                        <div><dt class="inline text-slate-500">Kelas penuh:</dt> <dd class="inline font-mono">{{ $e->class }}</dd></div>
                        <div><dt class="inline text-slate-500">Pertama terlihat:</dt> <dd class="inline">{{ $e->first_seen_at->format('d M Y H:i') }}</dd></div>
                        <div><dt class="inline text-slate-500">Permintaan terakhir:</dt> <dd class="inline font-mono">{{ $e->method }} {{ $e->url }}</dd></div>
                        <div><dt class="inline text-slate-500">ID pengguna terakhir:</dt> <dd class="inline">{{ $e->user_id ?? '-' }}</dd></div>
                    </dl>
                    @if ($e->trace)
                        <div>
                            <div class="mb-1 text-slate-500">Jejak tumpukan (10 frame teratas, tanpa argumen)</div>
                            <pre class="overflow-x-auto rounded-lg bg-black/30 p-3 font-mono leading-relaxed">{{ $e->trace }}</pre>
                        </div>
                    @endif
                    <div class="flex gap-4">
                        <form method="POST" action="{{ route('admin.system.errors.resolve', $e) }}">
                            @csrf
                            <button class="text-guard-400 hover:underline">{{ $e->isResolved() ? 'Buka kembali' : 'Tandai selesai' }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.system.errors.destroy', $e) }}" onsubmit="return confirm('Hapus catatan error ini?')">
                            @csrf @method('DELETE')
                            <button class="text-rose-300 hover:underline">Hapus</button>
                        </form>
                    </div>
                </div>
            </details>
        @empty
            <div class="card py-8 text-center text-sm text-slate-400">
                @if ($status === 'open' && $q === '') 🎉 Tidak ada error yang belum selesai. @else Tidak ada catatan yang cocok. @endif
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $entries->links() }}</div>
    <p class="mt-3 text-xs text-slate-500">Muncul lagi setelah ditandai selesai = otomatis dibuka kembali. Error 4xx (halaman tidak ditemukan, validasi, belum login) tidak dicatat. Alamat disamarkan dari token, tanpa query string, dan email di pesan error disensor.</p>
</div>
@endsection
