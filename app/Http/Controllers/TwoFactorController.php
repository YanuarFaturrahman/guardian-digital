<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\TwoFactorService;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class TwoFactorController extends Controller
{
    public function __construct(private TwoFactorService $twoFactor)
    {
    }

    // ---------- Tantangan saat masuk (belum login penuh) ----------

    public function challenge(Request $request)
    {
        if (! $this->pendingUser($request)) {
            return redirect()->route('login')->withErrors(['email' => 'Sesi masuk sudah berakhir. Silakan masuk lagi.']);
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request, AuthController $auth): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'Sesi masuk sudah berakhir. Silakan masuk lagi.']);
        }

        $request->validate([
            'code' => ['nullable', 'string', 'max:32'],
            'recovery_code' => ['nullable', 'string', 'max:32'],
        ]);

        $key = 'two-factor|' . $user->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

            return back()->withErrors(['code' => "Terlalu banyak percobaan. Coba lagi dalam {$minutes} menit."]);
        }

        $code = trim((string) $request->input('code'));
        $recovery = trim((string) $request->input('recovery_code'));

        $ok = false;
        $usedRecovery = false;

        if ($code !== '') {
            $ok = $this->twoFactor->verifyCode($user, $code);
        } elseif ($recovery !== '') {
            $ok = $usedRecovery = $this->twoFactor->useRecoveryCode($user, $recovery);
        }

        if (! $ok) {
            RateLimiter::hit($key, 15 * 60);
            ActivityLog::record('auth.two_factor_failed', "Kode verifikasi dua langkah salah untuk {$user->name}", $user, [], $user);

            return back()->withErrors([$recovery !== '' ? 'recovery_code' : 'code' => 'Kode tidak benar atau sudah dipakai.']);
        }

        RateLimiter::clear($key);
        $request->session()->forget('two_factor');

        if ($usedRecovery) {
            ActivityLog::record('auth.two_factor_recovery_used', "{$user->name} masuk memakai kode pemulihan", $user, [], $user);
        }

        // Tanpa "Ingat saya": cookie itu akan melewati kode 2FA.
        return $auth->completeLogin($request, $user, false);
    }

    private function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get('two_factor');

        if (! is_array($pending) || ($pending['expires'] ?? 0) < now()->timestamp) {
            $request->session()->forget('two_factor');

            return null;
        }

        return User::find($pending['id']);
    }

    // ---------- Pengaturan (sudah login) ----------

    public function setup(Request $request)
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return view('account.two-factor', [
                'enabled' => true,
                'left' => $this->twoFactor->recoveryCodesLeft($user),
                'required' => $user->requiresTwoFactor(),
                'recoveryCodes' => session('recovery_codes'),
            ]);
        }

        $secret = $this->twoFactor->beginSetup($user);

        return view('account.two-factor', [
            'enabled' => false,
            'secret' => trim(chunk_split($secret, 4, ' ')),
            'uri' => Totp::uri($secret, $user->email, config('app.name', 'Guardian Digital')),
            'recoveryCodes' => null,
            'required' => $user->requiresTwoFactor(),
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate(['code' => ['required', 'string', 'max:12']], [
            'code.required' => 'Masukkan kode 6 digit dari aplikasi autentikator.',
        ]);

        $codes = $this->twoFactor->confirm($user, $request->input('code'));

        if ($codes === null) {
            return back()->withErrors(['code' => 'Kode tidak cocok. Pastikan jam ponselmu akurat, lalu coba kode yang baru.']);
        }

        ActivityLog::record('user.two_factor_enabled', "{$user->name} mengaktifkan verifikasi dua langkah", $user);

        return redirect()->route('two-factor.setup')
            ->with('recovery_codes', $codes)
            ->with('status', 'Verifikasi dua langkah aktif. Simpan kode pemulihan di bawah ini.');
    }

    public function regenerate(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->confirmPassword($request);
        abort_unless($user->hasTwoFactorEnabled(), 403);

        ActivityLog::record('user.two_factor_codes_regenerated', "{$user->name} membuat ulang kode pemulihan", $user);

        return redirect()->route('two-factor.setup')
            ->with('recovery_codes', $this->twoFactor->regenerateRecoveryCodes($user))
            ->with('status', 'Kode pemulihan baru dibuat. Kode lama tidak berlaku lagi.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->requiresTwoFactor()) {
            return back()->withErrors(['password' => 'Akun admin wajib memakai verifikasi dua langkah, jadi tidak bisa dimatikan.']);
        }

        $this->confirmPassword($request);
        $this->twoFactor->disable($user);

        ActivityLog::record('user.two_factor_disabled', "{$user->name} menonaktifkan verifikasi dua langkah", $user);

        return redirect()->route('account.security')->with('status', 'Verifikasi dua langkah dimatikan.');
    }

    private function confirmPassword(Request $request): void
    {
        $request->validate(['password' => ['required', 'string']], ['password.required' => 'Masukkan kata sandimu untuk melanjutkan.']);

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['password' => 'Kata sandi tidak cocok.']);
        }
    }
}
