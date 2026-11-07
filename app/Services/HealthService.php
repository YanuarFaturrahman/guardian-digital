<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\ErrorLog;
use App\Models\HealthCheck;
use App\Models\Setting;
use App\Support\MailStatus;
use App\Support\SystemAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pemeriksaan kesehatan aplikasi.
 *
 * Tiap butir punya status: ok | warn | fail, dan penanda "critical".
 * Hanya butir kritis (database, cache, penyimpanan, disk, ekstensi PHP) yang menentukan
 * status keseluruhan: ok | degraded | down. Butir lain (backup, email, penjadwal, error)
 * bersifat saran dan hanya tampil di halaman Kesehatan Sistem.
 */
class HealthService
{
    /** Penjadwal dianggap mati jika tidak ada denyut lebih lama dari ini. */
    public const SCHEDULER_STALE_MINUTES = 30;

    /** @return array{status:string, checks:array<int,array>, db_ms:?int, disk_free_pct:?int} */
    public function run(bool $withAdvisory = true): array
    {
        $checks = [];
        $dbMs = null;

        $checks[] = $this->checkDatabase($dbMs);
        $checks[] = $this->checkCache();
        $checks[] = $this->checkStorage();
        $disk = $this->checkDisk($pct);
        $checks[] = $disk;
        $checks[] = $this->checkExtensions();

        if ($withAdvisory) {
            array_push($checks, $this->checkScheduler(), $this->checkBackup(), $this->checkMail(), $this->checkErrors());
        }

        $critical = array_filter($checks, fn ($c) => $c['critical']);

        $status = 'ok';
        foreach ($critical as $c) {
            if ($c['key'] === 'database' && $c['status'] === 'fail') {
                $status = 'down';
                break;
            }
            if ($c['status'] === 'fail') {
                $status = 'degraded';
            } elseif ($c['status'] === 'warn' && $status === 'ok') {
                $status = 'degraded';
            }
        }

        return ['status' => $status, 'checks' => $checks, 'db_ms' => $dbMs, 'disk_free_pct' => $pct];
    }

    /** Jalankan pemeriksaan, simpan riwayatnya, dan kirim peringatan jika baru memburuk. */
    public function record(string $source = 'scheduler'): array
    {
        $result = $this->run();

        try {
            $previous = HealthCheck::where('source', 'scheduler')->latest('id')->value('status');

            $issues = collect($result['checks'])
                ->filter(fn ($c) => $c['critical'] && $c['status'] !== 'ok')
                ->map(fn ($c) => $c['label'] . ': ' . $c['detail'])
                ->values()
                ->all();

            HealthCheck::create([
                'status' => $result['status'],
                'source' => $source === 'manual' ? 'manual' : 'scheduler',
                'db_ms' => $result['db_ms'],
                'disk_free_pct' => $result['disk_free_pct'],
                'issues' => $issues ?: null,
            ]);

            if ($result['status'] !== 'ok' && ($previous === null || $previous === 'ok')) {
                $label = $result['status'] === 'down' ? 'TIDAK BISA DIAKSES' : 'TERGANGGU';
                SystemAlert::send(
                    'health-' . $result['status'],
                    "Sistem {$label}",
                    "Status sistem: {$result['status']}.\n\n- " . implode("\n- ", $issues)
                );
            }
        } catch (Throwable $e) {
            report($e);
        }

        return $result;
    }

    /** Denyut penjadwal: kapan pemeriksaan terjadwal terakhir berjalan. */
    public function lastSchedulerBeat(): ?\Carbon\Carbon
    {
        try {
            return HealthCheck::where('source', 'scheduler')->latest('id')->value('created_at');
        } catch (Throwable) {
            return null;
        }
    }

    private function item(string $key, string $label, string $status, string $detail, bool $critical): array
    {
        return compact('key', 'label', 'status', 'detail', 'critical');
    }

    private function checkDatabase(?int &$ms): array
    {
        try {
            $start = microtime(true);
            DB::select('select 1');
            $ms = (int) round((microtime(true) - $start) * 1000);

            return $ms > 1000
                ? $this->item('database', 'Database', 'warn', "Terhubung tetapi lambat ({$ms} ms).", true)
                : $this->item('database', 'Database', 'ok', "Terhubung ({$ms} ms).", true);
        } catch (Throwable $e) {
            return $this->item('database', 'Database', 'fail', 'Tidak bisa terhubung: ' . Str::limit($e->getMessage(), 120), true);
        }
    }

    private function checkCache(): array
    {
        try {
            $key = 'health:probe:' . Str::random(8);
            Cache::put($key, 'ok', 30);
            $ok = Cache::get($key) === 'ok';
            Cache::forget($key);

            return $ok
                ? $this->item('cache', 'Cache', 'ok', 'Baca dan tulis berhasil (driver ' . config('cache.default') . ').', true)
                : $this->item('cache', 'Cache', 'fail', 'Nilai yang ditulis tidak terbaca kembali.', true);
        } catch (Throwable $e) {
            return $this->item('cache', 'Cache', 'fail', 'Error: ' . Str::limit($e->getMessage(), 120), true);
        }
    }

