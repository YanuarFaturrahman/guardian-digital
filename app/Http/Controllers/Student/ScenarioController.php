<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Category;
use App\Models\Scenario;
use App\Models\ScenarioChoice;
use App\Services\GameService;

class ScenarioController extends Controller
{
    public function __construct(private GameService $game)
    {
    }

    public function index()
    {
        $user = auth()->user();

        return view('student.scenarios.index', [
            'categories' => Category::with([
                'scenarios' => fn ($q) => $q->where('is_published', true)->orderBy('difficulty'),
            ])->get(),
            'best' => $user->bestScores(),
            'playing' => $user->attempts()->where('status', 'playing')->pluck('id', 'scenario_id'),
        ]);
    }

    public function show(Scenario $scenario)
    {
        abort_unless($scenario->is_published, 404);
        $user = auth()->user();

        return view('student.scenarios.show', [
            'scenario' => $scenario->load('category'),
            'best' => $user->attempts()->where('scenario_id', $scenario->id)
                ->where('status', 'finished')->max('score'),
            'playing' => $user->attempts()->where('scenario_id', $scenario->id)
                ->where('status', 'playing')->latest('id')->first(),
        ]);
    }

    public function start(Scenario $scenario)
    {
        abort_unless($scenario->is_published, 404);
        $attempt = $this->game->start(auth()->user(), $scenario);

        return redirect()->route('student.play', $attempt);
    }

    public function restart(Scenario $scenario)
    {
        auth()->user()->attempts()
            ->where('scenario_id', $scenario->id)
            ->where('status', 'playing')
            ->delete();

        return $this->start($scenario);
    }

    public function play(Attempt $attempt)
    {
        $this->authorizeAttempt($attempt);

        if ($attempt->status === 'finished') {
            return redirect()->route('student.result', $attempt);
        }

        $attempt->load(['scenario.category', 'currentNode.choices']);

        return view('student.scenarios.play', [
            'attempt' => $attempt,
            'stepNo' => $attempt->steps()->count() + 1,
        ]);
    }

    public function choose(Attempt $attempt, ScenarioChoice $choice)
    {
        $this->authorizeAttempt($attempt);

        // Tombol "kembali" atau kirim ulang form: arahkan saja ke adegan sekarang.
        if ($attempt->status !== 'playing' || $choice->node_id !== $attempt->current_node_id) {
            return redirect()->route('student.play', $attempt);
        }

        $attempt = $this->game->choose($attempt, $choice);

        if ($attempt->status === 'finished') {
            return redirect()->route('student.result', $attempt);
        }

        return redirect()->route('student.play', $attempt)->with('feedback', [
            'text' => $choice->feedback,
            'points' => $choice->points,
        ]);
    }

    public function result(Attempt $attempt)
    {
        $this->authorizeAttempt($attempt);
        abort_unless($attempt->status === 'finished', 404);

        $attempt->load([
            'scenario.category', 'scenario.redFlags', 'currentNode',
            'steps.node', 'steps.choice',
        ]);

        $doneIds = auth()->user()->attempts()->where('status', 'finished')->pluck('scenario_id');

        return view('student.scenarios.result', [
            'attempt' => $attempt,
            'nextScenario' => Scenario::where('is_published', true)
                ->whereNotIn('id', $doneIds)->orderBy('difficulty')->first(),
        ]);
    }

    /** Siswa hanya boleh membuka permainan miliknya sendiri. */
    private function authorizeAttempt(Attempt $attempt): void
    {
        abort_unless($attempt->user_id === auth()->id(), 403);
    }
}
