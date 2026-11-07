<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\ErrorLog;
use App\Models\HealthCheck;
use App\Models\Setting;
use App\Services\BackupService;
use Illuminate\Http\Request;
use App\Models\User;
use App\Support\MailStatus;
use Illuminate\Support\Facades\DB;

/** Halaman ringkasan menu Sistem: status, pemeriksaan keamanan, dan info server. */
class SystemController extends Controller
{
    public function index(Request $request, BackupService $backups)
    {
        $since = now()->subDay();
        $lastBackup = $backups->list()->first();

        $activeSessions = null;
        if (config('session.driver') === 'database') {
            $activeSessions = DB::table('sessions')
                ->whereNotNull('user_id')
                ->where('last_activity', '>=', now()->subMinutes((int) config('session.lifetime'))->timestamp)
                ->count();
        }

        $free = @disk_free_space(storage_path());

        return view('admin.system.index', [
            'logs24h' => ActivityLog::where('created_at', '>=', $since)->count(),
            'failedLogins24h' => ActivityLog::where('action', 'auth.login_failed')->where('created_at', '>=', $since)->count(),
            'activeSessions' => $activeSessions,
            'lastBackup' => $lastBackup,
            'maintenance' => Setting::bool('maintenance_mode'),
            'checks' => $this->securityChecks($request, $lastBackup),
            'info' => [
                'Versi PHP' => PHP_VERSION,
                'Versi Laravel' => app()->version(),
                'Lingkungan (APP_ENV)' => app()->environment(),
                'Database' => DB::connection()->getDriverName(),
                'Zona waktu' => config('app.timezone'),
                'Ruang disk tersisa' => $free === false ? '-' : BackupService::formatBytes($free),
                'Batas unggah PHP' => ini_get('upload_max_filesize') . ' (post: ' . ini_get('post_max_size') . ')',
            ],
            'recent' => ActivityLog::latest('id')->take(6)->get(),
            'health' => HealthCheck::latest('id')->first(),
            'uptime7d' => HealthCheck::uptime(7),
            'openErrors' => ErrorLog::open()->count(),
            'liveAnnouncements' => Announcement::live()->count(),
        ]);
    }

    /** Pemeriksaan cepat konfigurasi keamanan: tiap butir punya status ok / perlu perhatian. */
    private function securityChecks(Request $request, ?array $lastBackup): array
    {
        return [
            [
                'ok' => ! config('app.debug'),
                'label' => 'Mode debug dimatikan',
                'hint' => 'APP_DEBUG=true menampilkan detail error dan konfigurasi ke pengunjung. Ubah ke false di production.',
            ],
            [
                'ok' => app()->environment('production'),
                'label' => 'Lingkungan production',
                'hint' => 'APP_ENV masih "' . app()->environment() . '". Di server publik, set APP_ENV=production.',
            ],
            [
                'ok' => $request->isSecure(),
                'label' => 'Koneksi memakai HTTPS',
                'hint' => 'Halaman ini dibuka lewat HTTP. Kata sandi dan sesi bisa disadap di jaringan.',
            ],
            [
                'ok' => config('session.driver') === 'database',
                'label' => 'Sesi disimpan di database',
                'hint' => 'Fitur "Sesi Aktif" dan "Paksa keluar" hanya bekerja dengan SESSION_DRIVER=database.',
            ],
            [
                'ok' => MailStatus::isDeliverable(),
                'label' => 'Email benar-benar terkirim',
                'hint' => MailStatus::describe() . ' Atur MAIL_MAILER=smtp dan kredensial SMTP (Brevo, Mailgun, Gmail SMTP, dll.) di environment variable. Tanpa ini, tautan atur ulang kata sandi dan verifikasi email tidak sampai ke pengguna.',
            ],
            [
                'ok' => MailStatus::emailVerificationActive(),
                'label' => 'Verifikasi email pendaftar aktif',
                'hint' => 'Butuh EMAIL_VERIFICATION_REQUIRED=true dan email yang bisa terkirim.',
            ],
            [
                'ok' => ! config('security.two_factor_required')
                    ? false
                    : User::whereIn('role', config('security.two_factor_roles'))->whereNull('two_factor_confirmed_at')->doesntExist(),
                'label' => 'Semua admin memakai verifikasi dua langkah',
                'hint' => config('security.two_factor_required')
                    ? User::whereIn('role', config('security.two_factor_roles'))->whereNull('two_factor_confirmed_at')->count() . ' akun admin belum mengaktifkannya (mereka dipaksa mengaktifkan saat masuk berikutnya).'
                    : 'TWO_FACTOR_REQUIRED=false. Aktifkan kembali agar admin wajib memakai 2FA.',
            ],
            [
                'ok' => filled(config('privacy.contact_email')),
                'label' => 'Kontak privasi sudah diisi',
                'hint' => 'Isi PRIVACY_CONTACT_EMAIL dan PRIVACY_CONTROLLER_NAME agar Kebijakan Privasi memuat kontak yang benar.',
            ],
            [
                'ok' => Setting::bool('backup_auto'),
                'label' => 'Backup otomatis aktif',
                'hint' => 'Aktifkan di Pengaturan Sistem, dan pastikan scheduler (schedule:run) dijalankan server.',
            ],
            [
                'ok' => $lastBackup && $lastBackup['time']->greaterThan(now()->subDays(7)),
                'label' => 'Ada backup dalam 7 hari terakhir',
                'hint' => $lastBackup
                    ? 'Backup terakhir ' . $lastBackup['time']->diffForHumans() . '.'
                    : 'Belum ada backup sama sekali.',
            ],
        ];
    }
}
