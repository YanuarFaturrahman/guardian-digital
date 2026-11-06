@extends('layouts.app')
@section('title', 'Persetujuan Privasi')

@section('content')
<div class="rise mx-auto max-w-lg">
    <div class="card">
        <h1 class="h1 mb-1">Satu langkah lagi 📝</h1>
        <p class="mb-4 text-sm text-slate-400">
            Kami memperbarui Kebijakan Privasi (versi {{ config('privacy.policy_version') }}).
            Baca dulu, lalu setujui untuk melanjutkan.
        </p>

        <form method="POST" action="{{ route('privacy.accept.store') }}" class="space-y-4">
            @csrf
            @if ($user->needsBirthDate())
                <div>
                    <label class="label" for="birth_date">Tanggal lahir</label>
                    <input class="input" id="birth_date" type="date" name="birth_date" value="{{ old('birth_date') }}" required>
                    <p class="mt-1 text-xs text-slate-400">Dipakai untuk menentukan apakah diperlukan persetujuan orang tua/wali.</p>
                </div>
            @endif

            <label class="flex items-start gap-2 text-sm text-slate-200">
                <input type="checkbox" name="accept_privacy" value="1" class="mt-1 rounded" required>
                <span>Saya sudah membaca dan menyetujui
                    <a href="{{ route('privacy.policy') }}" target="_blank" class="text-guard-400 underline">Kebijakan Privasi</a> Guardian Digital.</span>
            </label>

            <button class="btn btn-primary w-full">Setuju dan lanjutkan</button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button class="btn btn-ghost w-full">Keluar</button>
        </form>
    </div>
</div>
@endsection
