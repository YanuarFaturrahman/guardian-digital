<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SafetyAction;
use App\Services\BadgeService;

class ActionController extends Controller
{
    public function index()
    {
        $actions = SafetyAction::orderBy('priority')->orderBy('position')->get();
        $doneIds = auth()->user()->safetyActions()->pluck('safety_actions.id');

        // Kelompokkan per tingkat urgensi: Darurat, Penting, Tambahan
        $tiers = collect(SafetyAction::PRIORITIES)
            ->map(function (array $info, int $priority) use ($actions, $doneIds) {
                $items = $actions->where('priority', $priority)->values();
                $done = $items->whereIn('id', $doneIds)->count();
                $total = $items->count();

                return $info + [
                    'priority' => $priority,
                    'items' => $items,
                    'done' => $done,
                    'total' => $total,
                    'percent' => $total ? (int) round($done / $total * 100) : 0,
                ];
            })
            ->filter(fn (array $tier) => $tier['total'] > 0);

        return view('student.actions', [
            'tiers' => $tiers,
            'doneIds' => $doneIds,
            'totalDone' => $tiers->sum('done'),
            'totalAll' => $tiers->sum('total'),
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