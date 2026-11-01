<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\Scenario;
use App\Models\ScenarioChoice;
use App\Models\ScenarioNode;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GameService
{
    public function __construct(private BadgeService $badges)
    {
    }

    /** Mulai misi, atau lanjutkan jika masih ada permainan yang belum selesai. */
    public function start(User $user, Scenario $scenario): Attempt
    {
        $existing = Attempt::where('user_id', $user->id)
            ->where('scenario_id', $scenario->id)
            ->where('status', 'playing')
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $start = $scenario->nodes()->where('is_start', true)->firstOrFail();

        return Attempt::create([
            'user_id' => $user->id,
            'scenario_id' => $scenario->id,
            'current_node_id' => $start->id,
            'status' => 'playing',
        ]);
    }

    /** Proses satu keputusan: catat langkah, pindah adegan, selesaikan jika sampai akhir. */
    public function choose(Attempt $attempt, ScenarioChoice $choice): Attempt
    {
        abort_unless(
            $attempt->status === 'playing' && $choice->node_id === $attempt->current_node_id,
            422,
            'Pilihan ini tidak valid untuk adegan saat ini.'
        );

        return DB::transaction(function () use ($attempt, $choice) {
            $attempt->steps()->create([
                'node_id' => $choice->node_id,
                'choice_id' => $choice->id,
                'points' => $choice->points,
            ]);

            $next = $choice->next;
            abort_if($next === null, 500, 'Pilihan belum punya adegan tujuan.');

            $attempt->current_node_id = $next->id;
            $attempt->save();

            if ($next->isEnding()) {
                $this->finish($attempt, $next);
            }

            return $attempt->fresh();
        });
    }

    /** Hitung skor akhir dan XP. XP hanya bertambah jika skor terbaik meningkat. */
    private function finish(Attempt $attempt, ScenarioNode $ending): void
    {
        $scenario = $attempt->scenario;
        $score = max(0, min(100, (int) $attempt->steps()->sum('points')));

        $previous = Attempt::where('user_id', $attempt->user_id)
            ->where('scenario_id', $scenario->id)
            ->where('status', 'finished')
            ->where('id', '!=', $attempt->id);

        $firstTime = ! (clone $previous)->exists();
        $previousBest = (int) (clone $previous)->max('score');

        $xpNew = (int) round($scenario->xp_reward * $score / 100);
        $xpOld = (int) round($scenario->xp_reward * $previousBest / 100);
        $gain = max(0, $xpNew - $xpOld) + ($firstTime ? 10 : 0);

        $attempt->update([
            'status' => 'finished',
            'score' => $score,
            'ending_kind' => $ending->kind,
            'xp_gained' => $gain,
            'finished_at' => now(),
        ]);

        $user = $attempt->user;
        $user->increment('xp', $gain);
        $this->badges->evaluate($user->fresh());
    }
}
