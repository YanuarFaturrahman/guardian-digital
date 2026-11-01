@extends('layouts.app')
@section('title', 'Ubah Skenario')

@section('content')
@php $nodeOptions = $scenario->nodes->pluck('node_key', 'id'); @endphp

<div class="rise mx-auto max-w-4xl space-y-6">
    <div>
        <a href="{{ route('admin.scenarios.index') }}" class="text-sm text-slate-400 hover:text-white">← Daftar skenario</a>
        <h1 class="h1 mt-2">{{ $scenario->title }}</h1>
        <p class="text-sm text-slate-400">
            Slug: {{ $scenario->slug }} · dimainkan {{ $plays }}x ·
            {{ $scenario->is_published ? 'Terbit' : 'Disembunyikan' }}
        </p>
    </div>

    @if ($problems)
        <div class="rounded-xl border border-warn/50 bg-warn/15 px-4 py-3 text-sm text-amber-100">
            <strong>Belum siap diterbitkan:</strong>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($problems as $problem)<li>{{ $problem }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.scenarios.update', $scenario) }}" class="card space-y-4">
        @csrf @method('PUT')
        <h2 class="text-lg font-semibold">Informasi skenario</h2>
        @include('admin.scenarios._form')
        <button class="btn btn-primary">Simpan perubahan</button>
    </form>

    <div>
        <h2 class="mb-3 text-lg font-semibold">Adegan dan pilihan</h2>
        <div class="space-y-4">
            @foreach ($scenario->nodes->sortBy('id') as $node)
                @include('admin.scenarios._node', ['node' => $node, 'nodeOptions' => $nodeOptions])
            @endforeach
        </div>

        <details class="card mt-4">
            <summary class="cursor-pointer font-semibold text-guard-400">+ Tambah adegan</summary>
            <div class="mt-3">
                @include('admin.scenarios._node_form', [
                    'node' => new App\Models\ScenarioNode(['kind' => 'story']),
                    'action' => route('admin.nodes.store', $scenario),
                    'method' => 'POST',
                ])
            </div>
        </details>
    </div>
</div>
@endsection