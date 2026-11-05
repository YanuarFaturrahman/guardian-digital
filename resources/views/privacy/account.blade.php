@extends('layouts.app')
@section('title', 'Privasi Akun')

@php
    $policyOk = $user->consents->where('type', 'privacy')->whereNull('revoked_at')
        ->where('policy_version', config('privacy.policy_version'))->sortByDesc('id')->first();
    $parentOk = $user->consents->where('type', 'parental')->whereNull('revoked_at')
        ->where('policy_version', config('privacy.policy_version'))->sortByDesc('id')->first();
@endphp

@section('content')
<div class="rise mx-auto max-w-3xl space-y-6">
    <div>
        <h1 class="h1">🔒 Privasi Akun</h1>
        <p class="mt-1 text-sm text-slate-400">Kelola datamu: lihat status persetujuan, unduh salinan data, atau hapus akun. <a href="{{ route('privacy.policy') }}" class="text-guard-400 hover:underline">Baca Kebijakan Privasi</a></p>
    </div>

    <section class="card">
        <h2 class="h2 mb-3">Status persetujuan</h2>
        <dl class="divide-y divide-white/10 text-sm">
            <div class="flex justify-between gap-4 py-2">
                <dt class="text-slate-400">Kebijakan Privasi (versi {{ config('privacy.policy_version') }})</dt>
                <dd>{{ $policyOk ? '✅ Disetujui ' . $policyOk->granted_at->format('d M Y H:i') : '⚠️ Belum disetujui' }}</dd>
            </div>
            @if ($user->role === 'student')
                <div class="flex justify-between gap-4 py-2">
                    <dt class="text-slate-400">Usia</dt>
                    <dd>{{ $user->birth_date ? $user->age() . ' tahun' . ($user->isMinor() ? ' (di bawah 18)' : '') : 'Belum diisi' }}</dd>
                </div>
                @if ($user->birth_date === null || $user->isMinor())
                    <div class="flex justify-between gap-4 py-2">
                        <dt class="text-slate-400">Persetujuan orang tua / wali</dt>
                        <dd class="text-right">
                            @if ($parentOk)
                                ✅ Oleh {{ $parentOk->given_by_name ?? 'orang tua / wali' }}, {{ $parentOk->granted_at->format('d M Y') }}
                            @else
                                ⚠️ Belum ada
                            @endif
                        </dd>
                    </div>
                @endif
            @endif
        </dl>
    </section>

    <section class="card">
        <h2 class="h2">📥 Unduh data saya</h2>
        <p class="mt-1 text-sm text-slate-400">
            Salinan seluruh data pribadimu (profil, persetujuan, progres belajar, dan log keamanan akun) dalam satu berkas JSON.
        </p>
        <form method="POST" action="{{ route('privacy.export') }}" class="mt-3 flex flex-wrap items-end gap-2">
            @csrf
            <div>
                <label class="label" for="exp_password">Kata sandi</label>
                <input class="input" id="exp_password" type="password" name="password" required autocomplete="current-password">
            </div>
            <button class="btn btn-primary">Unduh data</button>
        </form>
    </section>

    <section class="card border-danger/40">
        <h2 class="h2 text-rose-300">🗑️ Hapus akun saya</h2>

        @if ($blockReason)
            <p class="mt-2 text-sm text-slate-300">{{ $blockReason }}</p>
        @else
            <p class="mt-1 text-sm text-slate-400">Akun dan seluruh datamu dihapus <strong>permanen</strong> dan tidak bisa dikembalikan. Yang akan hilang:</p>

            <ul class="mt-2 list-disc space-y-1 pl-6 text-sm text-slate-300">
                <li>Akun dan profilmu (nama, email, avatar, tanggal lahir)</li>
                @foreach ($impact as $line)
                    <li>{{ $line }}</li>
                @endforeach
                <li>Log keamanan milikmu dibersihkan dari data pribadi</li>
            </ul>
            <p class="mt-2 text-xs text-slate-400">Salinan di cadangan database lama akan hilang sendiri saat cadangan itu tergantikan. Unduh datamu dulu jika masih ingin menyimpannya.</p>

            <form method="POST" action="{{ route('privacy.destroy') }}" class="mt-4 space-y-3"
                  onsubmit="return confirm('Hapus akun secara permanen? Ini tidak bisa dibatalkan.')">
                @csrf @method('DELETE')
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="label" for="del_password">Kata sandi</label>
                        <input class="input" id="del_password" type="password" name="password" required autocomplete="current-password">
                    </div>
                    <div>
                        <label class="label" for="del_email">Ketik email akunmu: <span class="font-mono text-xs text-slate-400">{{ $user->email }}</span></label>
                        <input class="input" id="del_email" type="text" name="confirm_email" required autocomplete="off">
                    </div>
                </div>
                <button class="btn btn-danger">Hapus akun saya permanen</button>
            </form>
        @endif
    </section>
</div>
@endsection
