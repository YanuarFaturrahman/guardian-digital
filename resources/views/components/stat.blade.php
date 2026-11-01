@props(['label', 'value', 'icon' => '⭐'])

<div class="card flex items-center gap-4">
    <div class="text-3xl">{{ $icon }}</div>
    <div>
        <div class="text-2xl font-bold">{{ $value }}</div>
        <div class="text-xs text-slate-400">{{ $label }}</div>
    </div>
</div>
