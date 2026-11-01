@extends('layouts.app')
@section('title', 'Skenario Baru')

@section('content')
<div class="rise mx-auto max-w-3xl">
    <a href="{{ route('admin.scenarios.index') }}" class="text-sm text-slate-400 hover:text-white">← Daftar skenario</a>
    <h1 class="h1 mb-4 mt-2">Skenario Baru</h1>

    <form method="POST" action="{{ route('admin.scenarios.store') }}" class="card space-y-4">
        @csrf
        @include('admin.scenarios._form')
        <button class="btn btn-primary">Simpan dan lanjut ke adegan</button>
    </form>
</div>
@endsection