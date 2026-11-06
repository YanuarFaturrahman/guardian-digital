<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\School;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Rules\StrongPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum benar.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $email = Str::lower(trim($credentials['email']));
        $lockKey = self::lockoutKey($email);
        $max = (int) config('security.lockout.max_attempts');

        // Penguncian per akun: berlaku dari IP mana pun, termasuk saat kata sandi yang dimasukkan benar.
        if (RateLimiter::tooManyAttempts($lockKey, $max)) {
            ActivityLog::record(
                'auth.lockout',
                'Percobaan masuk ditolak karena akun terkunci sementara: ' . Str::limit($email, 120, ''),
                null,
                ['email' => Str::limit($email, 120, '')],
            );

            $minutes = max(1, (int) ceil(RateLimiter::availableIn($lockKey) / 60));

            return back()
                ->withErrors(['email' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$minutes} menit."])
                ->onlyInput('email');
        }

        $user = User::where('email', $credentials['email'])->first();

        // Hash::check tetap dijalankan walau email tidak ada, agar waktu respons tidak membocorkan email terdaftar.
        $valid = Hash::check($credentials['password'], $user?->password ?? self::dummyHash());

        if (! $user || ! $valid) {
            RateLimiter::hit($lockKey, (int) config('security.lockout.decay_minutes') * 60);

            ActivityLog::record(
                'auth.login_failed',
                'Gagal masuk dengan email ' . Str::limit($credentials['email'], 120, ''),
                null,
                ['email' => Str::limit($credentials['email'], 120, '')],
            );

            return back()
                ->withErrors(['email' => 'Email atau kata sandi tidak cocok.'])
                ->onlyInput('email');
        }

        RateLimiter::clear($lockKey);

        if ($user->deactivated_at) {
            return back()
                ->withErrors(['email' => 'Akun ini dinonaktifkan. Hubungi admin sekolah atau pengelola Guardian Digital untuk mengaktifkannya kembali.'])
                ->onlyInput('email');
        }

        // Mode pemeliharaan: hanya superadmin yang boleh masuk
        if (Setting::bool('maintenance_mode') && $user->role !== 'superadmin') {
            return back()
                ->withErrors(['email' => Setting::read('maintenance_message') ?: 'Sistem sedang dalam pemeliharaan. Coba lagi nanti.'])
                ->onlyInput('email');
        }

        // Kata sandi lama yang terbukti lemah: paksa diganti setelah masuk.
        if (config('security.force_strong_password_on_login')
            && ! $user->must_change_password
            && StrongPassword::check($credentials['password'], $user->role, $user->email) !== null) {
            $user->forceFill(['must_change_password' => true])->save();
        }

        // Verifikasi dua langkah: kata sandi benar belum cukup, tunggu kode dari aplikasi autentikator.
        if ($user->hasTwoFactorEnabled()) {
            $request->session()->regenerate();
            $request->session()->put('two_factor', [
                'id' => $user->id,
                'expires' => now()->addMinutes(5)->timestamp,
            ]);

            return redirect()->route('two-factor.challenge');
        }

        // Akun dengan 2FA wajib tidak boleh memakai "Ingat saya" (cookie itu melewati kode 2FA).
        return $this->completeLogin($request, $user, $request->boolean('remember') && ! $user->requiresTwoFactor());
    }

    /** Selesaikan login: dipakai langsung (tanpa 2FA) dan setelah kode 2FA benar. */
    public function completeLogin(Request $request, User $user, bool $remember = false): RedirectResponse
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();

        ActivityLog::record('auth.login', "{$user->name} masuk", $user, [], $user);

        return redirect()->intended(route('dashboard'));
    }

    public static function lockoutKey(string $email): string
    {
        return 'login-account|' . sha1(Str::lower(trim($email)));
    }

    private static function dummyHash(): string
    {
        // Hash bcrypt valid dari teks acak, hanya untuk menyamakan waktu pemeriksaan.
        return '$2y$12$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
    }

    public function showRegister()
    {
        if ($closed = $this->registrationClosed()) {
            return $closed;
        }

        return view('auth.register', [
            'schools' => School::orderBy('name')->get(),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        if ($closed = $this->registrationClosed()) {
            return $closed;
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'confirmed', new StrongPassword($request->input('role'), $request->input('email'))],
            'role' => ['required', 'in:student,parent,teacher'],
            'school_id' => ['nullable', 'exists:schools,id'],
            'school_name' => ['nullable', 'string', 'min:4', 'max:120'],
            'accept_privacy' => ['accepted'],
            'birth_date' => [
                Rule::requiredIf($request->input('role') === 'student'),
                'nullable', 'date', 'after:1930-01-01',
                'before_or_equal:-' . (int) config('privacy.min_student_age') . ' years',
            ],
            'confirm_adult' => [Rule::requiredIf(in_array($request->input('role'), ['parent', 'teacher'], true)), 'accepted_if:role,parent,teacher'],
        ], [
            'name.required' => 'Nama tampilan wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
            'role.in' => 'Peran yang dipilih tidak valid.',
            'school_id.exists' => 'Sekolah yang dipilih tidak ditemukan.',
            'school_name.min' => 'Nama sekolah terlalu pendek. Tulis nama lengkapnya.',
            'accept_privacy.accepted' => 'Kamu perlu menyetujui Kebijakan Privasi untuk membuat akun.',
            'birth_date.required' => 'Tanggal lahir siswa wajib diisi.',
            'birth_date.date' => 'Format tanggal lahir belum benar.',
            'birth_date.after' => 'Tanggal lahir tidak masuk akal.',
            'birth_date.before_or_equal' => 'Siswa harus berusia minimal ' . (int) config('privacy.min_student_age') . ' tahun.',
            'confirm_adult.required' => 'Guru dan orang tua harus menyatakan berusia 18 tahun atau lebih.',
            'confirm_adult.accepted_if' => 'Guru dan orang tua harus menyatakan berusia 18 tahun atau lebih.',
        ]);

        // Orang tua tidak punya sekolah. Siswa hanya memilih dari daftar.
        // Guru boleh memilih dari daftar, atau menulis nama baru (dibakukan otomatis).
        $schoolId = null;

        if ($data['role'] === 'student') {
            $schoolId = $data['school_id'] ?? null;
        } elseif ($data['role'] === 'teacher') {
            if (filled($data['school_name'] ?? null)) {
                $schoolId = School::findOrCreateByName($data['school_name'])->id;
            } else {
                $schoolId = $data['school_id'] ?? null;
            }

            if (! $schoolId) {
                throw ValidationException::withMessages([
                    'school_id' => 'Guru wajib memilih sekolah atau menulis nama sekolahnya.',
                ]);
            }
        }

        $avatars = ['🦊', '🦉', '🐼', '🐯', '🐙', '🦅', '🐺', '🐬', '🦁'];

        $user = User::create(
            Arr::except($data, ['school_id', 'school_name', 'accept_privacy', 'confirm_adult', 'birth_date'])
            + [
                'school_id' => $schoolId,
                'avatar' => Arr::random($avatars),
                'birth_date' => $data['role'] === 'student' ? $data['birth_date'] : null,
                'privacy_accepted_at' => now(),
                'privacy_policy_version' => config('privacy.policy_version'),
            ]
        );

        // Kirim tautan verifikasi. Kegagalan kirim tidak boleh menggagalkan pendaftaran.
        try {
            $user->sendEmailVerificationNotification();
        } catch (Throwable $e) {
            report($e);
        }

        Auth::login($user);
        $request->session()->regenerate();

        ActivityLog::record('auth.register', "{$user->name} mendaftar sebagai {$user->role}", $user, [], $user);

        return redirect()->route('dashboard')
            ->with('status', 'Selamat datang di Guardian Digital!');
    }

    /** Dipakai saat pendaftaran ditutup lewat Pengaturan Sistem. */
    private function registrationClosed(): ?RedirectResponse
    {
        if (Setting::bool('registration_open')) {
            return null;
        }

        return redirect()->route('login')->withErrors([
            'email' => 'Pendaftaran akun baru sedang ditutup. Hubungi admin sekolah atau pengelola Guardian Digital.',
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        if ($request->user()) {
            ActivityLog::record('auth.logout', "{$request->user()->name} keluar", null, [], $request->user());
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}