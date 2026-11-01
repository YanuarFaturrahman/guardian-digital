<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class PasswordChangeController extends Controller
{
    public function edit(Request $request)
    {
        // Halaman ini hanya untuk pengguna yang sedang memakai kata sandi sementara
        if (! $request->user()->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Penting: tanpa pengecekan ini, siapa pun yang sedang login bisa mengganti
        // kata sandi tanpa tahu kata sandi lamanya.
        abort_unless($user->must_change_password, 403);

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ]);

        if (Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Kata sandi baru tidak boleh sama dengan kata sandi sementara.',
            ]);
        }

        // Cast 'hashed' di model User otomatis meng-hash kata sandi
        $user->forceFill([
            'password' => $data['password'],
            'must_change_password' => false,
        ])->save();

        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('status', 'Kata sandi berhasil diganti. Selamat datang di Guardian Digital!');
    }
}