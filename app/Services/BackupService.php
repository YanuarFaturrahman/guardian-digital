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


    /**
     * Periksa integritas satu cadangan: arsip gzip utuh (tidak terpotong/rusak) dan
     * isinya mengenali format yang benar. Tidak memulihkan apa pun.
     *
     * @return array{ok:bool,message:string}
     */
    public function verify(string $name): array
    {
        if (! $this->exists($name)) {
            return ['ok' => false, 'message' => 'Berkas backup tidak ditemukan.'];
        }

        $isSqlite = str_contains($name, '.sqlite.');
        $in = @fopen($this->path($name), 'rb');

        if ($in === false) {
            return ['ok' => false, 'message' => 'Berkas backup tidak bisa dibuka.'];
        }

        $ctx = inflate_init(ZLIB_ENCODING_GZIP);
        $head = '';
        $tail = '';
        $tables = 0;
        $carry = '';

        try {
            while (! feof($in)) {
                $chunk = fread($in, 1024 * 256);

                if ($chunk === false || $chunk === '') {
                    break;
                }

                $data = @inflate_add($ctx, $chunk, ZLIB_NO_FLUSH);

                if ($data === false) {
                    return ['ok' => false, 'message' => 'Arsip rusak: data gzip tidak bisa dibaca.'];
                }

                if (strlen($head) < 64) {
                    $head .= substr($data, 0, 64 - strlen($head));
                }

                if (! $isSqlite) {
                    // Sisa 13 karakter dari potongan sebelumnya (pola 14 karakter) agar tidak terhitung dua kali.
                    $buffer = $carry . $data;
                    $tables += substr_count($buffer, "\nCREATE TABLE ");
                    $carry = substr($buffer, -13);
                }

                $tail = substr($tail . $data, -200);
            }
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Arsip rusak: ' . $e->getMessage()];
        } finally {
            fclose($in);
        }

        if (inflate_get_status($ctx) !== ZLIB_STREAM_END) {
            return ['ok' => false, 'message' => 'Arsip terpotong (tidak lengkap). Buat backup baru.'];
        }

        if ($isSqlite) {
            return str_starts_with($head, "SQLite format 3\0")
                ? ['ok' => true, 'message' => 'Arsip utuh dan berisi database SQLite yang valid.']
                : ['ok' => false, 'message' => 'Arsip utuh, tetapi isinya bukan database SQLite.'];
        }

        if (! str_contains($head, '-- Guardian Digital: cadangan database')) {
            return ['ok' => false, 'message' => 'Arsip utuh, tetapi bukan backup Guardian Digital.'];
        }

        if (! str_contains($tail, 'SET FOREIGN_KEY_CHECKS=1;')) {
            return ['ok' => false, 'message' => 'Backup tidak selesai ditulis (penutup tidak ditemukan).'];
        }

        return ['ok' => true, 'message' => "Arsip utuh dan lengkap ({$tables} tabel tercadangkan)."];
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
