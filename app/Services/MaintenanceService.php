<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\ErrorLog;
use App\Models\HealthCheck;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Pemeliharaan data rutin: membuang data sementara yang sudah tidak berguna
 * (sesi kedaluwarsa, token reset lama, cache kedaluwarsa, log/error/riwayat lama)
 * supaya database tidak membengkak dan aplikasi tetap cepat.
 *
 * Tidak menyentuh data inti: pengguna, skenario, progres belajar, persetujuan privasi.
 */
class MaintenanceService
{
    /** Label tampilan tiap jenis data yang dibersihkan. */
    public const LABELS = [
        'sessions' => 'Sesi login kedaluwarsa',
        'password_resets' => 'Token atur ulang kata sandi kedaluwarsa',
        'cache' => 'Entri cache kedaluwarsa',
        'activity_logs' => 'Log aktivitas melewati batas simpan',
        'error_logs' => 'Catatan error melewati batas simpan',
        'health_checks' => 'Riwayat kesehatan melewati batas simpan',
    ];

    /** Jumlah baris yang SEKARANG bisa dibersihkan, per jenis. */
    public function pending(): array
    {
        return $this->each(fn (string $key) => $this->count($key));
    }

    /** Bersihkan semua jenis data kedaluwarsa. Mengembalikan jumlah baris terhapus per jenis. */
    public function purge(): array
    {
        $removed = $this->each(fn (string $key) => $this->delete($key));

        $total = array_sum($removed);
        ActivityLog::record(
            'system.maintenance_run',
            "Pembersihan data selesai: {$total} baris dibuang",
            null,
            ['rincian' => array_filter($removed)]
        );

        return $removed;
    }

    private function each(callable $fn): array
    {
        $out = [];

        foreach (array_keys(self::LABELS) as $key) {
            try {
                $out[$key] = (int) $fn($key);
            } catch (Throwable $e) {
                report($e);
                $out[$key] = 0;
            }
        }

        return $out;
    }

    private function count(string $key): int
    {
        return match ($key) {
            'sessions' => $this->sessionsQuery()?->count() ?? 0,
            'password_resets' => $this->resetsQuery()?->count() ?? 0,
            'cache' => $this->cacheQuery()?->count() ?? 0,
            'activity_logs' => ActivityLog::where('created_at', '<', now()->subDays($this->days('log_retention_days', 7)))->count(),
            'error_logs' => ErrorLog::where('last_seen_at', '<', now()->subDays($this->days('error_retention_days', 1)))->count(),
            'health_checks' => HealthCheck::where('created_at', '<', now()->subDays($this->days('health_retention_days', 1)))->count(),
        };
    }

    private function delete(string $key): int
    {
        return match ($key) {
            'sessions' => $this->sessionsQuery()?->delete() ?? 0,
            'password_resets' => $this->resetsQuery()?->delete() ?? 0,
            'cache' => $this->cacheQuery()?->delete() ?? 0,
            'activity_logs' => ActivityLog::prune(),
            'error_logs' => ErrorLog::prune($this->days('error_retention_days', 1)),
            'health_checks' => HealthCheck::prune($this->days('health_retention_days', 1)),
        };
    }

    private function days(string $setting, int $min): int
    {
        return max($min, (int) Setting::read($setting));
    }

    private function sessionsQuery()
    {
        if (config('session.driver') !== 'database' || ! Schema::hasTable('sessions')) {
            return null;
        }

        // Sesi yang sudah lewat masa berlakunya tidak bisa dipakai lagi.
        return DB::table('sessions')->where('last_activity', '<', now()->subMinutes((int) config('session.lifetime'))->timestamp);
    }

    private function resetsQuery()
    {
        $table = config('auth.passwords.users.table', 'password_reset_tokens');

        if (! Schema::hasTable($table)) {
            return null;
        }

        return DB::table($table)->where('created_at', '<', now()->subMinutes((int) config('auth.passwords.users.expire', 60)));
    }

    private function cacheQuery()
    {
        if (config('cache.default') !== 'database') {
            return null;
        }

        $table = config('cache.stores.database.table', 'cache');

        if (! Schema::hasTable($table)) {
            return null;
        }

        return DB::table($table)->where('expiration', '<', now()->timestamp);
    }

    /**
     * Ukuran tabel terbesar. MySQL: baris perkiraan + ukuran data+indeks.
     * SQLite: jumlah baris saja (ukuran per tabel tidak tersedia).
     *
     * @return array<int,array{name:string,rows:int,bytes:?int}>
     */
    public function tableSizes(int $limit = 12): array
    {
        try {
            $driver = DB::connection()->getDriverName();

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                return collect(DB::select(
                    'SELECT TABLE_NAME AS name, TABLE_ROWS AS row_count, (DATA_LENGTH + INDEX_LENGTH) AS bytes
                     FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = \'BASE TABLE\'
                     ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC LIMIT ' . (int) $limit
                ))->map(fn ($r) => [
                    'name' => $r->name,
                    'rows' => (int) $r->row_count,
                    'bytes' => (int) $r->bytes,
                ])->all();
            }

            if ($driver === 'sqlite') {
                return collect(DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"))
                    ->map(fn ($t) => [
                        'name' => $t->name,
                        'rows' => (int) DB::table($t->name)->count(),
                        'bytes' => null,
                    ])
                    ->sortByDesc('rows')
                    ->take($limit)
                    ->values()
                    ->all();
            }
        } catch (Throwable $e) {
            report($e);
        }

        return [];
    }

    /** Total ukuran database dalam byte (MySQL: dari information_schema; SQLite: ukuran file). */
    public function databaseSize(): ?int
    {
        try {
            $driver = DB::connection()->getDriverName();

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                return (int) DB::selectOne(
                    'SELECT SUM(DATA_LENGTH + INDEX_LENGTH) AS total FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()'
                )->total;
            }

            if ($driver === 'sqlite') {
                $file = DB::connection()->getDatabaseName();

                return is_file($file) ? filesize($file) : null;
            }
        } catch (Throwable) {
        }

        return null;
    }

    /** Cache tampilan Blade yang dikompilasi. */
    public function clearViews(): void
    {
        Artisan::call('view:clear');
    }

    /** Muat ulang cache pengaturan sistem dan pengumuman (aman, tidak menghapus apa pun yang penting). */
    public function refreshSettingsCache(): void
    {
        Cache::forget('system.settings');
        \App\Models\Announcement::forgetCache();
    }

    /**
     * Kosongkan SELURUH cache aplikasi. Efek samping: hitungan penguncian login per akun
     * ikut terhapus dan cache pengaturan dibangun ulang.
     */
    public function flushCache(): void
    {
        Cache::flush();
    }
}
