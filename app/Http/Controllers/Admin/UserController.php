<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const ROLES = [
        'student' => 'Siswa',
        'parent' => 'Orang tua',
        'teacher' => 'Guru',
        'school_admin' => 'Admin Sekolah',
        'superadmin' => 'Superadmin',
    ];

    public function index(Request $request)
    {
        $actor = $request->user();
        $roles = $this->rolesFor($actor);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(array_keys($roles))],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
        $status = $filters['status'] ?? '';

        $users = User::manageableBy($actor)->with('schoolRef')
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(
                fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")
            ))
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->when($status === 'active', fn ($query) => $query->whereNull('deactivated_at'))
            ->when($status === 'inactive', fn ($query) => $query->whereNotNull('deactivated_at'))
            ->orderBy('name')
            ->simplePaginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'q' => $filters['q'] ?? '',
            'role' => $filters['role'] ?? '',
            'status' => $status,
            'roles' => $roles,
            'scopeLabel' => $actor->role === 'superadmin'
                ? 'Semua sekolah'
                : ($actor->schoolRef?->name ?? 'Belum terhubung ke sekolah'),
        ]);
    }

    public function tempPassword(Request $request, User $user)
    {
        Gate::authorize('resetPassword', $user);

        $temp = User::generateTempPassword();

        $user->forceFill([
            'password' => $temp,
            'must_change_password' => true,
            'remember_token' => Str::random(60), // batalkan cookie "Ingat saya" yang lama
        ])->save();

        // Keluarkan pengguna dari semua perangkat (berlaku jika SESSION_DRIVER=database)
        DB::table('sessions')->where('user_id', $user->id)->delete();

        return back()->with('temp_password', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => $temp,
        ]);
    }

    public function deactivate(Request $request, User $user)
    {
        Gate::authorize('deactivate', $user);

        $user->forceFill([
            'deactivated_at' => now(),
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('sessions')->where('user_id', $user->id)->delete();

        return back()->with('status', "Akun {$user->name} dinonaktifkan. Ia tidak bisa masuk sampai diaktifkan kembali.");
    }

    public function activate(Request $request, User $user)
    {
        Gate::authorize('activate', $user);

        $user->forceFill(['deactivated_at' => null])->save();

        return back()->with('status', "Akun {$user->name} diaktifkan kembali.");
    }

    /** Halaman konfirmasi: tampilkan dampak penghapusan sebelum dieksekusi. */
    public function confirmDelete(User $user)
    {
        Gate::authorize('forceDelete', $user);

        if (! $user->deactivated_at) {
            return redirect()->route('users.index')->withErrors([
                'hapus' => 'Nonaktifkan akun ini dulu sebelum menghapusnya secara permanen.',
            ]);
        }

        return view('admin.users.delete', [
            'user' => $user,
            'impact' => $this->impactLines($user),
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        Gate::authorize('forceDelete', $user);

        if (! $user->deactivated_at) {
            return redirect()->route('users.index')->withErrors([
                'hapus' => 'Nonaktifkan akun ini dulu sebelum menghapusnya secara permanen.',
            ]);
        }

        $request->validate(
            ['confirm_email' => ['required', 'string']],
            ['confirm_email.required' => 'Ketik email akun untuk melanjutkan.']
        );

        if (strcasecmp(trim($request->input('confirm_email')), $user->email) !== 0) {
            return back()->withErrors(['confirm_email' => 'Email yang diketik tidak sama dengan email akun.']);
        }

        $name = $user->name;

        DB::transaction(function () use ($user) {
            // Dua tabel ini tidak punya kunci asing ke pengguna
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            // Data turunan (riwayat, lencana, kelas, tautan) ikut terhapus lewat cascadeOnDelete
            $user->delete();
        });

        return redirect()->route('users.index')->with('status', "Akun {$name} dihapus secara permanen.");
    }

    /** Daftar data yang akan hilang, ditulis dalam bahasa biasa untuk halaman konfirmasi. */
    private function impactLines(User $user): array
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

    /** Peran yang boleh dilihat dan difilter oleh $actor. */
    private function rolesFor(User $actor): array
    {
        return $actor->role === 'superadmin'
            ? self::ROLES
            : Arr::only(self::ROLES, ['student', 'teacher', 'parent']);
    }
}