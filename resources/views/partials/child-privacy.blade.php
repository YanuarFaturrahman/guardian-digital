@php($consented = $student->hasParentalConsent())
<div class="card mt-6 {{ $consented ? '' : 'border-warn/50' }}">
    <h2 class="h2">🔒 Privasi &amp; persetujuan {{ $student->name }}</h2>

    @if ($student->isMinor() || $student->birth_date === null)
        <div class="mt-3 text-sm">
            @if ($consented)
                <p class="text-emerald-200">✅ Persetujuan orang tua / wali sudah diberikan. Akun anak bisa dipakai.</p>
                <form method="POST" action="{{ route('family.consent.revoke', $student) }}" class="mt-2"
                      onsubmit="return confirm('Cabut persetujuan? Anak tidak bisa memakai akunnya sampai disetujui lagi.')">
                    @csrf @method('DELETE')
                    <button class="text-xs text-rose-300 hover:underline">Cabut persetujuan</button>
                </form>
            @else
                <p class="text-amber-200">⚠️ Akun ini menunggu persetujuanmu. Anak belum bisa memakai fitur belajar.</p>
                <form method="POST" action="{{ route('family.consent.grant', $student) }}" class="mt-3 space-y-3">
                    @csrf
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" name="accept" value="1" class="mt-1 h-4 w-4 accent-cyan-400" required>
                        <span>
                            Saya orang tua / wali sah dari {{ $student->name }}, telah membaca
                            <a href="{{ route('privacy.policy') }}" target="_blank" rel="noopener" class="text-guard-400 underline">Kebijakan Privasi</a>,
                            dan menyetujui pemrosesan data pribadi anak saya.
                        </span>
                    </label>
                    <button class="btn btn-primary">Beri persetujuan</button>
                </form>
            @endif
        </div>
    @else
        <p class="mt-2 text-sm text-slate-400">Anak sudah berusia 18 tahun atau lebih, jadi persetujuan orang tua tidak diperlukan.</p>
    @endif

    <details class="mt-5 text-sm">
        <summary class="cursor-pointer text-slate-300 hover:text-white">Unduh atau hapus data anak</summary>

        <div class="mt-3 space-y-5">
            <form method="POST" action="{{ route('family.child.export', $student) }}" class="flex flex-wrap items-end gap-2">
                @csrf
                <div>
                    <label class="label" for="cexp_pw">Kata sandimu</label>
                    <input class="input" id="cexp_pw" type="password" name="password" required autocomplete="current-password">
                </div>
                <button class="btn btn-ghost">📥 Unduh data anak (JSON)</button>
            </form>

            <form method="POST" action="{{ route('family.child.erase', $student) }}" class="space-y-3 rounded-xl border border-danger/40 p-4"
                  onsubmit="return confirm('Hapus akun anak secara permanen? Ini tidak bisa dibatalkan.')">
                @csrf @method('DELETE')
                <p class="text-xs text-slate-400">Menghapus akun anak dan seluruh data belajarnya secara <strong>permanen</strong>.</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="label" for="cdel_pw">Kata sandimu</label>
                        <input class="input" id="cdel_pw" type="password" name="password" required autocomplete="current-password">
                    </div>
                    <div>
                        <label class="label" for="cdel_name">Ketik nama anak: <span class="font-mono text-xs text-slate-400">{{ $student->name }}</span></label>
                        <input class="input" id="cdel_name" type="text" name="confirm_name" required autocomplete="off">
                    </div>
                </div>
                <button class="btn btn-danger">Hapus akun anak permanen</button>
            </form>
        </div>
    </details>
</div>
