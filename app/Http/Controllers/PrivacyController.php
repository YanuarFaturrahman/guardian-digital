<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PrivacyController extends Controller
{
    public function policy()
    {
        return view('privacy.policy');
    }

    public function showAccept(Request $request)
    {
        $user = $request->user();

        if ($user->hasAcceptedCurrentPrivacyPolicy() && ! $user->needsBirthDate()) {
            return redirect()->route('dashboard');
        }

        return view('privacy.accept', ['user' => $user]);
    }

    public function accept(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'accept_privacy' => ['accepted'],
            'birth_date' => [
                Rule::requiredIf($user->needsBirthDate()),
                'nullable', 'date', 'after:1930-01-01',
                'before_or_equal:-' . (int) config('privacy.min_student_age') . ' years',
            ],
        ], [
            'accept_privacy.accepted' => 'Kamu perlu menyetujui Kebijakan Privasi untuk melanjutkan.',
            'birth_date.required' => 'Tanggal lahir wajib diisi.',
            'birth_date.date' => 'Format tanggal lahir belum benar.',
            'birth_date.after' => 'Tanggal lahir tidak masuk akal.',
            'birth_date.before_or_equal' => 'Siswa harus berusia minimal ' . (int) config('privacy.min_student_age') . ' tahun.',
        ]);

        $user->forceFill([
            'privacy_accepted_at' => now(),
            'privacy_policy_version' => config('privacy.policy_version'),
        ]);

        if ($user->needsBirthDate() && ! empty($data['birth_date'])) {
            $user->birth_date = $data['birth_date'];
        }

        $user->save();

        ActivityLog::record('user.privacy_accepted', "{$user->name} menyetujui Kebijakan Privasi versi " . config('privacy.policy_version'), $user);

        return redirect()->route('dashboard');
    }

    /** Siswa di bawah usia persetujuan menunggu orang tua/wali menautkan akun dan menyetujui. */
    public function consentPending(Request $request)
    {
        $user = $request->user();

        if (! $user->needsParentalConsent()) {
            return redirect()->route('dashboard');
        }

        return view('privacy.consent-pending', ['user' => $user]);
    }
}
