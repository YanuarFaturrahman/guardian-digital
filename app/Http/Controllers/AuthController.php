<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
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
            return back()
                ->withErrors(['email' => 'Email atau kata sandi tidak cocok.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister()
    {
        return view('auth.register', [
            'schools' => School::orderBy('name')->get(),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'role' => ['required', 'in:student,parent,teacher'],
            'school_id' => ['nullable', 'exists:schools,id'],
            'school_name' => ['nullable', 'string', 'min:4', 'max:120'],
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
            Arr::except($data, ['school_id', 'school_name'])
            + ['school_id' => $schoolId, 'avatar' => Arr::random($avatars)]
        );

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('status', 'Selamat datang di Guardian Digital!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}