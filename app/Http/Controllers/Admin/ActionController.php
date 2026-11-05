<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SafetyAction;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class ActionController extends Controller
{
    public function index()
    {
        $actions = SafetyAction::withCount('users')
            ->orderBy('priority')->orderBy('position')->get();

        return view('admin.actions.index', [
            'actions' => $actions,
            'groups' => $actions->groupBy('priority'),
        ]);
    }

    public function create(Request $request)
    {
        // ?tingkat=1|2|3 dipakai oleh tombol "+ Tambah" di tiap tingkat
        $priority = (int) $request->query('tingkat', 3);
        if (! array_key_exists($priority, SafetyAction::PRIORITIES)) {
            $priority = 3;
        }

        return view('admin.actions.form', [
            'action' => new SafetyAction([
                'icon' => '✅',
                'priority' => $priority,
                'xp_reward' => SafetyAction::PRIORITIES[$priority]['xp'],
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $position = Arr::pull($data, 'position');

        $action = SafetyAction::createInOrder($data, $position);

        return redirect()->route('admin.actions.index')
            ->with('status', "Aksi \"{$action->title}\" ditambahkan.");
    }

    public function edit(SafetyAction $action)
    {
        return view('admin.actions.form', ['action' => $action]);
    }

    public function update(Request $request, SafetyAction $action)
    {
        $data = $this->validated($request, $action);
        $position = Arr::pull($data, 'position');

        $action->updateInOrder($data, $position);

        return redirect()->route('admin.actions.index')
            ->with('status', "Aksi \"{$action->title}\" diperbarui.");
    }

    public function destroy(SafetyAction $action)
    {
        $title = $action->title;
        $action->deleteInOrder(); // catatan "sudah dilakukan" siswa ikut terhapus, XP mereka tetap

        return redirect()->route('admin.actions.index')
            ->with('status', "Aksi \"{$title}\" dihapus.");
    }

    private function validated(Request $request, ?SafetyAction $action = null): array
    {
        $data = $request->validate([
            'icon' => ['required', 'string', 'max:8'],
            'title' => [
                'required', 'string', 'max:120',
                Rule::unique('safety_actions', 'title')->ignore($action?->id),
            ],
            'description' => ['required', 'string', 'max:400'],
            'priority' => ['required', Rule::in(array_keys(SafetyAction::PRIORITIES))],
            'xp_reward' => ['required', 'integer', 'between:0,200'],
            'position' => ['nullable', 'integer', 'between:1,250'],
        ], [
            'required' => ':attribute wajib diisi.',
            'max.string' => ':attribute terlalu panjang (maksimal :max karakter).',
            'between.numeric' => ':attribute harus antara :min dan :max.',
            'integer' => ':attribute harus berupa angka bulat.',
            'unique' => 'Sudah ada aksi dengan :attribute yang sama.',
            'in' => ':attribute tidak valid.',
        ], [
            'icon' => 'Ikon',
            'title' => 'Judul',
            'description' => 'Deskripsi',
            'priority' => 'Tingkat urgensi',
            'xp_reward' => 'XP',
            'position' => 'Urutan',
        ]);

        $data['priority'] = (int) $data['priority'];

        return $data;
    }
}