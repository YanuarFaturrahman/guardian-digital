<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $children = auth()->user()->children()->get()->map(fn ($child) => [
            'child' => $child,
            'summary' => $child->progressSummary(),
            'level' => $child->levelData(),
        ]);

        return view('family.dashboard', ['rows' => $children]);
    }

    public function link(Request $request)
    {
        $data = $request->validate(
            ['code' => ['required', 'string', 'max:12']],
            ['code.required' => 'Kode tautan wajib diisi.']
        );

        $student = User::where('role', 'student')
            ->where('link_code', strtoupper(trim($data['code'])))
            ->first();

        if (! $student) {
            return back()->withErrors(['code' => 'Kode tautan tidak ditemukan. Minta anakmu menunjukkannya di berandanya.']);
        }

        auth()->user()->children()->syncWithoutDetaching([$student->id]);

        return back()->with('status', "Akun {$student->name} berhasil ditautkan.");
    }

    public function show(User $child)
    {
        $this->authorizeChild($child);

        $report = $child->report();
        $weakest = $report['mastery']->sortBy('mastery')->first();

        return view('family.child', $report + ['weakest' => $weakest]);
    }

    public function unlink(User $child)
    {
        $this->authorizeChild($child);
        auth()->user()->children()->detach($child->id);

        return redirect()->route('family.dashboard')->with('status', 'Tautan akun dilepas.');
    }

    private function authorizeChild(User $child): void
    {
        abort_unless(auth()->user()->children()->where('users.id', $child->id)->exists(), 403);
    }
}
