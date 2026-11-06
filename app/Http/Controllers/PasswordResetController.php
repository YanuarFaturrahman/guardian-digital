<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use App\Rules\StrongPassword;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class PasswordResetController extends Controller
{
    /** Halaman: masukkan email. */
    public function showForgot()
    {
        return view('auth.forgot-password');
    }

    /** Kirim tautan reset. */
    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum benar.',
        ]);

        // Hasilnya sengaja diabaikan. Pesan yang tampil selalu sama, baik email
        // terdaftar maupun tidak, agar orang asing tidak bisa menebak email siapa
        // saja yang punya akun (user enumeration).
        // Kegagalan SMTP dicatat di log tetapi tidak ditampilkan, supaya halaman ini tidak
        // berubah perilaku dan tidak menjadi 500 saat layanan email sedang bermasalah.
        try {
            PasswordBroker::sendResetLink($request->only('email'));
        } catch (Throwable $e) {
            report($e);
        }

        $minutes = config('auth.passwords.users.expire');

        return back()->with(
            'status',
            "Jika email itu terdaftar, tautan untuk mengatur ulang kata sandi sudah dikirim. "
            . "Periksa kotak masuk dan folder spam. Tautan berlaku {$minutes} menit."
        );
    }

    /** Halaman: buat kata sandi baru (dibuka dari tautan email). */
    public function showReset(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /** Simpan kata sandi baru. */
    public function reset(Request $request): RedirectResponse
    {
        $role = User::where('email', $request->input('email'))->value('role');

        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', new StrongPassword($role, $request->input('email'))],
        ], [
            'email.required' => 'Email wajib diisi.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ]);

        $status = PasswordBroker::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                // Cast "hashed" di model User otomatis meng-hash kata sandi.
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    'must_change_password' => false,
                ])->save();

                // Membuka tautan di email membuktikan pemilik akun menguasai alamat itu.
                if (! $user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                }

                // Buka kunci login per akun (kasus lupa kata sandi setelah salah berkali-kali).
                RateLimiter::clear(AuthController::lockoutKey($user->email));

                // Keluarkan akun dari semua perangkat yang masih login.
                if (config('session.driver') === 'database') {
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                }

                event(new PasswordReset($user));
            }
        );

        if ($status === PasswordBroker::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', 'Kata sandi berhasil diubah. Silakan masuk dengan kata sandi baru.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Tautan tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.']);
    }
}