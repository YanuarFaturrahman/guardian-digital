<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Lesson;
use App\Models\SafetyAction;
use App\Models\Scenario;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $summary = $user->progressSummary();

        // Tiga jalur: Recognize (misi), Learn (materi), Act (aksi)
        $pillars = [
            'recognize' => [
                'done' => $summary['scenarios_done'],
                'total' => Scenario::where('is_published', true)->count(),
            ],
            'learn' => ['done' => $summary['lessons_read'], 'total' => Lesson::count()],
            'act' => ['done' => $summary['actions_done'], 'total' => SafetyAction::count()],
        ];

        $doneIds = $user->attempts()->where('status', 'finished')->pluck('scenario_id');

        return view('student.dashboard', [
            'user' => $user,
            'summary' => $summary,
            'pillars' => $pillars,
            'level' => $user->levelData(),
            'mastery' => $user->mastery(),
            'nextScenario' => Scenario::with('category')
                ->where('is_published', true)
                ->whereNotIn('id', $doneIds)
                ->orderBy('difficulty')->first(),
            'playing' => $user->attempts()->where('status', 'playing')
                ->with('scenario')->latest('id')->first(),
            'rank' => User::where('role', 'student')->where('xp', '>', $user->xp)->count() + 1,
            'classrooms' => $user->classrooms()->with('teacher')->get(),
            'parentCount' => $user->parents()->count(),
            'recentBadges' => $user->badges()->latest('badge_user.created_at')->take(4)->get(),
            'schools' => School::orderBy('name')->get(),
        ]);
    }

    public function joinClass(Request $request)
    {
        $data = $request->validate(
            ['code' => ['required', 'string', 'max:10']],
            ['code.required' => 'Kode kelas wajib diisi.']
        );

        $classroom = Classroom::with('teacher.schoolRef')
            ->where('join_code', strtoupper(trim($data['code'])))->first();

        if (! $classroom) {
            return back()->withErrors(['code' => 'Kode kelas tidak ditemukan.']);
        }

        $user = auth()->user();
        $user->classrooms()->syncWithoutDetaching([$classroom->id]);

        $message = "Kamu bergabung ke kelas {$classroom->name}.";

        // PERBAIKAN: sekolah siswa mengikuti sekolah guru dari kelas yang dimasuki.
        // Kelas adalah sumber yang paling tepercaya karena kodenya berasal dari guru.
        $teacherSchoolId = $classroom->teacher->school_id;

        if ($teacherSchoolId && $user->school_id !== $teacherSchoolId) {
            $user->update(['school_id' => $teacherSchoolId]);
            $message .= " Sekolahmu otomatis diatur ke {$classroom->teacher->school}.";
        }

        return back()->with('status', $message);
    }

    public function updateSchool(Request $request)
    {
        $data = $request->validate(
            ['school_id' => ['required', 'exists:schools,id']],
            ['school_id.required' => 'Pilih sekolah dari daftar.']
        );

        $request->user()->update(['school_id' => $data['school_id']]);

        return back()->with('status', 'Sekolah diperbarui.');
    }
}