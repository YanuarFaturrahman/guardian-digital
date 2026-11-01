@props(['value' => 0, 'color' => '#22d3ee'])

<div class="h-2.5 w-full overflow-hidden rounded-full bg-white/10">
    <div class="h-full rounded-full transition-all"
         style="width: {{ max(0, min(100, (int) $value)) }}%; background: {{ $color }}"></div>
</div>
