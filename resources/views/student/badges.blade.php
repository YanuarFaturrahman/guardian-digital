@extends('layouts.app')
@section('title', 'Lencana')

@section('content')
<div class="rise">
    <h1 class="h1">🏅 Guardian Badge</h1>
    <p class="mb-6 text-sm text-slate-400">{{ $earned->count() }} dari {{ $badges->count() }} lencana terkumpul.</p>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($badges as $badge)
            @php $owned = $earned->get($badge->id); @endphp
            <div class="card text-center {{ $owned ? 'border-warn/50 bg-warn/10' : 'opacity-60' }}">
                <div class="text-5xl {{ $owned ? '' : 'grayscale' }}">{{ $badge->icon }}</div>
                <div class="mt-2 font-semibold">{{ $badge->name }}</div>
                <p class="mt-1 text-sm text-slate-300">{{ $badge->description }}</p>
                <div class="mt-2 text-xs text-slate-400">
                    @if ($owned)
                        Diraih {{ $owned->pivot->created_at->translatedFormat('d F Y') }}
                    @else
                        🔒 Belum terbuka
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
