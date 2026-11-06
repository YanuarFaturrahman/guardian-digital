<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchoolAdminController extends Controller
{
    public function create()
    {
        return view('admin.school-admins.create', [
            'schools' => School::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'school_id' => ['required', 'exists:schools,id'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'school_id.required' => 'Pilih sekolah yang akan dikoordinasi.',
            'school_id.exists' => 'Sekolah yang dipilih tidak ditemukan.',
        ]);

        $temp = User::generateTempPassword();

        $admin = (new User)->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $temp,
            'role' => 'school_admin',
            'school_id' => $data['school_id'],
            'avatar' => '🏫',
            'must_change_password' => true,
            // Dibuat langsung oleh superadmin, jadi emailnya dianggap sudah diverifikasi.
            'email_verified_at' => now(),
        ]);
        $admin->save();

        ActivityLog::record('user.school_admin_created', "{$request->user()->name} membuat akun admin sekolah {$admin->name} ({$admin->email})", $admin);

        return redirect()->route('users.index')->with('temp_password', [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $temp,
        ]);
    }

    public function destroy(User $user)
    {
        abort_unless($user->role === 'school_admin', 403, 'Hanya akun admin sekolah yang bisa dihapus dari sini.');

        DB::table('sessions')->where('user_id', $user->id)->delete();
        $user->delete();

        return back()->with('status', 'Akun admin sekolah dihapus.');
    }
}