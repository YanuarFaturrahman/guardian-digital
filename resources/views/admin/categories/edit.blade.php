@extends('layouts.app')
@section('title', 'Ubah Kategori')

@section('content')
<div class="rise mx-auto max-w-3xl">
    <a href="{{ route('admin.categories.index') }}" class="text-sm text-slate-400 hover:text-white">← Daftar kategori</a>
    <h1 class="h1 mb-1 mt-2">Ubah Kategori</h1>
    <p class="mb-4 text-xs text-slate-400">Slug: <span class="font-mono">{{ $category->slug }}</span> (tidak dapat diubah)</p>

    <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="card space-y-4">
        @csrf @method('PUT')
        @include('admin.categories._form')
        <button class="btn btn-primary">Simpan perubahan</button>
    </form>
</div>
@endsection