@extends('layouts.app')
@section('title', 'Laporan ' . $student->name)

@section('content')
<div class="rise mx-auto max-w-4xl">
    <div class="mb-3 flex items-center justify-between">
        <a href="{{ route('family.dashboard') }}" class="text-sm text-slate-400 hover:text-white">← Semua anak</a>
        <form method="POST" action="{{ route('family.unlink', $student) }}"
              onsubmit="return confirm('Lepas tautan akun anak ini?')">
            @csrf @method('DELETE')
            <button class="text-xs text-slate-400 hover:text-danger">Lepas tautan</button>
        </form>
    </div>

    @include('partials.student-report')

    @include('partials.child-privacy')

    @if ($weakest && $weakest->mastery < 100)
        <div class="card mt-6 border-warn/40">
            <h2 class="h2">💬 Ide Obrolan Keluarga</h2>
            <p class="mt-1 text-sm text-slate-300">
                Kategori yang paling perlu dilatih: <strong>{{ $weakest->icon }} {{ $weakest->name }}</strong>
                ({{ $weakest->mastery }}%).
            </p>
            <p class="mt-2 text-sm text-slate-200">{{ $weakest->family_tip }}</p>
            <ul class="mt-3 list-disc space-y-1 pl-6 text-sm text-slate-300">
                <li>Mulai dengan rasa ingin tahu: "Misi mana yang paling seru? Kamu pilih apa?"</li>
                <li>Hindari menyalahkan. Pesannya: apa pun yang terjadi, kamu boleh bercerita.</li>
                <li>Sepakati langkah bersama: kapan harus bertanya kepada orang tua sebelum bertindak.</li>
            </ul>
        </div>
    @endif
</div>
@endsection
