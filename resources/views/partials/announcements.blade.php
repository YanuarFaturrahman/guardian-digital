@php
    $__announcements = \App\Models\Announcement::visibleFor(auth()->user()?->role);
    $__styles = [
        'info' => 'border-guard-500/40 bg-guard-500/15 text-cyan-50',
        'warning' => 'border-warn/50 bg-warn/15 text-amber-100',
        'danger' => 'border-danger/40 bg-danger/15 text-rose-100',
    ];
    $__icons = ['info' => '📣', 'warning' => '⚠️', 'danger' => '🚨'];
@endphp
@if ($__announcements)
    <div class="mx-auto max-w-6xl space-y-2 px-4 pt-4" data-announcements>
        @foreach ($__announcements as $__a)
            <div role="{{ $__a['level'] === 'danger' ? 'alert' : 'status' }}" data-announcement-id="{{ $__a['id'] }}" data-dismissible="{{ $__a['level'] === 'danger' ? '0' : '1' }}"
                 class="flex items-start justify-between gap-3 rounded-xl border px-4 py-3 text-sm {{ $__styles[$__a['level']] ?? $__styles['info'] }}">
                <span>{{ $__icons[$__a['level']] ?? '📣' }} {{ $__a['message'] }}</span>
                @if ($__a['level'] !== 'danger')
                    <button type="button" data-dismiss aria-label="Tutup pengumuman" class="shrink-0 text-lg leading-none opacity-70 hover:opacity-100">×</button>
                @endif
            </div>
        @endforeach
    </div>
    <script>
        (function () {
            var box = document.querySelector('[data-announcements]');
            if (!box) return;
            var key = 'gd-dismissed-announcements', seen = [];
            try { seen = JSON.parse(sessionStorage.getItem(key) || '[]'); } catch (e) {}
            box.querySelectorAll('[data-announcement-id]').forEach(function (el) {
                var id = el.getAttribute('data-announcement-id');
                if (el.getAttribute('data-dismissible') === '1' && seen.indexOf(id) !== -1) { el.remove(); return; }
                var btn = el.querySelector('[data-dismiss]');
                if (btn) btn.addEventListener('click', function () {
                    seen.push(id);
                    try { sessionStorage.setItem(key, JSON.stringify(seen)); } catch (e) {}
                    el.remove();
                });
            });
        })();
    </script>
@endif
