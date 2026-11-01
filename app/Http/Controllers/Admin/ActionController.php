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
        return view('admin.actions.index', [
            'actions' => SafetyAction::withCount('users')->orderBy('position')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.actions.form', [
            'action' => new SafetyAction(['icon' => '✅', 'xp_reward' => 25]),
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
        return $request->validate([
            'icon' => ['required', 'string', 'max:8'],
            'title' => [
                'required', 'string', 'max:120',
                Rule::unique('safety_actions', 'title')->ignore($action?->id),
            ],
            'description' => ['required', 'string', 'max:400'],
            'xp_reward' => ['required', 'integer', 'between:0,200'],
            'position' => ['nullable', 'integer', 'between:1,250'],
        ], [
            'required' => ':attribute wajib diisi.',
            'max.string' => ':attribute terlalu panjang (maksimal :max karakter).',
            'between.numeric' => ':attribute harus antara :min dan :max.',
            'integer' => ':attribute harus berupa angka bulat.',
            'unique' => 'Sudah ada aksi dengan :attribute yang sama.',
        ], [
            'icon' => 'Ikon',
            'title' => 'Judul',
            'description' => 'Deskripsi',
            'xp_reward' => 'XP',
            'position' => 'Urutan',
        ]);
    }
}