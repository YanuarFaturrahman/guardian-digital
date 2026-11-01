<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Paksa skema HTTPS saat berada di environment production
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            $minutes = config('auth.passwords.users.expire');

            // Khusus mode lokal: tulis tautan polos ke log supaya mudah disalin.
            // (Email di log tampil terpotong dan terenkripsi, jadi tautannya sulit dipakai.)
            if (app()->environment('local')) {
                logger()->info("Link reset kata sandi untuk {$notifiable->email}: {$url}");
            }

            return (new MailMessage)
                ->subject('Atur Ulang Kata Sandi Guardian Digital')
                ->greeting("Halo, {$notifiable->name}!")
                ->line('Kami menerima permintaan untuk mengatur ulang kata sandi akunmu.')
                ->action('Atur Ulang Kata Sandi', $url)
                ->line("Tautan ini berlaku {$minutes} menit.")
                ->line('Jika kamu tidak merasa memintanya, abaikan email ini. Kata sandimu tetap aman dan tidak berubah.')
                ->salutation('Salam, Tim Guardian Digital');
        });
    }
}