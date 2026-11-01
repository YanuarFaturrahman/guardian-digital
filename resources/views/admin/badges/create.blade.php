@extends('layouts.app')
@section('title', 'Lencana Baru')

@section('content')
<div class="rise mx-auto max-w-3xl">
    <a href="{{ route('admin.badges.index') }}" class="text-sm text-slate-400 hover:text-white">← Daftar lencana</a>
    <h1 class="h1 mb-4 mt-2">Lencana Baru</h1>

    <form method="POST" action="{{ route('admin.badges.store') }}" class="card space-y-4">
        @csrf
        @include('admin.badges._form')
        <button class="btn btn-primary">Simpan lencana</button>
    </form>
</div>
@endsection