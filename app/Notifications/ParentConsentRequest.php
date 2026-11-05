<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email ke orang tua / wali: minta persetujuan untuk akun anak. */
class ParentConsentRequest extends Notification
{
    public function __construct(private User $student, private string $url)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $days = config('privacy.consent_link_days');

        return (new MailMessage)
            ->subject('Mohon persetujuan Anda: akun Guardian Digital untuk ' . $this->student->name)
            ->greeting('Yth. Orang Tua / Wali,')
            ->line("{$this->student->name} mendaftar di Guardian Digital, platform belajar keamanan digital berbentuk simulasi.")
            ->line('Karena usianya di bawah 18 tahun, kami memerlukan persetujuan Anda sebelum akun anak dapat dipakai.')
            ->action('Tinjau dan beri persetujuan', $this->url)
            ->line("Tautan ini berlaku {$days} hari. Anda dapat membaca Kebijakan Privasi lengkap di halaman tersebut sebelum menyetujui.")
            ->line('Jika Anda tidak mengenal pendaftaran ini, abaikan email ini. Akun tidak akan aktif tanpa persetujuan Anda.');
    }
}
