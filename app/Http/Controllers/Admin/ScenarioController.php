<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Scenario;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ScenarioController extends Controller
{
    public function index()
    {
        $scenarios = Scenario::with('category')
            ->withCount(['attempts as plays' => fn ($q) => $q->where('status', 'finished')])
            ->withAvg(['attempts as avg_score' => fn ($q) => $q->where('status', 'finished')], 'score')
            ->get();

        return view('admin.scenarios.index', compact('scenarios'));
    }

    public function create()
    {
        return view('admin.scenarios.create', [
            'scenario' => new Scenario(['channel' => 'WhatsApp', 'difficulty' => 1, 'xp_reward' => 100]),
            'categories' => Category::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $scenario = Scenario::create(Arr::except($data, ['flags']) + [
            'slug' => $this->uniqueSlug($data['title']),
            'is_published' => false, // terbitkan setelah adegan lengkap
        ]);
        $this->syncFlags($scenario, $data['flags'] ?? []);

        return redirect()->route('admin.scenarios.edit', $scenario)
            ->with('status', 'Skenario dibuat. Sekarang tambahkan adegan, lalu pilihannya.');
    }

    public function edit(Scenario $scenario)
    {
        $scenario->load(['redFlags', 'nodes.choices']);

        return view('admin.scenarios.edit', [
            'scenario' => $scenario,
            'categories' => Category::orderBy('id')->get(),
            'problems' => $scenario->problems(),
            'plays' => $scenario->attempts()->where('status', 'finished')->count(),
        ]);
    }

    public function update(Request $request, Scenario $scenario)
    {
        $data = $request->validate($this->rules());

        // Slug sengaja tidak berubah supaya URL /siswa/skenario/{slug} tetap stabil
        $scenario->update(Arr::except($data, ['flags']));
        $this->syncFlags($scenario, $data['flags'] ?? []);

        return back()->with('status', 'Skenario diperbarui.');
    }

    public function destroy(Scenario $scenario)
    {
        if ($scenario->attempts()->exists()) {
            return back()->withErrors([
                'hapus' => 'Skenario ini sudah pernah dimainkan. Sembunyikan saja agar riwayat dan XP siswa tidak hilang.',
            ]);
        }

        $scenario->delete();

        return redirect()->route('admin.scenarios.index')->with('status', 'Skenario dihapus.');
    }

    public function toggle(Scenario $scenario)
    {
        if (! $scenario->is_published) {
            $problems = $scenario->problems();
            if ($problems) {
                return back()->withErrors([
                    'publikasi' => 'Belum bisa diterbitkan: ' . implode(' ', $problems),
                ]);
            }
        }

        $scenario->update(['is_published' => ! $scenario->is_published]);

        return back()->with('status', 'Status publikasi skenario diperbarui.');
    }

    private function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'synopsis' => ['required', 'string'],
            'channel' => ['required', 'string', 'max:40'],
            'difficulty' => ['required', 'integer', 'between:1,3'],
            'xp_reward' => ['required', 'integer', 'between:10,1000'],
            'flags' => ['nullable', 'array'],
            'flags.*.title' => ['nullable', 'string', 'max:255'],
            'flags.*.explanation' => ['nullable', 'string'],
        ];
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'skenario';
        $slug = $base;
        $i = 2;
        while (Scenario::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function syncFlags(Scenario $scenario, array $flags): void
    {
        $scenario->redFlags()->delete();

        $i = 0;
        foreach ($flags as $flag) {
            if (blank($flag['title'] ?? null)) {
                continue;
            }
            $scenario->redFlags()->create([
                'title' => $flag['title'],
                'explanation' => $flag['explanation'] ?? '',
                'position' => $i++,
            ]);
        }
    }
}