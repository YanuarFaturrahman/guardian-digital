<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('teacher.dashboard', [
            'classrooms' => auth()->user()->ownedClassrooms()->withCount('students')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(
            ['name' => ['required', 'string', 'max:80']],
            ['name.required' => 'Nama kelas wajib diisi.']
        );

        Classroom::create([
            'teacher_id' => auth()->id(),
            'name' => $data['name'],
            'join_code' => Classroom::makeCode(),
        ]);

        return back()->with('status', 'Kelas dibuat. Bagikan kode kelas ke siswa.');
    }

    public function show(Classroom $classroom)
    {
        $this->authorizeClass($classroom);

        $students = $classroom->students()->orderByDesc('xp')->get();

        $rows = $students->map(fn ($s) => [
            'student' => $s,
            'summary' => $s->progressSummary(),
            'level' => $s->levelData(),
        ]);

        // Rata-rata penguasaan per kategori untuk seluruh kelas
        $masteryByCategory = $students
            ->flatMap(fn ($s) => $s->mastery())
            ->groupBy('id')
            ->map(fn ($group) => (int) round($group->avg('mastery')));

        return view('teacher.classroom', [
            'classroom' => $classroom,
            'rows' => $rows,
            'categories' => Category::all(),
            'masteryByCategory' => $masteryByCategory,
        ]);
    }

    public function student(Classroom $classroom, User $student)
    {
        $this->authorizeClass($classroom);
        abort_unless($classroom->students()->where('users.id', $student->id)->exists(), 404);

        return view('teacher.student', $student->report() + ['classroom' => $classroom]);
    }

    private function authorizeClass(Classroom $classroom): void
    {
        abort_unless($classroom->teacher_id === auth()->id(), 403);
    }
}
