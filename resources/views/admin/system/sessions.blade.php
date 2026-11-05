@extends('layouts.app')
@section('title', 'Sesi Aktif')

@section('content')
<div class="rise mx-auto max-w-5xl">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="h1">🔐 Sesi Aktif</h1>
            <p class="mt-1 text-sm text-slate-400">Siapa yang sedang masuk, dari perangkat dan IP mana. Akhiri sesi yang mencurigakan.</p>
        </div>
        @if ($supported && $sessions->count() > 1)
            <form method="POST" action="{{ route('admin.system.sessions.destroy-all') }}" onsubmit="return confirm('Paksa semua pengguna lain keluar sekarang?')">
                @csrf
                <button class="btn btn-danger">Paksa semua keluar</button>
            </form>
        @endif
    </div>

    <div class="mt-4">@include('admin.system._tabs')</div>

    @if (! $supported)
        <div class="card text-sm text-slate-300">
            Fitur ini butuh <code>SESSION_DRIVER=database</code>. Driver sesi saat ini: <code>{{ config('session.driver') }}</code>.
        </div>
    @else
        <div class="card overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="text-xs uppercase text-slate-400">
                    <tr><th class="pb-2">Pengguna</th><th class="pb-2">Perangkat</th><th class="pb-2">IP</th><th class="pb-2">Aktivitas terakhir</th><th></th></tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @foreach ($sessions as $s)
                        <tr>
                            <td class="py-2">
                                {{ $s->name }} @if ($s->id === $currentId)<span class="chip text-guard-400">Kamu</span>@endif
                                <div class="text-xs text-slate-400">{{ $s->email }} · {{ $s->role }}</div>
                            </td>
                            <td class="text-xs">{{ $s->browser }}</td>
                            <td class="font-mono text-xs">{{ $s->ip_address ?? '-' }}</td>
                            <td class="text-xs text-slate-300">{{ $s->last_seen->diffForHumans() }}</td>
                            <td class="text-right">
                                @if ($s->id !== $currentId)
                                    <form method="POST" action="{{ route('admin.system.sessions.destroy', $s->id) }}" onsubmit="return confirm('Akhiri sesi ini?')">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-300 hover:underline">Akhiri</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-xs text-slate-400">Hanya sesi yang aktif dalam {{ config('session.lifetime') }} menit terakhir. Ditambah {{ $guests }} pengunjung tanpa login.</p>
    @endif
</div>
@endsection
