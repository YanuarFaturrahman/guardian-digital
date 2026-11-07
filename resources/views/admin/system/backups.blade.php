@extends('layouts.app')
@section('title', 'Manajemen Backup')

@section('content')
<div class="rise mx-auto max-w-5xl">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="h1">💾 Manajemen Backup</h1>
            <p class="mt-1 text-sm text-slate-400">Cadangan database terkompres (.gz). Backup otomatis: <strong class="{{ $auto ? 'text-emerald-300' : 'text-amber-300' }}">{{ $auto ? "aktif, simpan {$keep} terbaru" : 'nonaktif' }}</strong>.</p>
        </div>
        <form method="POST" action="{{ route('admin.system.backups.store') }}" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').textContent = 'Membuat backup...';">
            @csrf
            <button class="btn btn-primary">+ Buat backup sekarang</button>
        </form>
    </div>

    <div class="mt-4">@include('admin.system._tabs')</div>

    <div class="mb-4 rounded-xl border border-warn/50 bg-warn/15 px-4 py-3 text-xs text-amber-100">
        ⚠️ Backup disimpan di server ini (<code>storage/app/private/backups</code>). Jika aplikasi berjalan di container atau hosting yang direset saat deploy ulang, file ini ikut hilang. <strong>Unduh backup penting dan simpan di tempat lain.</strong>
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full min-w-[640px] text-left text-sm">
            <thead class="text-xs uppercase text-slate-400">
                <tr><th class="pb-2">File</th><th class="pb-2">Jenis</th><th class="pb-2">Ukuran</th><th class="pb-2">Dibuat</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @forelse ($backups as $backup)
                    <tr>
                        <td class="py-2 font-mono text-xs">{{ $backup['name'] }}</td>
                        <td><span class="chip {{ $backup['type'] === 'auto' ? 'text-guard-400' : 'text-amber-300' }}">{{ $backup['type'] === 'auto' ? 'Otomatis' : 'Manual' }}</span></td>
                        <td class="text-xs">{{ \App\Services\BackupService::formatBytes($backup['size']) }}</td>
                        <td class="text-xs text-slate-300">{{ $backup['time']->format('d M Y H:i') }}<div class="text-slate-500">{{ $backup['time']->diffForHumans() }}</div></td>
                        <td class="space-x-3 whitespace-nowrap text-right">
                            <a href="{{ route('admin.system.backups.download', $backup['name']) }}" class="text-guard-400 hover:underline">Unduh</a>
                            <form method="POST" action="{{ route('admin.system.backups.verify', $backup['name']) }}" class="inline">
                                @csrf
                                <button class="text-emerald-300 hover:underline" title="Periksa apakah arsip utuh dan bisa dipulihkan">Verifikasi</button>
                            </form>
                            <form method="POST" action="{{ route('admin.system.backups.destroy', $backup['name']) }}" class="inline" onsubmit="return confirm('Hapus backup ini?')">
                                @csrf @method('DELETE')
                                <button class="text-rose-300 hover:underline">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-slate-400">Belum ada backup. Klik "Buat backup sekarang".</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <details class="card mt-6 text-sm">
        <summary class="cursor-pointer font-semibold">Cara memulihkan (restore)</summary>
        <div class="mt-3 space-y-2 text-xs text-slate-300">
            <p>Pemulihan sengaja tidak tersedia lewat tombol, karena menimpa seluruh data. Lakukan manual saat mode pemeliharaan aktif:</p>
            @if (in_array($driver, ['mysql', 'mariadb']))
                <pre class="overflow-x-auto rounded-lg bg-black/30 p-3 font-mono">gunzip -c backup-XXXX.sql.gz | mysql -h HOST -P PORT -u USER -p NAMA_DATABASE</pre>
                <p>Untuk database dengan SSL (misalnya Aiven), tambahkan <code>--ssl-ca=certs/aiven-ca.pem</code>.</p>
            @else
                <pre class="overflow-x-auto rounded-lg bg-black/30 p-3 font-mono">gunzip -c backup-XXXX.sqlite.gz &gt; database/database.sqlite</pre>
            @endif
            <p>Backup tidak memuat isi tabel sesi, cache, dan antrean (data sementara), jadi semua pengguna perlu masuk lagi setelah pemulihan.</p>
        </div>
    </details>
</div>
@endsection
