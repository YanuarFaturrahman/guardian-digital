@props(['action'])
@php
    $category = explode('.', $action)[0];
    $color = [
        'auth' => 'text-guard-400',
        'user' => 'text-amber-300',
        'content' => 'text-emerald-300',
        'school' => 'text-violet-300',
        'system' => 'text-rose-300',
    ][$category] ?? 'text-slate-300';
@endphp
<span class="chip whitespace-nowrap font-mono {{ $color }}">{{ $action }}</span>
