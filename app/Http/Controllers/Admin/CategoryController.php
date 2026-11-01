<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount(['scenarios', 'lessons'])->orderBy('id')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create', [
            'category' => new Category(['color' => '#22d3ee']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['slug'] = $this->uniqueSlug($data['name']);

        Category::create($data);

        return redirect()->route('admin.categories.index')->with('status', 'Kategori ditambahkan.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        // Slug sengaja tidak ikut diubah (dipakai seeder sebagai kunci)
        $category->update($request->validate($this->rules()));

        return redirect()->route('admin.categories.index')->with('status', 'Kategori diperbarui.');
    }

    public function destroy(Category $category)
    {
        $scenarios = $category->scenarios()->count();
        $lessons = $category->lessons()->count();
        $badges = $category->badges()->count();

        if ($scenarios + $lessons + $badges > 0) {
            return back()->withErrors([
                'hapus' => "Kategori ini masih dipakai oleh {$scenarios} skenario, {$lessons} materi, dan {$badges} lencana. "
                    . 'Pindahkan atau hapus dulu isinya, karena menghapus kategori akan ikut menghapus skenario dan materi miliknya.',
            ]);
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('status', 'Kategori dihapus.');
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['required', 'string', 'max:16'],
            // Wajib #rrggbb: nilainya dipakai langsung di atribut style pada view siswa
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'tagline' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'family_tip' => ['nullable', 'string'],
        ];
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'kategori';
        $slug = $base;
        $i = 2;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}