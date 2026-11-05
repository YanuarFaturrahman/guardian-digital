<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Cadangan database tanpa bergantung pada mysqldump:
 * - MySQL / MariaDB: file .sql terkompres (struktur + isi tabel)
 * - SQLite: salinan konsisten file database, terkompres
 * Hasil disimpan di storage/app/private/backups.
 */
class BackupService
{
    public const DIR = 'backups';

    /** Struktur tabel tetap dicadangkan, tetapi isinya dilewati (data sementara). */
    private const SKIP_DATA = ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'];

    private const NAME_PATTERN = '/^backup-\d{8}-\d{6}-(manual|auto)\.(sql|sqlite)\.gz$/';

    public static function isValidName(string $name): bool
    {
        return (bool) preg_match(self::NAME_PATTERN, $name);
    }

    public static function formatBytes(int|float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $i === 0 ? 0 : 1) . ' ' . $units[$i];
    }

    private function disk()
    {
        return Storage::disk('local');
    }

    public function exists(string $name): bool
    {
        return self::isValidName($name) && $this->disk()->exists(self::DIR . '/' . $name);
    }

    public function path(string $name): string
    {
        return $this->disk()->path(self::DIR . '/' . $name);
    }

    public function delete(string $name): void
    {
        if (self::isValidName($name)) {
            $this->disk()->delete(self::DIR . '/' . $name);
        }
    }

    /** Daftar cadangan, terbaru di atas. */
    public function list(): Collection
    {
        $disk = $this->disk();

        return collect($disk->files(self::DIR))
            ->map(fn ($path) => basename($path))
            ->filter(fn ($name) => self::isValidName($name))
            ->map(function ($name) use ($disk) {
                $path = self::DIR . '/' . $name;

                return [
                    'name' => $name,
                    'size' => $disk->size($path),
                    'time' => Carbon::createFromTimestamp($disk->lastModified($path)),
                    'type' => str_contains($name, '-auto.') ? 'auto' : 'manual',
                ];
            })
            ->sortByDesc('time')
            ->values();
    }

    /** Buat cadangan baru dan kembalikan nama filenya. */
    public function create(string $type = 'manual'): string
    {
        $type = $type === 'auto' ? 'auto' : 'manual';
        $driver = DB::connection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb', 'sqlite'], true)) {
            throw new RuntimeException("Driver database \"{$driver}\" belum didukung untuk backup.");
        }

        $disk = $this->disk();
        $disk->makeDirectory(self::DIR);

        $ext = $driver === 'sqlite' ? 'sqlite' : 'sql';
        $name = sprintf('backup-%s-%s.%s.gz', now()->format('Ymd-His'), $type, $ext);
        $target = $disk->path(self::DIR . '/' . $name);

        $gz = @gzopen($target, 'wb9');

        if ($gz === false) {
            throw new RuntimeException('Tidak bisa menulis file backup. Periksa izin folder storage/app/private.');
        }

        try {
            $driver === 'sqlite' ? $this->writeSqlite($gz) : $this->writeSql($gz);
        } catch (Throwable $e) {
            gzclose($gz);
            @unlink($target);

            throw $e;
        }

        gzclose($gz);

        return $name;
    }

    /** Hapus cadangan otomatis yang melebihi batas simpan. Cadangan manual tidak disentuh. */
    public function pruneAuto(int $keep): int
    {
        $old = $this->list()->where('type', 'auto')->slice(max(1, $keep));

        foreach ($old as $backup) {
            $this->delete($backup['name']);
        }

        return $old->count();
    }

    private function writeSqlite($gz): void
    {
        $tmp = $this->disk()->path(self::DIR . '/.tmp-' . Str::random(8) . '.sqlite');

        try {
            // VACUUM INTO menghasilkan salinan yang konsisten walau database sedang dipakai
            DB::connection()->statement('VACUUM INTO ?', [$tmp]);

            $in = fopen($tmp, 'rb');
            while (! feof($in)) {
                gzwrite($gz, fread($in, 1024 * 1024));
            }
            fclose($in);
        } finally {
            @unlink($tmp);
        }
    }

    private function writeSql($gz): void
    {
        $db = DB::connection();
        $pdo = $db->getPdo();
        $ident = fn (string $name) => '`' . str_replace('`', '``', $name) . '`';

        gzwrite($gz, "-- Guardian Digital: cadangan database\n");
        gzwrite($gz, '-- Dibuat: ' . now()->toDateTimeString() . ' (' . config('app.timezone') . ")\n");
        gzwrite($gz, '-- Database: ' . $db->getDatabaseName() . "\n\n");
        gzwrite($gz, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = collect($db->select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"))
            ->map(fn ($row) => array_values((array) $row)[0]);

        foreach ($tables as $table) {
            $q = $ident($table);
            $create = (array) $db->selectOne("SHOW CREATE TABLE {$q}");
            $ddl = $create['Create Table'] ?? array_values($create)[1];

            gzwrite($gz, "-- Tabel {$table}\nDROP TABLE IF EXISTS {$q};\n{$ddl};\n\n");

            if (in_array($table, self::SKIP_DATA, true)) {
                continue;
            }

            $columns = null;
            $buffer = [];

            $flush = function () use ($gz, $q, &$columns, &$buffer) {
                if ($buffer) {
                    gzwrite($gz, "INSERT INTO {$q} ({$columns}) VALUES\n" . implode(",\n", $buffer) . ";\n");
                    $buffer = [];
                }
            };

            foreach ($db->table($table)->cursor() as $row) {
                $row = (array) $row;
                $columns ??= implode(',', array_map($ident, array_keys($row)));
                $buffer[] = '(' . implode(',', array_map(fn ($v) => $this->quote($pdo, $v), array_values($row))) . ')';

                if (count($buffer) >= 200) {
                    $flush();
                }
            }

            $flush();
            gzwrite($gz, "\n");
        }

        gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
    }

    private function quote(\PDO $pdo, mixed $value): string
    {
        return match (true) {
            $value === null => 'NULL',
            is_bool($value) => $value ? '1' : '0',
            is_int($value), is_float($value) => (string) $value,
            default => $pdo->quote((string) $value),
        };
    }
}
