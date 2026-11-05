<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Consent;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Halaman persetujuan untuk orang tua / wali tanpa akun.
 * Diakses lewat tautan bertanda tangan (signed URL) yang dikirim ke email, berlaku terbatas.
 */
class ParentConsentController extends Controller
{
    public function show(Request $request, User $user)
    {
        abort_unless($user->role === 'student' && $user->isMinor(), 404);

        $user->load('consents');

        return view('privacy.parent-consent', [
            'student' => $user,
            'already' => $user->hasParentalConsent(),
        ]);
    }

    public function store(Request $request, User $user)
    {
        abort_unless($user->role === 'student' && $user->isMinor(), 404);

        $data = $request->validate([
            'parent_name' => ['required', 'string', 'min:3', 'max:80'],
            'accept' => ['accepted'],
        ], [
            'parent_name.required' => 'Nama lengkap orang tua / wali wajib diisi.',
            'parent_name.min' => 'Tulis nama lengkap.',
            'accept.accepted' => 'Centang pernyataan persetujuan untuk melanjutkan.',
        ]);

        Consent::grant($user, 'parental', ['name' => $data['parent_name'], 'email' => $user->parent_email]);

        ActivityLog::record('privacy.parental_consent_given', "{$data['parent_name']} (orang tua / wali) menyetujui akun {$user->name} lewat email", $user, [], null);

        return view('privacy.parent-consent-done', ['student' => $user]);
    }
}
