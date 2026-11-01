<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Scenario;
use App\Models\ScenarioNode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NodeController extends Controller
{
    public function store(Request $request, Scenario $scenario)
    {
        $data = $this->validated($request, $scenario);

        if ($data['is_start']) {
            $scenario->nodes()->update(['is_start' => false]); // hanya boleh satu adegan awal
        }
        $scenario->nodes()->create($data);

        return back()->with('status', 'Adegan ditambahkan.');
    }

    public function update(Request $request, ScenarioNode $node)
    {
        $scenario = $node->scenario;
        $data = $this->validated($request, $scenario, $node);

        if ($data['is_start']) {
            $scenario->nodes()->where('id', '!=', $node->id)->update(['is_start' => false]);
        }
        $node->update($data);

        return back()->with('status', 'Adegan diperbarui.');
    }

    public function destroy(ScenarioNode $node)
    {
        // attempt_steps ikut terhapus (cascade) dan mengubah riwayat skor siswa
        if ($node->scenario->attempts()->exists()) {
            return back()->withErrors([
                'hapus' => 'Skenario ini sudah pernah dimainkan, adegannya tidak bisa dihapus. Sembunyikan skenario lalu buat versi baru.',
            ]);
        }

        $node->delete(); // pilihan milik adegan ini ikut terhapus

        return back()->with('status', 'Adegan dihapus. Periksa pilihan lain yang sebelumnya menuju adegan ini.');
    }

    private function validated(Request $request, Scenario $scenario, ?ScenarioNode $node = null): array
    {
        $data = $request->validate([
            'node_key' => ['required', 'alpha_dash', 'max:40',
                Rule::unique('scenario_nodes', 'node_key')
                    ->where('scenario_id', $scenario->id)->ignore($node?->id)],
            'kind' => ['required', 'in:story,good,neutral,bad'],
            'speaker' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'lesson' => ['nullable', 'string'],
        ]);
        $data['is_start'] = $request->boolean('is_start');

        if ($data['is_start'] && $data['kind'] !== 'story') {
            throw ValidationException::withMessages(['kind' => 'Adegan awal harus berjenis story.']);
        }

        return $data;
    }
}