@extends('layouts.app')
@section('title', 'Pemeliharaan Data')

@section('content')
<div class="rise mx-auto max-w-5xl">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="h1">🧹 Pemeliharaan Data &amp; Cache</h1>
            <p class="mt-1 text-sm text-slate-400">Buang data sementara yang sudah tidak berguna supaya database tidak membengkak dan aplikasi tetap cepat. Data inti (pengguna, skenario, progres belajar) tidak pernah disentuh.</p>
        </div>
    </div>

    <div class="mt-4">@include('admin.system._tabs')</div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card">
            <h2 class="h2 mb-1">Data kedaluwarsa</h2>
            <p class="mb-3 text-xs text-slate-400">Dibersihkan otomatis tiap hari pukul 04:00 oleh penjadwal. Bisa juga dijalankan sekarang.</p>
            <ul class="divide-y divide-white/10 text-sm">
                @foreach ($labels as $key => $label)
                    <li class="flex items-center justify-between gap-4 py-2">
                        <span>{{ $label }}</span>
                        <span class="chip {{ $pending[$key] > 0 ? 'text-amber-300' : 'text-slate-400' }}">{{ number_format($pending[$key]) }}</span>
                    </li>
                @endforeach
            </ul>
            <form method="POST" action="{{ route('admin.system.maintenance.purge') }}" class="mt-4">
                @csrf
                <button class="btn btn-primary" @disabled($pendingTotal === 0)>Bersihkan sekarang ({{ number_format($pendingTotal) }} baris)</button>
            </form>
            <p class="mt-2 text-xs text-slate-500">Batas simpan log aktivitas, error, dan riwayat kesehatan diatur di tab Pengaturan.</p>
        </div>

        <div class="card">
            <h2 class="h2 mb-1">Cache</h2>
            <p class="mb-3 text-xs text-slate-400">Driver cache: <code>{{ $cacheDriver }}</code> · driver sesi: <code>{{ $sessionDriver }}</code></p>

            <div class="space-y-4 text-sm">
                <form method="POST" action="{{ route('admin.system.maintenance.settings-cache') }}" class="flex items-start justify-between gap-3">
                    @csrf
                    <div>
                        <div class="font-medium">Muat ulang cache pengaturan</div>
                        <div class="text-xs text-slate-400">Aman. Dipakai jika pengaturan atau pengumuman terasa tidak berubah.</div>
                    </div>
                    <button class="btn btn-ghost shrink-0">Muat ulang</button>
                </form>

                <form method="POST" action="{{ route('admin.system.maintenance.views') }}" class="flex items-start justify-between gap-3">
                    @csrf
                    <div>
                        <div class="font-medium">Bersihkan cache tampilan</div>
                        <div class="text-xs text-slate-400">Aman. Halaman dikompilasi ulang saat dibuka, jadi pembukaan pertama sedikit lebih lambat.</div>
                    </div>
                    <button class="btn btn-ghost shrink-0">Bersihkan</button>
                </form>

                <form method="POST" action="{{ route('admin.system.maintenance.flush-cache') }}" class="flex items-start justify-between gap-3"
                      onsubmit="return confirm('Kosongkan SELURUH cache aplikasi? Hitungan penguncian login ikut terhapus.')">
                    @csrf
                    <div>
                        <div class="font-medium text-amber-200">Kosongkan seluruh cache</div>
                        <div class="text-xs text-slate-400">Hati-hati: hitungan percobaan masuk yang gagal (penguncian akun) ikut ter-reset{{ $cacheDriver === 'database' ? ', dan semua entri tabel cache dihapus' : '' }}.</div>
                    </div>
                    <button class="btn btn-danger shrink-0">Kosongkan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="card mt-6">
        <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
            <h2 class="h2">Ukuran database</h2>
            <span class="text-sm text-slate-300">Total: <strong>{{ $dbSize !== null ? \App\Services\BackupService::formatBytes($dbSize) : 'tidak diketahui' }}</strong></span>
        </div>
        @if ($tables)
            <div class="overflow-x-auto">
                <table class="w-full min-w-[420px] text-left text-sm">
                    <thead class="text-xs uppercase text-slate-400">
                        <tr><th class="pb-2">Tabel</th><th class="pb-2 text-right">Baris</th><th class="pb-2 text-right">Ukuran</th></tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @foreach ($tables as $t)
                            <tr>
                                <td class="py-2 font-mono text-xs">{{ $t['name'] }}</td>
                                <td class="text-right text-xs">{{ number_format($t['rows']) }}</td>
                                <td class="text-right text-xs">{{ $t['bytes'] !== null ? \App\Services\BackupService::formatBytes($t['bytes']) : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-2 text-xs text-slate-500">Jumlah baris di MySQL adalah perkiraan dari information_schema. Pantau tabel yang tumbuh cepat (biasanya <code>activity_logs</code> dan <code>sessions</code>).</p>
        @else
            <p class="text-sm text-slate-400">Informasi ukuran tabel tidak tersedia untuk database ini.</p>
        @endif
    </div>
</div>
@endsection
