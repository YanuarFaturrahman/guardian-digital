@extends('layouts.app')
@section('title', 'Pengaturan Sistem')

@section('content')
<div class="rise mx-auto max-w-3xl">
    <h1 class="h1">⚙️ Pengaturan Sistem</h1>
    <p class="mb-4 mt-1 text-sm text-slate-400">Konfigurasi umum aplikasi. Setiap perubahan dicatat di Log Aktivitas.</p>

    @include('admin.system._tabs')

    <form method="POST" action="{{ route('admin.system.settings.update') }}" class="space-y-6">
        @csrf @method('PUT')

        <section class="card space-y-4">
            <h2 class="h2">🚧 Pemeliharaan &amp; akses</h2>

            <label class="flex items-start gap-3 text-sm">
                <input type="checkbox" name="maintenance_mode" value="1" class="mt-1 h-4 w-4 accent-cyan-400" @checked(old('maintenance_mode', $v['maintenance_mode']))>
                <span>
                    <span class="font-medium">Aktifkan mode pemeliharaan</span>
                    <span class="block text-xs text-slate-400">Semua pengguna selain superadmin melihat halaman pemeliharaan dan tidak bisa masuk. Kamu sendiri tetap bisa memakai aplikasi.</span>
                </span>
            </label>

            <div>
                <label class="label" for="maintenance_message">Pesan pemeliharaan (opsional)</label>
                <textarea id="maintenance_message" name="maintenance_message" rows="2" maxlength="300" class="input"
                          placeholder="Contoh: Sedang pembaruan materi. Kembali lagi pukul 16.00.">{{ old('maintenance_message', $v['maintenance_message']) }}</textarea>
            </div>

            <label class="flex items-start gap-3 text-sm">
                <input type="checkbox" name="registration_open" value="1" class="mt-1 h-4 w-4 accent-cyan-400" @checked(old('registration_open', $v['registration_open']))>
                <span>
                    <span class="font-medium">Pendaftaran akun baru dibuka</span>
                    <span class="block text-xs text-slate-400">Matikan jika tidak ingin ada pendaftar baru. Akun yang sudah ada tidak terpengaruh.</span>
                </span>
            </label>
        </section>

        <section class="card space-y-4">
            <h2 class="h2">📦 Unggah file</h2>
            <div>
                <label class="label" for="max_upload_kb">Batas ukuran unggah (KB)</label>
                <input id="max_upload_kb" type="number" name="max_upload_kb" min="100" max="51200" class="input max-w-[12rem]" value="{{ old('max_upload_kb', $v['max_upload_kb']) }}">
                <p class="mt-1 text-xs text-slate-400">Batas keras server saat ini: {{ $phpLimit }} (php.ini). Nilai di sini tidak bisa melewatinya. Dibaca lewat <code>Setting::read('max_upload_kb')</code>.</p>
            </div>
        </section>

        <section class="card space-y-4">
            <h2 class="h2">💾 Backup &amp; log</h2>

            <label class="flex items-start gap-3 text-sm">
                <input type="checkbox" name="backup_auto" value="1" class="mt-1 h-4 w-4 accent-cyan-400" @checked(old('backup_auto', $v['backup_auto']))>
                <span>
                    <span class="font-medium">Backup otomatis setiap hari (02:00)</span>
                    <span class="block text-xs text-slate-400">Butuh scheduler Laravel yang berjalan di server (<code>php artisan schedule:run</code> tiap menit).</span>
                </span>
            </label>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="backup_keep">Simpan backup otomatis terbaru</label>
                    <input id="backup_keep" type="number" name="backup_keep" min="1" max="60" class="input" value="{{ old('backup_keep', $v['backup_keep']) }}">
                    <p class="mt-1 text-xs text-slate-400">Yang lebih lama dihapus. Backup manual tidak pernah dihapus otomatis.</p>
                </div>
                <div>
                    <label class="label" for="log_retention_days">Simpan log aktivitas (hari)</label>
                    <input id="log_retention_days" type="number" name="log_retention_days" min="7" max="730" class="input" value="{{ old('log_retention_days', $v['log_retention_days']) }}">
                    <p class="mt-1 text-xs text-slate-400">Log yang lebih tua dibersihkan otomatis tiap hari.</p>
                </div>
            </div>
        </section>

        <section class="card space-y-4">
            <h2 class="h2">🩺 Pemantauan &amp; peringatan</h2>

            <label class="flex items-start gap-3 text-sm">
                <input type="checkbox" name="alerts_enabled" value="1" class="mt-1 h-4 w-4 accent-cyan-400" @checked(old('alerts_enabled', $v['alerts_enabled']))>
                <span>
                    <span class="font-medium">Kirim peringatan email ke superadmin</span>
                    <span class="block text-xs text-slate-400">Email dikirim saat sistem terganggu atau tidak bisa diakses, dan saat backup gagal atau rusak. Satu jenis peringatan maksimal sekali per {{ \App\Support\SystemAlert::COOLDOWN_HOURS }} jam.
                        @unless (\App\Support\MailStatus::isDeliverable())
                            <strong class="text-amber-300">Saat ini email belum benar-benar terkirim ({{ \App\Support\MailStatus::mailer() }}), jadi peringatan tidak akan sampai.</strong>
                        @endunless
                    </span>
                </span>
            </label>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="error_retention_days">Simpan catatan error (hari)</label>
                    <input id="error_retention_days" type="number" name="error_retention_days" min="1" max="365" class="input" value="{{ old('error_retention_days', $v['error_retention_days']) }}">
                    <p class="mt-1 text-xs text-slate-400">Dihitung dari kemunculan terakhir.</p>
                </div>
                <div>
                    <label class="label" for="health_retention_days">Simpan riwayat kesehatan (hari)</label>
                    <input id="health_retention_days" type="number" name="health_retention_days" min="1" max="365" class="input" value="{{ old('health_retention_days', $v['health_retention_days']) }}">
                    <p class="mt-1 text-xs text-slate-400">Dasar perhitungan uptime. Disarankan 30 hari atau lebih.</p>
                </div>
            </div>
        </section>

        <section class="card space-y-4">
            <h2 class="h2">🔌 API eksternal</h2>
            <p class="text-xs text-slate-400">Tempat menyimpan alamat dan kunci layanan pihak ketiga. Nilainya bisa dibaca kode lewat <code>Setting::read('external_api_url')</code> dan <code>Setting::read('external_api_key')</code>. Aplikasi belum memakainya sampai kamu menyambungkannya.</p>

            <label class="flex items-start gap-3 text-sm">
                <input type="checkbox" name="external_api_enabled" value="1" class="mt-1 h-4 w-4 accent-cyan-400" @checked(old('external_api_enabled', $v['external_api_enabled']))>
                <span class="font-medium">Aktifkan integrasi API eksternal</span>
            </label>

            <div>
                <label class="label" for="external_api_url">Alamat API (https)</label>
                <input id="external_api_url" type="url" name="external_api_url" class="input" placeholder="https://api.contoh.com/v1" value="{{ old('external_api_url', $v['external_api_url']) }}">
            </div>

            <div>
                <label class="label" for="external_api_key">Kunci API</label>
                <input id="external_api_key" type="password" name="external_api_key" autocomplete="new-password" class="input"
                       placeholder="{{ $hasApiKey ? 'Sudah tersimpan (kosongkan jika tidak ingin mengganti)' : 'Tempel kunci API di sini' }}">
                <p class="mt-1 text-xs text-slate-400">Disimpan terenkripsi dan tidak pernah ditampilkan kembali.</p>
                @if ($hasApiKey)
                    <label class="mt-2 flex items-center gap-2 text-xs text-rose-300">
                        <input type="checkbox" name="clear_external_api_key" value="1" class="h-4 w-4 accent-rose-400"> Hapus kunci yang tersimpan
                    </label>
                @endif
            </div>
        </section>

        <div class="flex justify-end">
            <button class="btn btn-primary">Simpan pengaturan</button>
        </div>
    </form>
</div>
@endsection
