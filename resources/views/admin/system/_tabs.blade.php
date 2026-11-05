@php
    $tabs = [
        ['admin.system.index', '🧭 Ringkasan'],
        ['admin.system.logs', '📜 Log Aktivitas'],
        ['admin.system.settings', '⚙️ Pengaturan'],
        ['admin.system.backups', '💾 Backup'],
        ['admin.system.sessions', '🔐 Sesi Aktif'],
    ];
@endphp
<div class="mb-6 flex flex-wrap gap-2">
    @foreach ($tabs as [$route, $label])
        <a href="{{ route($route) }}"
           class="rounded-xl border px-4 py-2 text-sm font-medium transition {{ request()->routeIs($route) ? 'border-guard-500 bg-guard-500/15 text-guard-400' : 'border-white/10 text-slate-300 hover:bg-white/10' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
