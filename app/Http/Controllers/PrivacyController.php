<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\PrivacyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PrivacyController extends Controller
{
    public function __construct(private PrivacyService $privacy)
    {
    }

    /** Kebijakan privasi: halaman publik. */
    public function policy()
    {
        return view('privacy.policy');
    }

    /** Pusat privasi akun: status persetujuan, unduh data, hapus akun. */
    public function account(Request $request)
    {
        $user = $request->user()->load('consents');

        return view('privacy.account', [
            'user' => $user,
            'blockReason' => $this->privacy->blockReason($user),
            'impact' => $this->privacy->impactLines($user),
        ]);
    }

    /** Unduh seluruh data pribadi sebagai berkas JSON. Perlu kata sandi. */
    public function export(Request $request)
    {
        $request->validate(
            ['password' => ['required', 'string']],
            ['password.required' => 'Masukkan kata sandimu untuk mengunduh data.']
        );

        $user = $request->user();

        if (! Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors(['password' => 'Kata sandi salah.']);
        }

        ActivityLog::record('privacy.data_exported', "{$user->name} mengunduh salinan data pribadinya", $user);

        $json = json_encode($this->privacy->export($user), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response($json, 200, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="data-guardian-digital-' . $user->id . '-' . now()->format('Ymd') . '.json"',
        ]);
    }

    /** Hapus akun sendiri secara permanen. Perlu kata sandi dan ketikan email. */
    public function destroy(Request $request)
    {
        $user = $request->user();

        if ($reason = $this->privacy->blockReason($user)) {
            return back()->withErrors(['hapus' => $reason]);
        }

        $request->validate([
            'password' => ['required', 'string'],
            'confirm_email' => ['required', 'string'],
        ], [
            'password.required' => 'Masukkan kata sandimu.',
            'confirm_email.required' => 'Ketik email akunmu untuk melanjutkan.',
        ]);

        if (! Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors(['password' => 'Kata sandi salah.']);
        }

        if (strcasecmp(trim($request->input('confirm_email')), $user->email) !== 0) {
            return back()->withErrors(['confirm_email' => 'Email yang diketik tidak sama dengan email akunmu.']);
        }

        $id = $user->id;
        $role = $user->role;

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $this->privacy->erase($user);

        // Sengaja tanpa nama atau email: hanya ID dan peran, agar log tidak menyimpan data pribadi
        ActivityLog::record('privacy.account_deleted', "Akun (ID {$id}, peran {$role}) dihapus atas permintaan pemiliknya sendiri");

        return redirect()->route('home')->with('status', 'Akunmu dan seluruh datanya sudah dihapus permanen.');
    }
}
