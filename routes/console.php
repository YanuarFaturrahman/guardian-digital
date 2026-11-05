<?php

use Illuminate\Foundation\Inspiring;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup database otomatis tiap hari, hanya jika diaktifkan di Pengaturan Sistem.
// Butuh scheduler berjalan di server: * * * * * php artisan schedule:run
Schedule::command('backup:run --type=auto')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->when(fn () => Setting::bool('backup_auto'));

// Bersihkan log aktivitas yang melewati batas simpan.
Schedule::call(fn () => ActivityLog::prune())
    ->dailyAt('03:00')
    ->name('prune-activity-logs');
