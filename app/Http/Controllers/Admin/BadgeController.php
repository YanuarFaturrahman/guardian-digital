<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\Category;
use App\Models\User;
use App\Services\BadgeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BadgeController extends Controller
{
    public function index()
    {
        $badges = Badge::with('category')->withCount('users')->orderBy('id')->get();

        return view('admin.badges.index', compact('badges'));
    }

    public function create()
    {
        return view('admin.badges.create', [
            'badge' => new Badge(['type' => 'scenarios', 'threshold' => 1]),
            'categories' => Category::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        Badge::create($data);

        return redirect()->route('admin.badges.index')
            ->with('status', 'Lencana ditambahkan. Tekan "Periksa ulang siswa" agar siswa yang sudah memenuhi syarat langsung mendapatkannya.');
    }

    public function edit(Badge $badge)
    {
        return view('admin.badges.edit', [
            'badge' => $badge,
            'categories' => Category::orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, Badge $badge)
    {
        // Slug sengaja tidak berubah (dipakai BadgeSeeder sebagai kunci)
        $badge->update($this->validated($request));

        return redirect()->route('admin.badges.index')->with('status', 'Lencana diperbarui.');
    }

    public function destroy(Badge $badge)
    {
        $owners = $badge->users()->count();
        $badge->delete(); // baris badge_user ikut terhapus (cascade)

        return redirect()->route('admin.badges.index')->with(
            'status',
            $owners ? "Lencana dihapus dan dicabut dari {$owners} siswa." : 'Lencana dihapus.'
        );
    }

    /** Berikan lencana kepada siswa yang syaratnya sudah terpenuhi tetapi belum menerimanya. */
    public function regrant(BadgeService $badges)
    {
        $granted = 0;

        User::where('role', 'student')->each(function (User $student) use ($badges, &$granted) {
            $granted += $badges->evaluate($student, false)->count();
        });

        return back()->with('status', "Pemeriksaan selesai: {$granted} lencana baru diberikan.");
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'icon' => ['required', 'string', 'max:16'],
            'type' => ['required', Rule::in(array_keys(Badge::TYPES))],
            'threshold' => ['required', 'integer', 'between:1,1000000'],
            'category_id' => ['nullable', 'required_if:type,category', 'exists:categories,id'],
        ]);

        // Kategori hanya bermakna untuk jenis "category"
        if ($data['type'] !== 'category') {
            $data['category_id'] = null;
        }

        return $data;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'lencana';
        $slug = $base;
        $i = 2;
        while (Badge::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}