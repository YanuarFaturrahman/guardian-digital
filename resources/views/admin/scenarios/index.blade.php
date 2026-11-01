@extends('layouts.app')
@section('title', 'Kelola Skenario')

@section('content')
<div class="rise mx-auto max-w-5xl">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="h1">Kelola Skenario</h1>
            <p class="text-sm text-slate-400">Skenario yang disembunyikan tidak muncul bagi siswa, tetapi datanya tetap tersimpan.</p>
        </div>
        <a href="{{ route('admin.scenarios.create') }}" class="btn btn-primary">+ Skenario Baru</a>
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="text-xs uppercase text-slate-400">
                <tr><th class="pb-2">Judul</th><th class="pb-2">Kategori</th><th class="pb-2">Dimainkan</th><th class="pb-2">Rata-rata</th><th class="pb-2">Status</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @foreach ($scenarios as $scenario)
                    <tr>
                        <td class="py-2">{{ $scenario->title }}</td>
                        <td>{{ $scenario->category->icon }} {{ $scenario->category->name }}</td>
                        <td>{{ $scenario->plays }}x</td>
                        <td>{{ $scenario->avg_score !== null ? round($scenario->avg_score) : '-' }}</td>
                        <td>
                            <span class="chip {{ $scenario->is_published ? 'text-safe' : 'text-warn' }}">
                                {{ $scenario->is_published ? 'Terbit' : 'Disembunyikan' }}
                            </span>
                        </td>
                        <td class="space-x-3 whitespace-nowrap">
                            <a href="{{ route('admin.scenarios.edit', $scenario) }}" class="text-guard-400 hover:underline">Ubah</a>
                            <form method="POST" action="{{ route('admin.scenarios.toggle', $scenario) }}" class="inline">
                                @csrf
                                <button class="text-guard-400 hover:underline">{{ $scenario->is_published ? 'Sembunyikan' : 'Terbitkan' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.scenarios.destroy', $scenario) }}" class="inline"
                                  onsubmit="return confirm('Hapus skenario ini secara permanen?')">
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