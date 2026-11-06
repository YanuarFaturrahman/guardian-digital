<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\MailStatus;
use Illuminate\Console\Command;

class SecurityCheck extends Command
{
    protected $signature = 'security:check';

    protected $description = 'Periksa konfigurasi keamanan penting sebelum/ sesudah deploy';

    public function handle(): int
    {
        $checks = [
            ['APP_DEBUG mati', ! config('app.debug')],
            ['APP_ENV=production', app()->environment('production')],
            ['APP_KEY terisi', filled(config('app.key'))],
            ['Email benar-benar terkirim (' . MailStatus::mailer() . ')', MailStatus::isDeliverable()],
            ['Cookie sesi hanya lewat HTTPS (SESSION_SECURE_COOKIE=true)', (bool) config('session.secure')],
            ['2FA wajib untuk admin', (bool) config('security.two_factor_required')],
            ['Kontak privasi terisi (PRIVACY_CONTACT_EMAIL)', filled(config('privacy.contact_email'))],
        ];

        try {
            $missing = User::whereIn('role', config('security.two_factor_roles'))->whereNull('two_factor_confirmed_at')->count();
            $checks[] = ["Semua admin sudah mengaktifkan 2FA ({$missing} belum)", $missing === 0];

            $weak = User::whereIn('email', ['admin@guardian.test', 'adminsekolah@guardian.test'])->count();
            $checks[] = ['Tidak ada akun demo bawaan (*@guardian.test)', $weak === 0];
        } catch (\Throwable) {
            $this->warn('Database belum bisa dibaca, pemeriksaan akun dilewati.');
        }

        // Berkas rahasia yang ter-commit ke Git (jika git tersedia).
        if (is_dir(base_path('.git')) && function_exists('shell_exec')) {
            $tracked = trim((string) @shell_exec('cd ' . escapeshellarg(base_path()) . ' && git ls-files 2>/dev/null | grep -E "^\\.env(\\..+)?$" | grep -v "\\.example$"'));
            $checks[] = ['Tidak ada berkas .env yang ter-commit ke Git', $tracked === ''];
        }

        $failed = 0;
        foreach ($checks as [$label, $ok]) {
            $this->line(($ok ? '  <info>OK</info>   ' : '  <error>GAGAL</error> ') . $label);
            $failed += $ok ? 0 : 1;
        }

        $this->newLine();
        $failed ? $this->warn("{$failed} pemeriksaan perlu perhatian.") : $this->info('Semua pemeriksaan lolos.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
