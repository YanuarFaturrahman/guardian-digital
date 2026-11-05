<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Consent;
use App\Notifications\ParentConsentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Throwable;

/** Gerbang persetujuan untuk pemilik akun (dan permintaan persetujuan ke orang tua). */
class ConsentController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load('consents');
        $gate = $user->privacyGate();

        if ($gate === null) {
            return redirect()->route('dashboard');
        }

        return view('privacy.consent', ['user' => $user, 'gate' => $gate]);
    }

    public function accept(Request $request)
    {
        $user = $request->user();
        $needsBirthDate = $user->role === 'student' && $user->birth_date === null;

        $rules = ['accept' => ['accepted']];
        if ($needsBirthDate) {
            $rules['birth_date'] = ['required', 'date', 'before:today', 'after:1930-01-01'];
        }

        $data = $request->validate($rules, [
            'accept.accepted' => 'Centang persetujuan untuk melanjutkan.',
            'birth_date.required' => 'Tanggal lahir wajib diisi.',
            'birth_date.before' => 'Tanggal lahir harus sebelum hari ini.',
            'birth_date.after' => 'Tanggal lahir tidak valid.',
        ]);

        // Tanggal lahir hanya bisa diisi sekali oleh pemilik akun, supaya tidak dipakai menghindari persetujuan orang tua
        if ($needsBirthDate) {
            $user->forceFill(['birth_date' => $data['birth_date']])->save();
        }

        if (! $user->hasAcceptedPolicy()) {
            Consent::grant($user, 'privacy', ['name' => $user->name, 'email' => $user->email, 'user_id' => $user->id]);
            ActivityLog::record('privacy.consent_given', "{$user->name} menyetujui Kebijakan Privasi versi " . config('privacy.policy_version'), $user);
        }

        return redirect()->route('consent.show');
    }

    /** Kirim (atau kirim ulang) tautan persetujuan ke email orang tua / wali. */
    public function requestParent(Request $request)
    {
        $user = $request->user();

        abort_unless($user->role === 'student' && $user->isMinor(), 403);

        $data = $request->validate(
            ['parent_email' => ['required', 'email', 'max:120']],
            ['parent_email.required' => 'Email orang tua / wali wajib diisi.', 'parent_email.email' => 'Format email tidak valid.']
        );

        if (strcasecmp($data['parent_email'], $user->email) === 0) {
            return back()->withErrors(['parent_email' => 'Gunakan email orang tua / wali, bukan emailmu sendiri.'])->withInput();
        }

        $user->forceFill(['parent_email' => $data['parent_email']])->save();

        $url = URL::temporarySignedRoute('consent.parent', now()->addDays((int) config('privacy.consent_link_days')), ['user' => $user->id]);

        // Di lingkungan lokal, tautan juga dicatat di storage/logs agar mudah diuji tanpa SMTP
        if (app()->environment('local')) {
            Log::info("Tautan persetujuan orang tua untuk {$user->email}: {$url}");
        }

        try {
            Notification::route('mail', $data['parent_email'])->notify(new ParentConsentRequest($user, $url));
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['parent_email' => 'Email gagal dikirim. Periksa pengaturan email server, atau minta orang tuamu menautkan akunnya lewat Kode Tautan.']);
        }

        ActivityLog::record('privacy.parent_consent_requested', "{$user->name} meminta persetujuan orang tua / wali lewat email", $user);

        return back()->with('status', 'Tautan persetujuan sudah dikirim ke ' . $data['parent_email'] . '. Minta orang tuamu membuka emailnya (cek juga folder spam).');
    }
}
