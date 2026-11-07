@extends('layouts.app')
@section('title', 'Kesehatan Sistem')

@section('content')
@php
    $statusInfo = [
        'ok' => ['🟢', 'Sehat', 'border-safe/40 bg-safe/15 text-emerald-100'],
        'degraded' => ['🟠', 'Terganggu', 'border-warn/50 bg-warn/15 text-amber-100'],
        'down' => ['🔴', 'Tidak bisa diakses', 'border-danger/40 bg-danger/15 text-rose-100'],
    ][$result['status']];
    $itemIcon = ['ok' => '✅', 'warn' => '⚠️', 'fail' => '❌'];
@endphp
<div class="rise mx-auto max-w-6xl">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="h1">🩺 Kesehatan Sistem</h1>
            <p class="mt-1 text-sm text-slate-400">Pemeriksaan langsung komponen penting, riwayat, dan uptime. Dipantau otomatis tiap 5 menit oleh penjadwal.</p>
        </div>
        <form method="POST" action="{{ route('admin.system.health.check') }}">
            @csrf
            <button class="btn btn-primary">Periksa & catat sekarang</button>
        </form>
    </div>

    <div class="mt-4">@include('admin.system._tabs')</div>

    <div class="mb-6 rounded-xl border px-4 py-3 text-sm {{ $statusInfo[2] }}">
        {{ $statusInfo[0] }} <strong>Status saat ini: {{ $statusInfo[1] }}</strong>
        @if ($result['status'] !== 'ok')
            <span class="block text-xs opacity-90">Lihat butir bertanda ⚠️ / ❌ di bawah untuk penyebabnya.</span>
        @endif
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Uptime 24 jam" :value="$uptime24h !== null ? $uptime24h . '%' : '-'" icon="⏱️" />
        <x-stat label="Uptime 7 hari" :value="$uptime7d !== null ? $uptime7d . '%' : '-'" icon="📈" />
        <x-stat label="Uptime 30 hari" :value="$uptime30d !== null ? $uptime30d . '%' : '-'" icon="📅" />
        <x-stat label="Rata-rata respons database (24 jam)" :value="$avgDb ? $avgDb . ' ms' : '-'" icon="⚡" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card">
            <h2 class="h2 mb-3">Hasil pemeriksaan</h2>
            <ul class="space-y-3 text-sm">
                @foreach ($result['checks'] as $c)
                    <li class="flex gap-3">
                        <span class="mt-0.5">{{ $itemIcon[$c['status']] }}</span>
                        <div>
                            <div class="font-medium {{ $c['status'] === 'ok' ? 'text-emerald-200' : ($c['status'] === 'warn' ? 'text-amber-200' : 'text-rose-200') }}">
                                {{ $c['label'] }}
                                @unless ($c['critical'])<span class="chip ml-1 text-slate-300">saran</span>@endunless
                            </div>
                            <div class="text-xs text-slate-400">{{ $c['detail'] }}</div>
                        </div>
                    </li>
                @endforeach
            </ul>
            <p class="mt-4 text-xs text-slate-500">Butir "saran" tidak memengaruhi status keseluruhan; hanya butir kritis (database, cache, penyimpanan, disk, ekstensi) yang menentukan.</p>
        </div>

        <div class="space-y-6">
            <div class="card">
                <h2 class="h2 mb-2">Pantau dari luar (uptime monitor)</h2>
                <p class="text-xs text-slate-400">Daftarkan alamat di bawah ke layanan gratis seperti UptimeRobot atau BetterStack agar kamu tahu jika situs mati, termasuk saat server tidak bisa menjalankan penjadwalnya sendiri.</p>
                <pre class="mt-3 overflow-x-auto rounded-lg bg-black/30 p-3 font-mono text-xs">{{ $statusUrl }}</pre>
                <p class="mt-2 text-xs text-slate-400">Mengembalikan JSON <code>{"status":"ok|degraded|down|maintenance"}</code>: kode 200 jika layanan hidup, 503 jika database tidak terjangkau atau mode pemeliharaan aktif. Tidak membuka detail konfigurasi.</p>
            </div>

            <div class="card">
                <h2 class="h2 mb-2">Denyut penjadwal</h2>
                @if ($beat)
                    <p class="text-sm">Terakhir berjalan <strong>{{ $beat->diffForHumans() }}</strong> <span class="text-xs text-slate-400">({{ $beat->format('d M Y H:i') }})</span>.</p>
                @else
                    <p class="text-sm text-amber-200">Belum pernah berjalan.</p>
                @endif
                <p class="mt-2 text-xs text-slate-400">Backup otomatis, pembersihan data, dan riwayat uptime bergantung pada penjadwal. Server biasa: cron <code>* * * * * php artisan schedule:run</code>. Hosting tanpa cron: isi <code>LAZY_SCHEDULER=true</code>.</p>
            </div>
        </div>
    </div>

    <div class="card mt-6">
        <h2 class="h2 mb-3">Riwayat pemeriksaan <span class="text-xs font-normal text-slate-400">(48 terakhir)</span></h2>
        @if ($history->isEmpty())
            <p class="text-sm text-slate-400">Belum ada riwayat. Klik "Periksa &amp; catat sekarang", atau tunggu penjadwal berjalan.</p>
        @else
            <div class="mb-4 flex flex-wrap gap-1" aria-label="Grafik status, kiri terlama">
                @foreach ($history->reverse() as $h)
                    <span title="{{ $h->created_at->format('d M H:i') }}: {{ $h->status }}"
                          class="h-6 w-2 rounded-sm {{ ['ok' => 'bg-safe', 'degraded' => 'bg-warn', 'down' => 'bg-danger'][$h->status] ?? 'bg-slate-500' }}"></span>
                @endforeach
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] text-left text-sm">
                    <thead class="text-xs uppercase text-slate-400">
                        <tr><th class="pb-2">Waktu</th><th class="pb-2">Status</th><th class="pb-2">DB</th><th class="pb-2">Disk bebas</th><th class="pb-2">Sumber</th><th class="pb-2">Masalah</th></tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @foreach ($history->take(12) as $h)
                            <tr>
                                <td class="py-2 text-xs">{{ $h->created_at->format('d M Y H:i') }}</td>
                                <td><span class="chip {{ ['ok' => 'text-emerald-300', 'degraded' => 'text-amber-300', 'down' => 'text-rose-300'][$h->status] ?? '' }}">{{ $h->status }}</span></td>
                                <td class="text-xs">{{ $h->db_ms !== null ? $h->db_ms . ' ms' : '-' }}</td>
                                <td class="text-xs">{{ $h->disk_free_pct !== null ? $h->disk_free_pct . '%' : '-' }}</td>
                                <td class="text-xs text-slate-400">{{ $h->source === 'manual' ? 'Manual' : 'Penjadwal' }}</td>
                                <td class="text-xs text-slate-300">{{ $h->issues ? implode('; ', $h->issues) : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
