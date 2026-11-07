<?php

namespace App\Support;

use App\Mail\SystemAlertMail;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Peringatan email ke semua superadmin aktif saat ada masalah sistem
 * (kesehatan turun, backup gagal). Hanya jalan jika diaktifkan di Pengaturan Sistem
 * dan email benar-benar bisa terkirim. Satu jenis peringatan hanya dikirim sekali
 * per masa jeda (bawaan 6 jam) supaya kotak masuk tidak dibanjiri.
 */
final class SystemAlert
{
    public const COOLDOWN_HOURS = 6;

    /** @return bool true jika email dikirim */
    public static function send(string $key, string $subject, string $body): bool
    {
        try {
            if (! Setting::bool('alerts_enabled') || ! MailStatus::isDeliverable()) {
                return false;
            }

            $to = User::where('role', 'superadmin')
                ->whereNull('deactivated_at')
                ->pluck('email')
                ->filter()
                ->all();

            if (! $to) {
                return false;
            }

            // Cache::add atomik: hanya satu proses yang lolos selama masa jeda.
            if (! Cache::add('system-alert:' . $key, 1, now()->addHours(self::COOLDOWN_HOURS))) {
                return false;
            }

            Mail::to($to)->send(new SystemAlertMail($subject, $body));

            ActivityLog::record('system.alert_sent', 'Peringatan email dikirim: ' . Str::limit($subject, 200), null, ['kunci' => $key]);

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
