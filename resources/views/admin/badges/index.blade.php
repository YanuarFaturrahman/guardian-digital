@extends('layouts.app')
@section('title', 'Kelola Lencana')

@section('content')
<div class="rise mx-auto max-w-5xl">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="h1">Kelola Lencana</h1>
            <p class="text-sm text-slate-400">Lencana terbuka otomatis saat siswa memenuhi syaratnya.</p>
        </div>
        <a href="{{ route('admin.badges.create') }}" class="btn btn-primary">+ Lencana Baru</a>
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="text-xs uppercase text-slate-400">
                <tr><th class="pb-2">Lencana</th><th class="pb-2">Syarat</th><th class="pb-2">Dimiliki</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @foreach ($badges as $badge)
                    <tr>
                        <td class="py-2">
                            <span class="mr-1 text-xl">{{ $badge->icon }}</span> {{ $badge->name }}
                            <div class="text-xs text-slate-400">{{ $badge->description }}</div>
                        </td>
                        <td class="text-slate-300">{{ $badge->requirementText() }}</td>
                        <td>{{ $badge->users_count }} siswa</td>
                        <td class="space-x-3 whitespace-nowrap">
                            <a href="{{ route('admin.badges.edit', $badge) }}" class="text-guard-400 hover:underline">Ubah</a>
                            <form method="POST" action="{{ route('admin.badges.destroy', $badge) }}" class="inline"
                                  onsubmit="return confirm('Hapus lencana ini? Dimiliki {{ $badge->users_count }} siswa dan akan hilang dari koleksi mereka.')">
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