    private function checkStorage(): array
    {
        $dirs = [storage_path('app'), storage_path('framework'), storage_path('logs')];
        $bad = [];

        foreach ($dirs as $dir) {
            if (! is_dir($dir) || ! is_writable($dir)) {
                $bad[] = basename($dir);
            }
        }

        return $bad
            ? $this->item('storage', 'Penyimpanan (storage)', 'fail', 'Folder tidak bisa ditulis: ' . implode(', ', $bad) . '. Periksa izin folder.', true)
            : $this->item('storage', 'Penyimpanan (storage)', 'ok', 'Folder storage bisa ditulis.', true);
    }

    /** Di bawah ini ruang bebas dianggap menipis / hampir habis (backup dan log butuh ruang). */
    public const DISK_WARN_BYTES = 1024 ** 3;       // 1 GB
    public const DISK_FAIL_BYTES = 200 * 1024 ** 2; // 200 MB

    /**
     * Penilaian berdasarkan ruang bebas ABSOLUT, bukan persentase: disk besar yang tinggal 4%
     * tetapi masih 10 GB bebas aman untuk aplikasi ini, sebaliknya disk 1 GB yang 30% kosong
     * (300 MB) sudah mepet.
     */
    public static function classifyDisk(int|float $freeBytes): string
    {
        return match (true) {
            $freeBytes < self::DISK_FAIL_BYTES => 'fail',
            $freeBytes < self::DISK_WARN_BYTES => 'warn',
            default => 'ok',
        };
    }

    private function checkDisk(?int &$pct): array
    {
        $free = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());

        if ($free === false || ! $total) {
            $pct = null;

            return $this->item('disk', 'Ruang disk', 'ok', 'Tidak bisa dibaca di server ini (dilewati).', true);
        }

        $pct = (int) round(100 * $free / $total);
        $detail = BackupService::formatBytes($free) . " tersisa ({$pct}%).";

        return match (self::classifyDisk($free)) {
            'fail' => $this->item('disk', 'Ruang disk', 'fail', $detail . ' Hampir penuh: backup dan log bisa gagal.', true),
            'warn' => $this->item('disk', 'Ruang disk', 'warn', $detail . ' Mulai menipis.', true),
            default => $this->item('disk', 'Ruang disk', 'ok', $detail, true),
        };
    }

    private function checkExtensions(): array
    {
        $required = ['mbstring', 'openssl', 'zlib', 'json', 'ctype', 'tokenizer', 'xml'];
        $required[] = in_array(config('database.default'), ['sqlite'], true) ? 'pdo_sqlite' : 'pdo_mysql';

        $missing = array_values(array_filter($required, fn ($e) => ! extension_loaded($e)));

        return $missing
            ? $this->item('extensions', 'Ekstensi PHP', 'fail', 'Belum aktif: ' . implode(', ', $missing), true)
            : $this->item('extensions', 'Ekstensi PHP', 'ok', 'Semua ekstensi yang dibutuhkan aktif.', true);
    }

    private function checkScheduler(): array
    {
        $beat = $this->lastSchedulerBeat();

        if ($beat === null) {
            return $this->item('scheduler', 'Penjadwal (cron)', 'warn', 'Belum pernah berjalan. Pasang cron "schedule:run" tiap menit, atau isi LAZY_SCHEDULER=true di hosting tanpa cron.', false);
        }

        return $beat->lt(now()->subMinutes(self::SCHEDULER_STALE_MINUTES))
            ? $this->item('scheduler', 'Penjadwal (cron)', 'warn', 'Terakhir berjalan ' . $beat->diffForHumans() . '. Backup dan pembersihan otomatis mungkin tidak jalan.', false)
            : $this->item('scheduler', 'Penjadwal (cron)', 'ok', 'Terakhir berjalan ' . $beat->diffForHumans() . '.', false);
    }

    private function checkBackup(): array
    {
        try {
            $last = app(BackupService::class)->list()->first();
        } catch (Throwable) {
            $last = null;
        }

        if (! $last) {
            return $this->item('backup', 'Backup database', 'warn', 'Belum ada backup sama sekali.', false);
        }

        $limit = Setting::bool('backup_auto') ? 36 : 24 * 7;

        return $last['time']->lt(now()->subHours($limit))
            ? $this->item('backup', 'Backup database', 'warn', 'Backup terakhir ' . $last['time']->diffForHumans() . '.', false)
            : $this->item('backup', 'Backup database', 'ok', 'Backup terakhir ' . $last['time']->diffForHumans() . '.', false);
    }

    private function checkMail(): array
    {
        return MailStatus::isDeliverable()
            ? $this->item('mail', 'Pengiriman email', 'ok', MailStatus::describe(), false)
            : $this->item('mail', 'Pengiriman email', 'warn', MailStatus::describe(), false);
    }

    private function checkErrors(): array
    {
        try {
            $n = ErrorLog::open()->where('last_seen_at', '>=', now()->subDay())->count();
        } catch (Throwable) {
            $n = 0;
        }

        return $n > 0
            ? $this->item('errors', 'Error aplikasi (24 jam)', 'warn', "{$n} jenis error belum diselesaikan. Lihat tab Error Aplikasi.", false)
            : $this->item('errors', 'Error aplikasi (24 jam)', 'ok', 'Tidak ada error baru.', false);
    }
}
