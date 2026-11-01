<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Lesson;
use App\Models\Scenario;
use App\Services\BadgeService;

class LessonController extends Controller
{
    public function index()
    {
        return view('student.lessons.index', [
            'categories' => Category::with('lessons')->get(),
            'readIds' => auth()->user()->lessons()->pluck('lessons.id'),
        ]);
    }

    public function show(Lesson $lesson)
    {
        $user = auth()->user();

        return view('student.lessons.show', [
            'lesson' => $lesson->load('category'),
            'isRead' => $user->lessons()->where('lessons.id', $lesson->id)->exists(),
            'scenarios' => Scenario::where('category_id', $lesson->category_id)
                ->where('is_published', true)->get(),
        ]);
    }

    public function complete(Lesson $lesson, BadgeService $badges)
    {
        $user = auth()->user();

        if ($user->lessons()->where('lessons.id', $lesson->id)->exists()) {
            return back();
        }

        $user->lessons()->attach($lesson->id);
        $user->increment('xp', $lesson->xp_reward);
        $badges->evaluate($user->fresh());

        return back()->with('status', "Materi selesai! +{$lesson->xp_reward} XP");
    }
}
