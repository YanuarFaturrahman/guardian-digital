@extends('layouts.app')
@section('title', 'Kelola Materi')

@section('content')
<div class="rise mx-auto max-w-5xl">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="h1">📚 Kelola Materi Belajar</h1>
            <p class="text-sm text-slate-400">{{ $total }} materi. Siswa melihatnya di menu Belajar.</p>
        </div>
        <a href="{{ route('admin.lessons.create') }}" class="btn btn-primary">+ Materi baru</a>
    </div>

    @foreach ($groups as $categoryId => $items)
        @php $category = $categories[$categoryId]; @endphp
        <section class="mb-6">
            <h2 class="h2 mb-2" style="color: {{ $category->color }}">
                {{ $category->icon }} {{ $category->name }}
                <span class="text-xs font-normal text-slate-400">({{ $items->count() }} materi)</span>
            </h2>
            <div class="card overflow-x-auto !p-0">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="text-xs uppercase text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Urutan</th><th class="py-3">Judul</th>
                            <th class="py-3">Baca</th><th class="py-3">XP</th>
                            <th class="py-3">Selesai oleh</th><th></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @foreach ($items as $lesson)
                            <tr>
                                <td class="px-4 py-3 text-slate-400">{{ $lesson->position }}</td>
                                <td class="py-3 pr-3">
                                    <div class="font-medium">{{ $lesson->title }}</div>
                                    <div class="text-xs text-slate-400">{{ $lesson->summary }}</div>
                                </td>
                                <td class="py-3">{{ $lesson->read_minutes }} mnt</td>
                                <td class="py-3">+{{ $lesson->xp_reward }}</td>
                                <td class="py-3">{{ $lesson->readers_count }} siswa</td>
                                <td class="py-3 pr-4 text-right">
                                    <a href="{{ route('admin.lessons.edit', $lesson) }}" class="text-guard-400 hover:underline">Ubah</a>
                                    <form method="POST" action="{{ route('admin.lessons.destroy', $lesson) }}"
                                          class="ml-3 inline"
                                          onsubmit="return confirm('Hapus materi ini? Catatan siswa yang sudah membacanya ikut terhapus (XP mereka tetap).')">
                                        @csrf @method('DELETE')
                                        <button class="text-danger hover:underline">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach

    {{-- Kategori yang belum punya materi --}}
    @php $emptyCategories = $categories->except($groups->keys()->all()); @endphp
    @if ($emptyCategories->isNotEmpty())
        <section class="card">
            <h2 class="h2 mb-2">Kategori belum punya materi</h2>
            <div class="flex flex-wrap gap-2">
                @foreach ($emptyCategories as $category)
                    <a href="{{ route('admin.lessons.create', ['category' => $category->id]) }}"
                       class="chip hover:bg-white/20">{{ $category->icon }} {{ $category->name }} + tambah</a>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection