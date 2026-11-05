<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchoolController extends Controller
{
    public function index()
    {
        $schools = School::withCount([
            'users',
            'users as students_count' => fn ($q) => $q->where('role', 'student'),
            'users as teachers_count' => fn ($q) => $q->where('role', 'teacher'),
        ])->orderBy('name')->get();

        return view('admin.schools', compact('schools'));
    }

    public function update(Request $request, School $school)
    {
        $data = $request->validate(
            ['name' => ['required', 'string', 'min:4', 'max:120']],
            ['name.min' => 'Nama sekolah terlalu pendek.']
        );

        $name = School::cleanName($data['name']);
        $key = School::makeKey($name);

        if (School::where('match_key', $key)->where('id', '!=', $school->id)->exists()) {
            return back()->withErrors([
                'name' => 'Nama itu sudah dipakai sekolah lain. Gunakan tombol Gabungkan.',
            ]);
        }

        $school->update(['name' => $name, 'match_key' => $key]);

        return back()->with('status', 'Nama sekolah diperbarui.');
    }

    public function merge(Request $request, School $school)
    {
        $data = $request->validate(['target_id' => ['required', 'exists:schools,id']]);

        if ((int) $data['target_id'] === $school->id) {
            return back()->withErrors(['target_id' => 'Pilih sekolah tujuan yang berbeda.']);
        }

        $target = School::findOrFail($data['target_id']);

        DB::transaction(function () use ($school, $target) {
            User::where('school_id', $school->id)->update(['school_id' => $target->id]);
            $school->delete();
        });

        ActivityLog::record('school.merged', "{$request->user()->name} menggabungkan sekolah \"{$school->name}\" ke \"{$target->name}\"", $target, ['dari_id' => $school->id]);

        return back()->with('status', "Sekolah digabungkan ke {$target->name}.");
    }

    public function destroy(School $school)
    {
        if ($school->users()->exists()) {
            return back()->withErrors([
                'name' => 'Sekolah ini masih punya pengguna. Gabungkan ke sekolah lain dulu.',
            ]);
        }

        $school->delete();

        return back()->with('status', 'Sekolah dihapus.');
    }
}