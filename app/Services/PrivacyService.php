<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Hak subjek data: mengekspor dan menghapus data pribadi sebuah akun. */
class PrivacyService
{
    /** Alasan akun tidak boleh dihapus mandiri, atau null jika boleh. */
    public function blockReason(User $user): ?string
    {
        if ($user->role === 'superadmin') {
            return 'Akun superadmin tidak bisa dihapus mandiri agar aplikasi tidak kehilangan pengelolanya. Minta superadmin lain mengaturnya.';
        }

        return null;
    }

    /** Salinan data pribadi akun dalam bentuk array (diubah ke JSON oleh pemanggil). */
    public function export(User $user): array
    {
        $user->loadMissing('schoolRef');
        $iso = fn ($date) => $date ? \Illuminate\Support\Carbon::parse($date)->toIso8601String() : null;

        $data = [
            'diekspor_pada' => now()->toIso8601String(),
            'versi_kebijakan_privasi' => config('privacy.policy_version'),
            'profil' => [
                'id' => $user->id,
                'nama_tampilan' => $user->name,
                'email' => $user->email,
                'peran' => $user->role,
                'avatar' => $user->avatar,
                'sekolah' => $user->schoolRef?->name,
                'tanggal_lahir' => $user->birth_date?->toDateString(),
                'email_orang_tua' => $user->parent_email,
                'kode_tautan_orang_tua' => $user->link_code,
                'xp' => $user->xp,
                'akun_dibuat' => $iso($user->created_at),
                'akun_diubah' => $iso($user->updated_at),
            ],
            'persetujuan' => $user->consents()->orderBy('id')->get()->map(fn ($c) => [
                'jenis' => $c->type === 'parental' ? 'orang_tua_wali' : 'pemilik_akun',
                'versi_kebijakan' => $c->policy_version,
                'diberikan_oleh' => $c->given_by_name,
                'email_pemberi' => $c->given_by_email,
                'waktu' => $iso($c->granted_at),
                'dicabut_pada' => $iso($c->revoked_at),
            ])->all(),
            'log_keamanan_akun' => ActivityLog::where('user_id', $user->id)->latest('id')->limit(500)->get()
                ->map(fn ($l) => [
                    'waktu' => $iso($l->created_at),
                    'aktivitas' => $l->action,
                    'ip' => $l->ip_address,
                    'perangkat' => $l->user_agent,
                ])->all(),
        ];

        $named = fn ($model) => $model->title ?? $model->name;

        if ($user->role === 'student') {
            $data['riwayat_misi'] = $user->attempts()->with('scenario')->withCount('steps')->get()->map(fn ($a) => [
                'misi' => $a->scenario ? $named($a->scenario) : null,
                'status' => $a->status,
                'skor' => $a->score,
                'hasil_akhir' => $a->ending_kind,
                'xp_didapat' => $a->xp_gained,
                'jumlah_pilihan_diambil' => $a->steps_count,
                'selesai_pada' => $iso($a->finished_at),
            ])->all();
        }

        if (in_array($user->role, ['student', 'parent'], true) || $user->badges()->exists()) {
            $data['lencana'] = $user->badges()->get()->map(fn ($b) => [
                'nama' => $named($b), 'diperoleh' => $iso($b->pivot->created_at),
            ])->all();
            $data['materi_dibaca'] = $user->lessons()->get()->map(fn ($l) => [
                'judul' => $named($l), 'dibaca' => $iso($l->pivot->created_at),
            ])->all();
            $data['aksi_keamanan_dilakukan'] = $user->safetyActions()->get()->map(fn ($a) => [
                'aksi' => $named($a), 'dilakukan' => $iso($a->pivot->created_at),
            ])->all();
        }

        $data['kelas_diikuti'] = $user->classrooms()->get()->map(fn ($c) => ['nama_kelas' => $c->name])->all();
        $data['akun_orang_tua_tertaut'] = $user->parents()->pluck('name')->all();
        $data['akun_anak_tertaut'] = $user->children()->pluck('name')->all();
        $data['kelas_diampu'] = $user->ownedClassrooms()->withCount('students')->get()->map(fn ($c) => [
            'nama_kelas' => $c->name, 'jumlah_siswa' => $c->students_count,
        ])->all();

        return $data;
    }

    /** Daftar data yang akan hilang, dalam bahasa biasa untuk halaman konfirmasi. */
    public function impactLines(User $user): array
    {
        $lines = [];
        $add = function (int $count, string $text) use (&$lines) {
            if ($count > 0) {
                $lines[] = "{$count} {$text}";
            }
        };

        $add($user->attempts()->count(), 'riwayat permainan misi, beserta skor dan XP-nya');
        $add($user->badges()->count(), 'lencana yang dimiliki');
        $add($user->lessons()->count(), 'catatan materi yang sudah dibaca');
        $add($user->safetyActions()->count(), 'catatan aksi keamanan yang sudah dilakukan');
        $add($user->classrooms()->count(), 'keanggotaan kelas');
        $add($user->parents()->count(), 'tautan ke akun orang tua');
        $add($user->children()->count(), 'tautan ke akun anak');

        $classes = $user->ownedClassrooms()->withCount('students')->get();
        $add($classes->count(), 'kelas yang diampu (' . $classes->sum('students_count') . ' siswa kehilangan keanggotaan di kelas itu, akun siswanya tetap ada)');

        return $lines;
    }

    /** Hapus akun beserta seluruh data turunannya (cascade) dan bersihkan jejaknya di log. */
    public function erase(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->scrubLogs($user->id);

            // Dua tabel ini tidak punya kunci asing ke pengguna
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            $user->delete();
        });
    }

    /** Hilangkan data pribadi dari log aktivitas milik atau tentang akun ini. */
    public function scrubLogs(int $userId): void
    {
        ActivityLog::where('user_id', $userId)->update([
            'user_id' => null,
            'actor_name' => null,
            'description' => 'Aktivitas dari akun yang sudah dihapus',
            'properties' => null,
            'ip_address' => null,
            'user_agent' => null,
        ]);

        ActivityLog::where('subject_type', 'User')->where('subject_id', $userId)->update([
            'subject_id' => null,
            'description' => 'Tindakan pada akun yang sudah dihapus',
            'properties' => null,
        ]);
    }
}
