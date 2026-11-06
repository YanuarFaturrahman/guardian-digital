<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Rules\StrongPassword;
use App\Services\AccountDataService;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function security(Request $request, TwoFactorService $twoFactor)
    {
        $user = $request->user();

        return view('account.security', [
            'user' => $user,
            'twoFactorEnabled' => $user->hasTwoFactorEnabled(),
            'twoFactorRequired' => $user->requiresTwoFactor(),
            'min' => StrongPassword::privileged($user->role)
                ? config('security.password.min_length_privileged')
                : config('security.password.min_length'),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', new StrongPassword($user->role, $user->email)],
        ], [
            'current_password.required' => 'Masukkan kata sandimu yang sekarang.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Kata sandi sekarang tidak cocok.']);
        }

        if (Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['password' => 'Kata sandi baru tidak boleh sama dengan yang sekarang.']);
        }

        $user->forceFill([
            'password' => $data['password'],
            'must_change_password' => false,
            'remember_token' => Str::random(60),
        ])->save();

        // Keluarkan perangkat lain (berlaku jika SESSION_DRIVER=database); perangkat ini tetap masuk.
        if (config('session.driver') === 'database') {
            DB::table('sessions')->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())->delete();
        }

        $request->session()->regenerate();

        ActivityLog::record('user.password_changed', "{$user->name} mengganti kata sandinya", $user);

        return back()->with('status', 'Kata sandi berhasil diganti. Perangkat lain dikeluarkan.');
    }

    // ---------- Privasi: unduh dan hapus data sendiri ----------

    public function privacy(Request $request)
    {
        $user = $request->user();

        return view('account.privacy', [
            'user' => $user,
            'canSelfDelete' => ! in_array($user->role, ['superadmin', 'school_admin'], true),
        ]);
    }

    public function export(Request $request, AccountDataService $data)
    {
        $user = $request->user();
        $payload = $data->export($user);

        ActivityLog::record('user.data_exported', "{$user->name} mengunduh salinan datanya", $user);

        return response()->streamDownload(
            fn () => print(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'data-guardian-digital-' . now()->format('Ymd') . '.json',
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    public function destroy(Request $request, AccountDataService $data): RedirectResponse
    {
        $user = $request->user();

        if (in_array($user->role, ['superadmin', 'school_admin'], true)) {
            return back()->withErrors(['password' => 'Akun admin tidak bisa dihapus sendiri. Hubungi pengelola Guardian Digital.']);
        }

        $request->validate([
            'password' => ['required', 'string'],
            'confirm_text' => ['required', 'in:HAPUS'],
        ], [
            'password.required' => 'Masukkan kata sandimu untuk melanjutkan.',
            'confirm_text.required' => 'Ketik HAPUS untuk mengonfirmasi.',
            'confirm_text.in' => 'Ketik HAPUS (huruf besar semua) untuk mengonfirmasi.',
        ]);

        if (! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages(['password' => 'Kata sandi tidak cocok.']);
        }

        $role = $user->role;

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $data->delete($user);

        // Tanpa nama atau email: catatan ini tidak memuat identitas orang yang sudah menghapus datanya.
        ActivityLog::record('user.self_deleted', "Seorang pengguna ({$role}) menghapus akunnya sendiri", null, [], null);

        return redirect()->route('home')->with('status', 'Akunmu dan seluruh datanya sudah dihapus.');
    }
}
