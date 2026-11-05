@extends('layouts.app')
@section('title', 'Log Aktivitas')

@section('content')
<div class="rise mx-auto max-w-6xl">
    <h1 class="h1">📜 Log Aktivitas</h1>
    <p class="mb-4 mt-1 text-sm text-slate-400">Jejak audit: siapa melakukan apa, kapan, dan dari IP mana. Log tidak bisa diubah dari aplikasi dan dihapus otomatis sesuai batas simpan di Pengaturan.</p>

    @include('admin.system._tabs')

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <input name="q" value="{{ $filters['q'] ?? '' }}" class="input max-w-xs" placeholder="Cari pelaku, kegiatan, atau IP...">
        <select name="kategori" class="input max-w-[12rem]">
            <option value="">Semua kategori</option>
            @foreach ($categories as $value => $label)
                <option value="{{ $value }}" @selected(($filters['kategori'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <div>
            <label class="label !mb-0 text-xs">Dari</label>
            <input type="date" name="dari" value="{{ $filters['dari'] ?? '' }}" class="input">
        </div>
        <div>
            <label class="label !mb-0 text-xs">Sampai</label>
            <input type="date" name="sampai" value="{{ $filters['sampai'] ?? '' }}" class="input">
        </div>
        <button class="btn btn-ghost">Terapkan</button>
        <a href="{{ route('admin.system.logs') }}" class="btn btn-ghost">Reset</a>
    </form>

    <div class="card overflow-x-auto">
        <table class="w-full min-w-[820px] text-left text-sm">
            <thead class="text-xs uppercase text-slate-400">
                <tr><th class="pb-2">Waktu</th><th class="pb-2">Pelaku</th><th class="pb-2">Aktivitas</th><th class="pb-2">IP</th></tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @forelse ($logs as $log)
                    <tr class="align-top">
                        <td class="whitespace-nowrap py-2 text-xs text-slate-300">
                            {{ $log->created_at->format('d M Y H:i:s') }}
                            <div class="text-slate-500">{{ $log->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="py-2">
                            @if ($log->actor_name)
                                {{ $log->actor_name }}
                                <div class="text-xs text-slate-400">{{ $log->actor_role }}</div>
                            @else
                                <span class="text-xs text-slate-400">Sistem / tamu</span>
                            @endif
                        </td>
                        <td class="py-2">
                            <x-log-chip :action="$log->action" />
                            <div class="mt-1">{{ $log->description }}</div>
                            @if ($log->properties)
                                <details class="mt-1 text-xs text-slate-400">
                                    <summary class="cursor-pointer hover:text-slate-200">Detail</summary>
                                    <pre class="mt-1 whitespace-pre-wrap break-all rounded-lg bg-black/30 p-2 font-mono">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </details>
                            @endif
                        </td>
                        <td class="whitespace-nowrap py-2 font-mono text-xs">{{ $log->ip_address ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-6 text-center text-slate-400">Tidak ada log yang cocok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
</div>
@endsection
