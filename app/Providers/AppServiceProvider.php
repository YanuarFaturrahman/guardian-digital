<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\Badge;
use App\Models\Category;
use App\Models\Lesson;
use App\Models\SafetyAction;
use App\Models\Scenario;
use App\Models\School;
use App\Models\User;
use App\Observers\AuditObserver;
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

        // Jejak audit: catat tambah / ubah / hapus konten dan sekolah oleh pengguna yang login
        foreach ([Category::class, Scenario::class, Lesson::class, Badge::class, SafetyAction::class, School::class] as $model) {
            $model::observe(AuditObserver::class);
        }

        // Jejak audit: perubahan peran akun
        User::updated(function (User $user) {
            if ($user->wasChanged('role')) {
                $from = $user->getOriginal('role');
                ActivityLog::record(
                    'user.role_changed',
                    "Peran {$user->name} diubah dari {$from} menjadi {$user->role}",
                    $user,
                    ['dari' => $from, 'ke' => $user->role],
                );
            }
        });

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