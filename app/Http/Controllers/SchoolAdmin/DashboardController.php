<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user();
        $school = $actor->schoolRef;

        abort_if(! $school, 403, 'Akun admin sekolah ini belum terhubung ke sekolah mana pun. Hubungi superadmin.');

        $studentIds = fn () => User::where('role', 'student')->where('school_id', $school->id)->select('id');
        $finished = fn () => Attempt::where('status', 'finished')->whereIn('user_id', $studentIds());

        return view('school.dashboard', [
            'school' => $school,
            'studentCount' => User::where('role', 'student')->where('school_id', $school->id)->count(),
            'teacherCount' => User::where('role', 'teacher')->where('school_id', $school->id)->count(),
            'parentCount' => User::where('role', 'parent')
                ->whereHas('children', fn ($q) => $q->where('school_id', $school->id))->count(),
            'classCount' => Classroom::whereHas('teacher', fn ($q) => $q->where('school_id', $school->id))->count(),
            'plays' => $finished()->count(),
            'avgScore' => (int) round($finished()->avg('score') ?? 0),
            'recentUsers' => User::manageableBy($actor)->latest()->take(8)->get(),
        ]);
    }
}