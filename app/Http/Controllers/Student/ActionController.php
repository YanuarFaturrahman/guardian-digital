<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SafetyAction;
use App\Services\BadgeService;

class ActionController extends Controller
{
    public function index()
    {
        return view('student.actions', [
            'actions' => SafetyAction::orderBy('position')->get(),
            'doneIds' => auth()->user()->safetyActions()->pluck('safety_actions.id'),
        ]);
    }

    public function complete(SafetyAction $action, BadgeService $badges)
    {
        $user = auth()->user();

        if ($user->safetyActions()->where('safety_actions.id', $action->id)->exists()) {
            return back();
        }

        $user->safetyActions()->attach($action->id);
        $user->increment('xp', $action->xp_reward);
        $badges->evaluate($user->fresh());

        return back()->with('status', "Aksi tercatat! +{$action->xp_reward} XP. Kamu makin aman.");
    }
}
