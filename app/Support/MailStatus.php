<?php

namespace App\Support;

/** Memeriksa apakah email benar-benar bisa terkirim ke kotak masuk (bukan hanya ditulis ke log). */
final class MailStatus
{
    public static function mailer(): string
    {
        return (string) config('mail.default');
    }

    public static function isDeliverable(): bool
    {
        $mailer = self::mailer();

        if (in_array($mailer, ['log', 'array'], true)) {
            return false;
        }

        if ($mailer === 'smtp') {
            $host = (string) config('mail.mailers.smtp.host');

            return $host !== '' && ! in_array($host, ['127.0.0.1', 'localhost', 'mailpit', 'mailhog'], true);
        }

        return true; // ses, postmark, resend, mailgun, failover, dll.
    }

    /** Verifikasi email hanya dituntut jika diaktifkan DAN email memang bisa terkirim. */
    public static function emailVerificationActive(): bool
    {
        return (bool) config('security.email_verification') && self::isDeliverable();
    }

    public static function describe(): string
    {
        return self::isDeliverable()
            ? 'Email dikirim lewat "' . self::mailer() . '".'
            : 'MAIL_MAILER="' . self::mailer() . '": email hanya ditulis ke log dan tidak sampai ke pengguna.';
    }
}
