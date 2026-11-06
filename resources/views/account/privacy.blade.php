@extends('layouts.app')
@section('title', 'Privasi & Data Saya')

@section('content')
<div class="rise mx-auto max-w-2xl space-y-6">
    <div>
        <h1 class="h1">🛡️ Privasi &amp; Data Saya</h1>
        <p class="mt-1 text-sm text-slate-400">
            Kamu berhak melihat, mengunduh, dan menghapus datamu. Baca
            <a href="{{ route('privacy.policy') }}" class="text-guard-400 hover:underline">Kebijakan Privasi</a>.
        </p>
    </div>

    <div class="card text-sm text-slate-300">
        <h2 class="h2 mb-2">Status persetujuan</h2>
        <ul class="space-y-1">
            <li>Kebijakan Privasi disetujui:
                <strong>{{ $user->privacy_accepted_at ? $user->privacy_accepted_at->translatedFormat('d F Y') . ' (versi ' . $user->privacy_policy_version . ')' : 'belum' }}</strong></li>
            @if ($user->role === 'student')
                <li>Persetujuan orang tua/wali:
                    <strong>
                        @if ($user->parental_consent_at) tercatat {{ $user->parental_consent_at->translatedFormat('d F Y') }}
                        @elseif ($user->isMinor()) belum ada
                        @else tidak diperlukan (sudah berusia {{ config('privacy.consent_age') }} tahun atau lebih)
                        @endif
                    </strong></li>
            @endif
        </ul>
    </div>

    <div class="card">
        <h2 class="h2">Unduh salinan datamu</h2>
        <p class="mt-1 text-sm text-slate-400">Berkas JSON berisi profil, riwayat misi, lencana, materi yang dibaca, dan catatan aktivitas akunmu.</p>
        <a href="{{ route('account.export') }}" class="btn btn-primary mt-3 inline-flex">⬇️ Unduh data saya</a>
    </div>

    <div class="card border-danger/40">
        <h2 class="h2 text-danger">Hapus akun dan semua datanya</h2>
        @if ($canSelfDelete)
            <p class="mt-1 text-sm text-slate-300">
                Tindakan ini <strong>permanen</strong>: riwayat misi, XP, lencana, keanggotaan kelas, dan tautan keluarga ikut terhapus.
                Catatan aktivitas lamamu dianonimkan.
            </p>
            <form method="POST" action="{{ route('account.delete') }}" class="mt-4 space-y-3"
                  onsubmit="return confirm('Hapus akun dan semua datanya secara permanen?')">
                @csrf @method('DELETE')
                <input class="input max-w-sm" type="password" name="password" placeholder="Kata sandimu" required autocomplete="current-password">
                <input class="input max-w-sm" name="confirm_text" placeholder="Ketik HAPUS untuk konfirmasi" required autocomplete="off">
                <div><button class="btn btn-ghost text-danger">Hapus akun saya</button></div>
            </form>
        @else
            <p class="mt-1 text-sm text-slate-300">Akun admin tidak bisa dihapus sendiri. Hubungi pengelola Guardian Digital.</p>
        @endif
    </div>
</div>
@endsection
