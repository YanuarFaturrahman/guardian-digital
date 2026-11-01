<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class LessonController extends Controller
{
    public function index()
    {
        return view('admin.lessons.index', [
            'groups' => Lesson::with('category')->withCount('readers')
                ->orderBy('category_id')->orderBy('position')->get()
                ->groupBy('category_id'),
            'categories' => Category::orderBy('name')->get()->keyBy('id'),
            'total' => Lesson::count(),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.lessons.form', [
            'lesson' => new Lesson([
                'category_id' => $request->query('category'),
                'read_minutes' => 3,
                'xp_reward' => 20,
            ]),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $position = Arr::pull($data, 'position');
        $data['slug'] = $this->uniqueSlug($data['title']);

        $lesson = Lesson::createInOrder($data, $position);

        return redirect()->route('admin.lessons.index')
            ->with('status', "Materi \"{$lesson->title}\" ditambahkan.");
    }

    public function edit(Lesson $lesson)
    {
        return view('admin.lessons.form', [
            'lesson' => $lesson,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Lesson $lesson)
    {
        $data = $this->validated($request);
        $position = Arr::pull($data, 'position');

        // Slug sengaja tidak diubah supaya alamat materi tetap stabil.
        $lesson->updateInOrder($data, $position);

        return redirect()->route('admin.lessons.index')
            ->with('status', "Materi \"{$lesson->title}\" diperbarui.");
    }

    public function destroy(Lesson $lesson)
    {
        $title = $lesson->title;
        $lesson->deleteInOrder(); // catatan "sudah dibaca" siswa ikut terhapus, XP mereka tetap

        return redirect()->route('admin.lessons.index')
            ->with('status', "Materi \"{$title}\" dihapus.");
    }

    /** Ubah Markdown menjadi HTML untuk pratinjau di formulir. */
    public function preview(Request $request)
    {
        $request->validate(['body' => ['nullable', 'string', 'max:20000']]);

        return response(Str::markdown((string) $request->input('body', ''), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:120'],
            'summary' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:20000'],
            'read_minutes' => ['required', 'integer', 'between:1,60'],
            'xp_reward' => ['required', 'integer', 'between:0,200'],
            'position' => ['nullable', 'integer', 'between:1,250'],
        ], [
            'required' => ':attribute wajib diisi.',
            'max.string' => ':attribute terlalu panjang (maksimal :max karakter).',
            'between.numeric' => ':attribute harus antara :min dan :max.',
            'integer' => ':attribute harus berupa angka bulat.',
            'exists' => ':attribute tidak valid.',
        ], [
            'category_id' => 'Kategori',
            'title' => 'Judul',
            'summary' => 'Ringkasan',
            'body' => 'Isi materi',
            'read_minutes' => 'Lama baca',
            'xp_reward' => 'XP',
            'position' => 'Urutan',
        ]);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'materi';
        $slug = $base;
        $i = 2;

        while (Lesson::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}