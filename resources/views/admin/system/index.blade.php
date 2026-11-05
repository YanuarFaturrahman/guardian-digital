@extends('layouts.app')
@section('title', 'Keamanan & Sistem')

@section('content')
<div class="rise mx-auto max-w-6xl">
    <h1 class="h1">🛡️ Keamanan &amp; Sistem</h1>
    <p class="mb-4 mt-1 text-sm text-slate-400">Pantau aktivitas, atur konfigurasi aplikasi, dan jaga data tetap aman. Hanya superadmin yang bisa membuka menu ini.</p>

    @include('admin.system._tabs')

    @if ($maintenance)
        <div class="mb-4 rounded-xl border border-warn/50 bg-warn/15 px-4 py-3 text-sm text-amber-100">
            🚧 <strong>Mode pemeliharaan sedang aktif.</strong> Pengguna lain melihat halaman pemeliharaan.
            <a href="{{ route('admin.system.settings') }}" class="underline">Ubah di Pengaturan</a>
        </div>
    @endif

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Aktivitas 24 jam terakhir" :value="$logs24h" icon="📜" />
        <x-stat label="Gagal masuk 24 jam terakhir" :value="$failedLogins24h" icon="🚫" />
        <x-stat label="Sesi login aktif" :value="$activeSessions ?? '-'" icon="🔐" />
        <x-stat label="Backup terakhir" :value="$lastBackup ? $lastBackup['time']->diffForHumans() : 'Belum ada'" icon="💾" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card">
            <h2 class="h2 mb-3">Pemeriksaan keamanan</h2>
            <ul class="space-y-3 text-sm">
                @foreach ($checks as $check)
                    <li class="flex gap-3">
                        <span class="mt-0.5">{{ $check['ok'] ? '✅' : '⚠️' }}</span>
                        <div>
                            <div class="font-medium {{ $check['ok'] ? 'text-emerald-200' : 'text-amber-200' }}">{{ $check['label'] }}</div>
                            @unless ($check['ok'])
                                <div class="text-xs text-slate-400">{{ $check['hint'] }}</div>
                            @endunless
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="card">
            <h2 class="h2 mb-3">Informasi server</h2>
            <dl class="divide-y divide-white/10 text-sm">
                @foreach ($info as $label => $value)
                    <div class="flex justify-between gap-4 py-2">
                        <dt class="text-slate-400">{{ $label }}</dt>
                        <dd class="text-right font-mono text-xs">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>

    <div class="card mt-6">
        <div class="mb-2 flex items-center justify-between">
            <h2 class="h2">Aktivitas terbaru</h2>
            <a href="{{ route('admin.system.logs') }}" class="text-sm text-guard-400 hover:underline">Lihat semua →</a>
        </div>
        @forelse ($recent as $log)
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-white/10 py-2 text-sm last:border-0">
                <span>{{ $log->description }}</span>
                <span class="flex items-center gap-2 text-xs text-slate-400">
                    <x-log-chip :action="$log->action" /> {{ $log->created_at->diffForHumans() }}
                </span>
            </div>
        @empty
            <p class="text-sm text-slate-400">Belum ada aktivitas tercatat.</p>
        @endforelse
    </div>
</div>
@endsection
