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

    @if ($student->isMinor() && (int) $student->parental_consent_by === (int) auth()->id())
        <div class="card mt-6">
            <h2 class="h2">Data &amp; persetujuan anak</h2>
            <p class="mt-1 text-sm text-slate-400">Sebagai wali yang memberi persetujuan, kamu bisa mengunduh, mencabut persetujuan, atau menghapus data anak.</p>
            <div class="mt-3 flex flex-wrap items-start gap-3">
                <a href="{{ route('family.child.export', $student) }}" class="btn btn-ghost">⬇️ Unduh data anak</a>
                <form method="POST" action="{{ route('family.child.withdraw', $student) }}"
                      onsubmit="return confirm('Cabut persetujuan? Akun anak terkunci sampai kamu menyetujui lagi.')">
                    @csrf
                    <button class="btn btn-ghost text-warn">Cabut persetujuan</button>
                </form>
            </div>
            <form method="POST" action="{{ route('family.child.delete', $student) }}" class="mt-5 space-y-3 border-t border-white/10 pt-4"
                  onsubmit="return confirm('Hapus akun anak dan semua datanya secara permanen?')">
                @csrf @method('DELETE')
                <div class="text-sm font-semibold text-danger">Hapus akun anak secara permanen</div>
                <input class="input max-w-sm" type="password" name="password" placeholder="Kata sandimu" required autocomplete="current-password">
                <input class="input max-w-sm" name="confirm_text" placeholder="Ketik HAPUS untuk konfirmasi" required autocomplete="off">
                <div><button class="btn btn-ghost text-danger">Hapus akun anak</button></div>
            </form>
        </div>
    @endif

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
