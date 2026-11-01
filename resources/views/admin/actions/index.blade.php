@extends('layouts.app')
@section('title', 'Kelola Aksi')

@section('content')
<div class="rise mx-auto max-w-5xl">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="h1">⚡ Kelola Aksi Keamanan</h1>
            <p class="text-sm text-slate-400">{{ $actions->count() }} aksi. Siswa melihatnya sebagai checklist di Pusat Aksi.</p>
        </div>
        <a href="{{ route('admin.actions.create') }}" class="btn btn-primary">+ Aksi baru</a>
    </div>

    <div class="card overflow-x-auto !p-0">
        <table class="w-full min-w-[640px] text-left text-sm">
            <thead class="text-xs uppercase text-slate-400">
                <tr>
                    <th class="px-4 py-3">Urutan</th><th class="py-3">Aksi</th>
                    <th class="py-3">XP</th><th class="py-3">Dilakukan oleh</th><th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @forelse ($actions as $action)
                    <tr>
                        <td class="px-4 py-3 text-slate-400">{{ $action->position }}</td>
                        <td class="py-3 pr-3">
                            <div class="font-medium">{{ $action->icon }} {{ $action->title }}</div>
                            <div class="text-xs text-slate-400">{{ \Illuminate\Support\Str::limit($action->description, 110) }}</div>
                        </td>
                        <td class="py-3">+{{ $action->xp_reward }}</td>
                        <td class="py-3">{{ $action->users_count }} siswa</td>
                        <td class="py-3 pr-4 text-right">
                            <a href="{{ route('admin.actions.edit', $action) }}" class="text-guard-400 hover:underline">Ubah</a>
                            <form method="POST" action="{{ route('admin.actions.destroy', $action) }}" class="ml-3 inline"
                                  onsubmit="return confirm('Hapus aksi ini? Catatan siswa yang sudah melakukannya ikut terhapus (XP mereka tetap).')">
                                @csrf @method('DELETE')
                                <button class="text-danger hover:underline">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-6 text-center text-slate-400">Belum ada aksi. Klik "+ Aksi baru".</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection