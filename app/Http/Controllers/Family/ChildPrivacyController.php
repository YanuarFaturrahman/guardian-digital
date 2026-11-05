<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Consent;
use App\Models\User;
use App\Services\PrivacyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/** Orang tua / wali memakai hak privasi atas nama anak yang tertaut ke akunnya. */
class ChildPrivacyController extends Controller
{
    public function __construct(private PrivacyService $privacy)
    {
    }

    public function grant(Request $request, User $child)
    {
        $this->authorizeChild($request, $child);

        $request->validate(
            ['accept' => ['accepted']],
            ['accept.accepted' => 'Centang pernyataan persetujuan untuk melanjutkan.']
        );

        $parent = $request->user();
        Consent::grant($child, 'parental', ['name' => $parent->name, 'email' => $parent->email, 'user_id' => $parent->id]);

        ActivityLog::record('privacy.parental_consent_given', "{$parent->name} (orang tua / wali) menyetujui akun {$child->name}", $child);

        return back()->with('status', "Persetujuan untuk {$child->name} tersimpan. Akunnya sekarang bisa dipakai.");
    }

    public function revoke(Request $request, User $child)
    {
        $this->authorizeChild($request, $child);

        Consent::revoke($child, 'parental');

        ActivityLog::record('privacy.parental_consent_revoked', "{$request->user()->name} mencabut persetujuan orang tua untuk akun {$child->name}", $child);

        return back()->with('status', "Persetujuan dicabut. {$child->name} tidak bisa memakai akunnya sampai persetujuan diberikan lagi.");
    }

    public function export(Request $request, User $child)
    {
        $this->authorizeChild($request, $child);
        $this->confirmPassword($request);

        ActivityLog::record('privacy.child_data_exported', "{$request->user()->name} mengunduh salinan data akun {$child->name}", $child);

        $json = json_encode($this->privacy->export($child), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response($json, 200, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="data-anak-guardian-digital-' . $child->id . '-' . now()->format('Ymd') . '.json"',
        ]);
    }

    public function erase(Request $request, User $child)
    {
        $this->authorizeChild($request, $child);
        $this->confirmPassword($request);

        $request->validate(
            ['confirm_name' => ['required', 'string']],
            ['confirm_name.required' => 'Ketik nama akun anak untuk melanjutkan.']
        );

        if (strcasecmp(trim($request->input('confirm_name')), $child->name) !== 0) {
            return back()->withErrors(['confirm_name' => 'Nama yang diketik tidak sama dengan nama akun anak.']);
        }

        $id = $child->id;
        $name = $child->name;

        $this->privacy->erase($child);

        ActivityLog::record('privacy.account_deleted', "Akun siswa (ID {$id}) dihapus atas permintaan orang tua / wali yang tertaut");

        return redirect()->route('family.dashboard')->with('status', "Akun {$name} dan seluruh datanya sudah dihapus permanen.");
    }

    private function confirmPassword(Request $request): void
    {
        $request->validate(
            ['password' => ['required', 'string']],
            ['password.required' => 'Masukkan kata sandimu untuk melanjutkan.']
        );

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            back()->withErrors(['password' => 'Kata sandi salah.'])->throwResponse();
        }
    }

    private function authorizeChild(Request $request, User $child): void
    {
        abort_unless($request->user()->role === 'parent'
            && $request->user()->children()->where('users.id', $child->id)->exists(), 403);
    }
}
