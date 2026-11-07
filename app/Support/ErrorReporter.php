<?php

namespace App\Support;

use App\Models\ErrorLog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Throwable;

/**
 * Menyimpan error aplikasi ke database, dikelompokkan per sidik jari
 * (kelas + file + baris), jadi 1.000 kejadian yang sama = 1 baris dengan hitungan.
 *
 * Kenapa di database, bukan hanya file log? Di Vercel/Docker log dikirim ke stderr
 * dan di hosting gratis file log sulit dibuka. Dengan begini superadmin tetap bisa
 * melihat error langsung dari menu Sistem.
 *
 * Data pribadi dijaga: tanpa isi form/query string, token di URL dan alamat email di
 * pesan error disamarkan, dan jejak tumpukan hanya memuat file:baris (tanpa argumen).
 */
final class ErrorReporter
{
    private static bool $busy = false;

    public static function capture(Throwable $e): void
    {
        // Cegah rekursi bila penyimpanan error itu sendiri yang bermasalah.
        if (self::$busy) {
            return;
        }

        self::$busy = true;

        try {
            $file = self::relativePath($e->getFile());
            $fingerprint = sha1(get_class($e) . '|' . $file . '|' . $e->getLine());
            $now = now();

            $existing = ErrorLog::where('fingerprint', $fingerprint)->first();

            if ($existing) {
                $existing->update([
                    'occurrences' => $existing->occurrences + 1,
                    'last_seen_at' => $now,
                    'message' => self::cleanMessage($e),
                    'method' => self::method(),
                    'url' => self::path(),
                    'user_id' => auth()->id(),
                    // Muncul lagi setelah ditandai selesai = kambuh, buka kembali.
                    'resolved_at' => null,
                ]);

                return;
            }

            ErrorLog::create([
                'fingerprint' => $fingerprint,
                'class' => Str::limit(get_class($e), 190, ''),
                'message' => self::cleanMessage($e),
                'file' => $file,
                'line' => $e->getLine(),
                'trace' => self::trace($e),
                'method' => self::method(),
                'url' => self::path(),
                'user_id' => auth()->id(),
                'occurrences' => 1,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
            ]);
        } catch (Throwable) {
            // Gagal mencatat error tidak boleh menimbulkan error baru.
        } finally {
            self::$busy = false;
        }
    }

    public static function cleanMessage(Throwable $e): string
    {
        $message = $e->getMessage();

        // QueryException memuat nilai parameter SQL (bisa berisi email/data pribadi).
        if ($e instanceof QueryException) {
            $message = ($e->errorInfo[2] ?? null) ?: Str::before($message, ' (Connection:');
        }

        $message = preg_replace('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/', '[email]', $message) ?? $message;

        return Str::limit(trim($message) !== '' ? $message : '(tanpa pesan)', 497);
    }

    /** Ubah path absolut menjadi relatif terhadap folder proyek. */
    public static function relativePath(string $path): string
    {
        $base = rtrim(str_replace('\\', '/', base_path()), '/') . '/';
        $path = str_replace('\\', '/', $path);

        return Str::limit(str_starts_with($path, $base) ? substr($path, strlen($base)) : $path, 250, '');
    }

    /** Samarkan segmen URL yang tampak seperti token (panjang, acak) dan buang query string. */
    public static function maskPath(string $path): string
    {
        $path = Str::before($path, '?');

        $parts = array_map(
            fn ($seg) => preg_match('/^[A-Za-z0-9_\-]{24,}$/', $seg) ? '*' : $seg,
            explode('/', $path)
        );

        return Str::limit(implode('/', $parts), 250, '');
    }

    private static function path(): ?string
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return 'artisan';
        }

        $request = request();

        return $request ? self::maskPath('/' . ltrim($request->path(), '/')) : null;
    }

    private static function method(): ?string
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return 'CLI';
        }

        return request()?->method();
    }

    /** Maksimal 10 frame, hanya file:baris dan fungsi (tanpa argumen). */
    private static function trace(Throwable $e): string
    {
        $lines = [];

        foreach (array_slice($e->getTrace(), 0, 10) as $i => $frame) {
            $where = isset($frame['file'])
                ? self::relativePath($frame['file']) . ':' . ($frame['line'] ?? '?')
                : '[internal]';
            $fn = ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '');
            $lines[] = '#' . $i . ' ' . $where . ' ' . $fn . '()';
        }

        return implode("\n", $lines);
    }
}
