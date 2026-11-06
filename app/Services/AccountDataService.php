<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Hak pengguna atas datanya: unduh salinan dan hapus akun. */
class AccountDataService
{
    /** Semua data pribadi dan riwayat belajar milik $user, siap dijadikan JSON. */
    public function export(User $user): array
    {
        $user->loadMissing('schoolRef');

        return [
            'diekspor_pada' => now()->toIso8601String(),
            'versi_kebijakan_privasi' => config('privacy.policy_version'),
            'profil' => [
                'id' => $user->id,
                'nama_tampilan' => $user->name,
                'email' => $user->email,
                'email_terverifikasi_pada' => $user->email_verified_at?->toIso8601String(),
                'peran' => $user->role,
                'sekolah' => $user->schoolRef?->name,
                'avatar' => $user->avatar,
                'tanggal_lahir' => $user->birth_date?->toDateString(),
                'kode_tautan' => $user->link_code,
                'xp' => $user->xp,
                'akun_dibuat_pada' => $user->created_at?->toIso8601String(),
                'verifikasi_dua_langkah_aktif' => $user->hasTwoFactorEnabled(),
            ],
            'persetujuan' => [
                'kebijakan_privasi_disetujui_pada' => $user->privacy_accepted_at?->toIso8601String(),
                'versi_disetujui' => $user->privacy_policy_version,
                'persetujuan_orang_tua_pada' => $user->parental_consent_at?->toIso8601String(),
            ],
            'riwayat_misi' => $user->attempts()->with(['scenario:id,title', 'steps.choice:id,label'])
                ->orderBy('id')->get()->map(fn ($a) => [
                    'misi' => $a->scenario?->title,
                    'status' => $a->status,
                    'skor' => $a->score,
                    'jenis_akhir' => $a->ending_kind,
                    'xp_didapat' => $a->xp_gained,
                    'selesai_pada' => $a->finished_at?->toIso8601String(),
                    'keputusan' => $a->steps->map(fn ($s) => [
                        'pilihan' => $s->choice?->label,
                        'poin' => $s->points,
                    ])->all(),
                ])->all(),
            'lencana' => $user->badges()->get()->map(fn ($b) => [
                'nama' => $b->name,
                'diraih_pada' => $b->pivot->created_at?->toIso8601String(),
            ])->all(),
            'materi_dibaca' => $user->lessons()->get()->map(fn ($l) => [
                'judul' => $l->title,
                'dibaca_pada' => $l->pivot->created_at?->toIso8601String(),
            ])->all(),
            'aksi_keamanan' => $user->safetyActions()->get()->map(fn ($a) => [
                'aksi' => $a->title,
                'dilakukan_pada' => $a->pivot->created_at?->toIso8601String(),
            ])->all(),
            'kelas' => $user->classrooms()->get()->map(fn ($c) => [
                'nama_kelas' => $c->name,
                'bergabung_pada' => $c->pivot->created_at?->toIso8601String(),
            ])->all(),
            'tautan_keluarga' => [
                'jumlah_orang_tua_tertaut' => $user->parents()->count(),
                'jumlah_anak_tertaut' => $user->children()->count(),
            ],
            'catatan_aktivitas' => ActivityLog::where('user_id', $user->id)
                ->orderByDesc('id')->limit(1000)->get()
                ->map(fn ($l) => [
                    'waktu' => $l->created_at?->toIso8601String(),
                    'aksi' => $l->action,
                    'keterangan' => $l->description,
                    'alamat_ip' => $l->ip_address,
                    'perangkat' => $l->user_agent,
                ])->all(),
        ];
    }

    /**
     * Hapus akun dan semua data turunannya (riwayat, lencana, kelas, tautan ikut terhapus lewat
     * cascade). Catatan aktivitas milik akun itu dianonimkan agar tidak lagi memuat identitasnya.
     */
    public function delete(User $user): void
    {
        $id = $user->id;
        $email = $user->email;

        DB::transaction(function () use ($user, $id, $email) {
            DB::table('sessions')->where('user_id', $id)->delete();
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            $scrub = [
                'user_id' => null,
                'actor_name' => 'Pengguna terhapus',
                'description' => 'Catatan aktivitas dari akun yang sudah dihapus.',
                'properties' => null,
                'ip_address' => null,
                'user_agent' => null,
            ];

            ActivityLog::where('user_id', $id)->update($scrub);
            ActivityLog::where('subject_type', 'User')->where('subject_id', $id)->update($scrub + ['subject_id' => null]);
            ActivityLog::where('description', 'like', '%' . $email . '%')->update($scrub);

            $user->delete();
        });
    }
}
