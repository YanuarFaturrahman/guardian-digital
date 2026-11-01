<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Scenario;
use App\Models\ScenarioChoice;
use App\Models\ScenarioNode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChoiceController extends Controller
{
    public function store(Request $request, ScenarioNode $node)
    {
        abort_if($node->isEnding(), 422, 'Adegan akhir tidak boleh punya pilihan.');

        $data = $this->validated($request, $node->scenario);
        $data['position'] = (ScenarioChoice::where('node_id', $node->id)->max('position') ?? -1) + 1;
        $node->choices()->create($data);

        return back()->with('status', 'Pilihan ditambahkan.');
    }

    public function update(Request $request, ScenarioChoice $choice)
    {
        $choice->update($this->validated($request, $choice->node->scenario));

        return back()->with('status', 'Pilihan diperbarui.');
    }

    public function destroy(ScenarioChoice $choice)
    {
        if ($choice->node->scenario->attempts()->exists()) {
            return back()->withErrors([
                'hapus' => 'Skenario ini sudah pernah dimainkan, pilihannya tidak bisa dihapus. Ubah isinya saja, atau buat versi baru.',
            ]);
        }

        $choice->delete();

        return back()->with('status', 'Pilihan dihapus.');
    }

    private function validated(Request $request, Scenario $scenario): array
    {
        return $request->validate([
            'label' => ['required', 'string'],
            // tujuan wajib adegan milik skenario yang sama
            'next_node_id' => ['required', Rule::exists('scenario_nodes', 'id')->where('scenario_id', $scenario->id)],
            'points' => ['required', 'integer', 'between:-50,50'],
            'feedback' => ['nullable', 'string'],
        ]);
    }
}