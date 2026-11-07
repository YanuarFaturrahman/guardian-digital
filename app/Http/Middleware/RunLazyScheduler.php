<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Pengganti cron untuk hosting gratis yang tidak punya cron job (mis. InfinityFree).
 *
 * Dijalankan SETELAH respons dikirim ke pengunjung (terminate), paling sering sekali
 * per 10 menit, dan setiap pekerjaan harian hanya sekali per hari:
 *  - 02:00 ke atas : backup otomatis (hanya jika diaktifkan di Pengaturan Sistem)
 *  - 03:00 ke atas : bersihkan log aktivitas yang melewati batas simpan
 *  - tiap tick     : pemeriksaan kesehatan (riwayat uptime + peringatan email)
 *  - 04:00 ke atas : pembersihan data kedaluwarsa (sesi, token, cache, error lama, dll.)
 *
 * Aktif hanya jika LAZY_SCHEDULER=true di .env. Di server yang punya cron sungguhan, biarkan false.
 */
class RunLazyScheduler
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! config('security.lazy_scheduler') || ! $request->isMethod('GET')) {
            return;
        }

        try {
            // Pemeriksaan murah: satu query baca per permintaan.
            if (Cache::has('lazy-scheduler:tick')) {
                return;
            }

            Cache::put('lazy-scheduler:tick', 1, now()->addMinutes(10));

            $now = now();
            $day = $now->format('Ymd');

            if ($now->hour >= 2
                && Setting::bool('backup_auto')
                && Cache::add("lazy-scheduler:backup:{$day}", 1, now()->addDay())) {
                @set_time_limit(300);
                Artisan::call('backup:run', ['--type' => 'auto']);
            }

            if ($now->hour >= 3 && Cache::add("lazy-scheduler:prune:{$day}", 1, now()->addDay())) {
                ActivityLog::prune();
            }

            // Tiap tick (maks. sekali per 10 menit). Dibungkus sendiri supaya kegagalannya
            // tidak menghentikan pekerjaan harian di bawah.
            try {
                Artisan::call('system:health-check');
            } catch (Throwable $e) {
                report($e);
            }

            if ($now->hour >= 4 && Cache::add("lazy-scheduler:maintain:{$day}", 1, now()->addDay())) {
                Artisan::call('system:maintain');
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
