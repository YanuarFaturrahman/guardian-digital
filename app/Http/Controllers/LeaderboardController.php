<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $scope = $request->query('scope', 'global');

        // Orang tua: muat anak beserta kelas dan guru kelasnya, sekali saja
        $children = $user->role === 'parent'
            ? $user->children()->with('classrooms.teacher')->get()
            : collect();

        // Kelas yang boleh dilihat:
        // siswa = kelas yang diikuti, guru = kelas yang diampu,
        // orang tua = gabungan kelas semua anaknya.
        $classrooms = match ($user->role) {
            'student' => $user->classrooms()->with('teacher')->orderBy('name')->get(),
            'teacher' => $user->ownedClassrooms()->orderBy('name')->get(),
            'parent' => $children->flatMap(fn ($child) => $child->classrooms)
                ->unique('id')->sortBy('name')->values(),
            default => collect(),
        };

        // Khusus orang tua: id kelas => nama anak-anak yang ada di kelas itu
        $childNamesByClass = $classrooms->mapWithKeys(fn ($class) => [
            $class->id => $children
                ->filter(fn ($child) => $child->classrooms->contains('id', $class->id))
                ->pluck('name')->all(),
        ])->filter();

        // Kelas yang dipilih harus ada di daftar yang diizinkan; jika tidak, pakai yang pertama
        $classId = (int) $request->query('class');
        if (! $classrooms->contains('id', $classId)) {
            $classId = (int) $classrooms->first()?->id;
        }

        // Sekolah milik pengguna (siswa/guru) atau sekolah semua anak (orang tua)
        $mySchoolIds = ($user->role === 'parent'
            ? $children->pluck('school_id')
            : collect([$user->school_id]))->filter()->unique()->values();

        // PERBAIKAN: tampilkan semua sekolah yang punya pengguna (guru ATAU siswa),
        // bukan hanya yang sudah punya siswa. Sekolah saya/anak saya di urutan teratas.
        $schools = School::has('users')->orderBy('name')->get()
            ->sortBy(fn ($s) => [$mySchoolIds->contains($s->id) ? 0 : 1, $s->name])
            ->values();

        $schoolId = (int) $request->query('school');
        if (! $schools->contains('id', $schoolId)) {
            $schoolId = (int) $schools->first()?->id;
        }

        // Siswa yang dinonaktifkan tidak ikut peringkat (XP dan skornya tetap tersimpan)
        $query = User::query()->where('role', 'student')
            ->whereNull('deactivated_at')
            ->withCount('badges')->with('schoolRef');

        if ($scope === 'class' && $classId) {
            $query->whereHas('classrooms', fn ($q) => $q->where('classrooms.id', $classId));
        } elseif ($scope === 'school' && $schoolId) {
            $query->where('school_id', $schoolId);
        } else {
            $scope = 'global';
        }

        return view('leaderboard', [
            'students' => $query->orderByDesc('xp')->orderBy('name')->limit(50)->get(),
            'scope' => $scope,
            'classrooms' => $classrooms,
            'classId' => $classId,
            'childNamesByClass' => $childNamesByClass,
            'childIds' => $children->pluck('id'),
            'schools' => $schools,
            'mySchoolIds' => $mySchoolIds,
            'schoolId' => $schoolId,
        ]);
    }
}