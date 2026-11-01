@extends('layouts.app')
@section('title', 'Kelola Kategori')

@section('content')
<div class="rise mx-auto max-w-5xl">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="h1">Kelola Kategori</h1>
            <p class="text-sm text-slate-400">Kategori yang masih punya skenario, materi, atau lencana tidak bisa dihapus.</p>
        </div>
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">+ Kategori Baru</a>
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="text-xs uppercase text-slate-400">
                <tr><th class="pb-2">Kategori</th><th class="pb-2">Slug</th><th class="pb-2">Warna</th><th class="pb-2">Skenario</th><th class="pb-2">Materi</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @foreach ($categories as $category)
                    <tr>
                        <td class="py-2">
                            {{ $category->icon }} {{ $category->name }}
                            <div class="text-xs text-slate-400">{{ $category->tagline }}</div>
                        </td>
                        <td class="font-mono text-xs">{{ $category->slug }}</td>
                        <td class="whitespace-nowrap">
                            <span class="inline-block h-4 w-4 rounded-full align-middle" style="background: {{ $category->color }}"></span>
                            <span class="text-xs text-slate-400">{{ $category->color }}</span>
                        </td>
                        <td>{{ $category->scenarios_count }}</td>
                        <td>{{ $category->lessons_count }}</td>
                        <td class="space-x-3 whitespace-nowrap">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="text-guard-400 hover:underline">Ubah</a>
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="inline"
                                  onsubmit="return confirm('Hapus kategori ini?')">
                                @csrf @method('DELETE')
                                <button class="text-rose-300 hover:underline">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection