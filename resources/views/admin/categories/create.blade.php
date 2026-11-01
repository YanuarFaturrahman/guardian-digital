@extends('layouts.app')
@section('title', 'Kategori Baru')

@section('content')
<div class="rise mx-auto max-w-3xl">
    <a href="{{ route('admin.categories.index') }}" class="text-sm text-slate-400 hover:text-white">← Daftar kategori</a>
    <h1 class="h1 mb-4 mt-2">Kategori Baru</h1>

    <form method="POST" action="{{ route('admin.categories.store') }}" class="card space-y-4">
        @csrf
        @include('admin.categories._form')
        <button class="btn btn-primary">Simpan kategori</button>
    </form>
</div>
@endsection