<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActivityLog;
use App\Services\AccountDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function index()
    {
        $children = auth()->user()->children()->get()->map(fn ($child) => [
            'child' => $child,
            'summary' => $child->progressSummary(),
            'level' => $child->levelData(),
        ]);

        return view('family.dashboard', ['rows' => $children]);
    }

    public function link(Request $request)
    {
        $data = $request->validate(
            ['code' => ['required', 'string', 'max:12']],
            ['code.required' => 'Kode tautan wajib diisi.']
        );

        $student = User::where('role', 'student')
            ->where('link_code', strtoupper(trim($data['code'])))
            ->first();

        if (! $student) {
            return back()->withErrors(['code' => 'Kode tautan tidak ditemukan. Minta anakmu menunjukkannya di berandanya.']);
        }

        $parent = auth()->user();
        $message = "Akun {$student->name} berhasil ditautkan.";

        // UU PDP: data anak diproses dengan persetujuan orang tua/wali. Menautkan akun siswa di bawah
        // usia persetujuan sekaligus menjadi persetujuan, dan hanya dengan pernyataan eksplisit.
        if ($student->needsParentalConsent()) {
            if (! $request->boolean('consent')) {
                return back()->withErrors([
                    'consent' => 'Anak ini belum berusia ' . (int) config('privacy.consent_age') . ' tahun. Centang pernyataan persetujuan sebagai orang tua/wali untuk menautkan.',
                ])->withInput();
            }

            $student->forceFill([
                'parental_consent_at' => now(),
                'parental_consent_by' => $parent->id,
            ])->save();

            ActivityLog::record('user.parental_consent_given', "{$parent->name} memberi persetujuan orang tua untuk akun {$student->name}", $student);
            $message .= ' Persetujuan orang tua tercatat, anakmu kini bisa memakai Guardian Digital.';
        }

        $parent->children()->syncWithoutDetaching([$student->id]);

        return back()->with('status', $message);
    }

    public function show(User $child)
    {
        $this->authorizeChild($child);

        $report = $child->report();
        $weakest = $report['mastery']->sortBy('mastery')->first();

        return view('family.child', $report + ['weakest' => $weakest]);
    }

    public function unlink(User $child)
    {
        $this->authorizeChild($child);
        auth()->user()->children()->detach($child->id);

        return redirect()->route('family.dashboard')->with('status', 'Tautan akun dilepas.');
    }

    /** Unduh salinan seluruh data anak (hanya wali yang memberi persetujuan). */
    public function export(User $child, AccountDataService $data)
    {
        $this->authorizeGuardian($child);
        $payload = $data->export($child);

        ActivityLog::record('user.data_exported', auth()->user()->name . ' mengunduh salinan data anak (siswa)', $child);

        return response()->streamDownload(
            fn () => print(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'data-anak-guardian-digital-' . now()->format('Ymd') . '.json',
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    /** Cabut persetujuan: akun anak terkunci sampai orang tua menyetujui lagi. */
    public function withdrawConsent(User $child)
    {
        $this->authorizeGuardian($child);

        $child->forceFill(['parental_consent_at' => null, 'parental_consent_by' => null, 'remember_token' => \Illuminate\Support\Str::random(60)])->save();
        DB::table('sessions')->where('user_id', $child->id)->delete();

        ActivityLog::record('user.parental_consent_withdrawn', auth()->user()->name . ' mencabut persetujuan orang tua untuk akun anak (siswa)', $child);

        return redirect()->route('family.dashboard')->with('status', 'Persetujuan dicabut. Akun anakmu tidak bisa dipakai sampai kamu menyetujui lagi lewat kode tautan.');
    }

    /** Hapus akun dan seluruh data anak secara permanen. */
    public function destroyChild(Request $request, User $child, AccountDataService $data)
    {
        $this->authorizeGuardian($child);
        $parent = $request->user();

        $request->validate([
            'password' => ['required', 'string'],
            'confirm_text' => ['required', 'in:HAPUS'],
        ], [
            'password.required' => 'Masukkan kata sandimu untuk melanjutkan.',
            'confirm_text.in' => 'Ketik HAPUS (huruf besar semua) untuk mengonfirmasi.',
            'confirm_text.required' => 'Ketik HAPUS untuk mengonfirmasi.',
        ]);

        if (! Hash::check($request->input('password'), $parent->password)) {
            throw ValidationException::withMessages(['password' => 'Kata sandi tidak cocok.']);
        }

        $data->delete($child);

        ActivityLog::record('user.guardian_deleted_child', "{$parent->name} menghapus akun anak (siswa) beserta datanya", null, [], $parent);

        return redirect()->route('family.dashboard')->with('status', 'Akun anak dan seluruh datanya sudah dihapus.');
    }

    /** Hak wali (unduh, cabut, hapus): tertaut, memberi persetujuan, dan anak masih di bawah usia persetujuan. */
    private function authorizeGuardian(User $child): void
    {
        $this->authorizeChild($child);

        abort_unless(
            $child->isMinor() && (int) $child->parental_consent_by === (int) auth()->id(),
            403,
            'Hanya orang tua/wali yang memberi persetujuan yang boleh melakukan ini.'
        );
    }

    private function authorizeChild(User $child): void
    {
        abort_unless(auth()->user()->children()->where('users.id', $child->id)->exists(), 403);
    }
}
