<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Consent;
use App\Models\School;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

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

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
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

        // Mode pemeliharaan: hanya superadmin yang boleh masuk
        if (Setting::bool('maintenance_mode') && Auth::user()->role !== 'superadmin') {
            Auth::logout();

            return back()
                ->withErrors(['email' => Setting::read('maintenance_message') ?: 'Sistem sedang dalam pemeliharaan. Coba lagi nanti.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        ActivityLog::record('auth.login', "{$request->user()->name} masuk", $request->user(), [], $request->user());

        return redirect()->intended(route('dashboard'));
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
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'role' => ['required', 'in:student,parent,teacher'],
            'school_id' => ['nullable', 'exists:schools,id'],
            'school_name' => ['nullable', 'string', 'min:4', 'max:120'],
            'birth_date' => ['nullable', 'date', 'before:today', 'after:1930-01-01'],
            'accept_privacy' => ['accepted'],
        ], [
            'name.required' => 'Nama tampilan wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'role.in' => 'Peran yang dipilih tidak valid.',
            'school_id.exists' => 'Sekolah yang dipilih tidak ditemukan.',
            'school_name.min' => 'Nama sekolah terlalu pendek. Tulis nama lengkapnya.',
            'birth_date.before' => 'Tanggal lahir harus sebelum hari ini.',
            'birth_date.after' => 'Tanggal lahir tidak valid.',
            'accept_privacy.accepted' => 'Kamu harus menyetujui Kebijakan Privasi untuk mendaftar.',
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

        if ($data['role'] === 'student' && empty($data['birth_date'])) {
            throw ValidationException::withMessages([
                'birth_date' => 'Siswa wajib mengisi tanggal lahir.',
            ]);
        }

        $avatars = ['🦊', '🦉', '🐼', '🐯', '🐙', '🦅', '🐺', '🐬', '🦁'];

        $user = User::create(
            Arr::except($data, ['school_id', 'school_name', 'birth_date', 'accept_privacy'])
            + ['school_id' => $schoolId, 'avatar' => Arr::random($avatars)]
        );

        if ($data['role'] === 'student') {
            $user->forceFill(['birth_date' => $data['birth_date']])->save();
        }

        // Bukti persetujuan Kebijakan Privasi saat mendaftar
        Consent::grant($user, 'privacy', ['name' => $user->name, 'email' => $user->email, 'user_id' => $user->id]);

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