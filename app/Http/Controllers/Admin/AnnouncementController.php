<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Tab "Pengumuman": banner pemberitahuan (jadwal pemeliharaan, gangguan, info penting). */
class AnnouncementController extends Controller
{
    public function index()
    {
        return view('admin.system.announcements.index', [
            'announcements' => Announcement::latest('id')->simplePaginate(15),
        ]);
    }

    public function create()
    {
        return view('admin.system.announcements.form', ['announcement' => new Announcement(['level' => 'info', 'audience' => 'all', 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        $announcement = Announcement::create($this->validated($request) + ['created_by' => $request->user()->id]);

        ActivityLog::record('system.announcement_created', "{$request->user()->name} membuat pengumuman: {$announcement->message}", null, ['id' => $announcement->id]);

        return redirect()->route('admin.system.announcements.index')->with('status', 'Pengumuman dibuat.');
    }

    public function edit(Announcement $announcement)
    {
        return view('admin.system.announcements.form', ['announcement' => $announcement]);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $announcement->update($this->validated($request));

        ActivityLog::record('system.announcement_updated', "{$request->user()->name} mengubah pengumuman #{$announcement->id}", null, ['id' => $announcement->id]);

        return redirect()->route('admin.system.announcements.index')->with('status', 'Pengumuman diperbarui.');
    }

    public function toggle(Request $request, Announcement $announcement)
    {
        $announcement->update(['is_active' => ! $announcement->is_active]);

        ActivityLog::record(
            'system.announcement_' . ($announcement->is_active ? 'enabled' : 'disabled'),
            "{$request->user()->name} " . ($announcement->is_active ? 'mengaktifkan' : 'menonaktifkan') . " pengumuman #{$announcement->id}",
            null,
            ['id' => $announcement->id]
        );

        return back()->with('status', $announcement->is_active ? 'Pengumuman diaktifkan.' : 'Pengumuman dinonaktifkan.');
    }

    public function destroy(Request $request, Announcement $announcement)
    {
        $announcement->delete();

        ActivityLog::record('system.announcement_deleted', "{$request->user()->name} menghapus pengumuman #{$announcement->id}", null, ['id' => $announcement->id]);

        return back()->with('status', 'Pengumuman dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:5', 'max:300'],
            'level' => ['required', Rule::in(array_keys(Announcement::LEVELS))],
            'audience' => ['required', Rule::in(array_keys(Announcement::AUDIENCES))],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ], [
            'message.required' => 'Isi pengumuman wajib diisi.',
            'message.min' => 'Isi pengumuman minimal 5 karakter.',
            'message.max' => 'Isi pengumuman maksimal 300 karakter.',
            'ends_at.after' => 'Waktu berakhir harus setelah waktu mulai.',
        ]);

        return [
            'message' => trim($data['message']),
            'level' => $data['level'],
            'audience' => $data['audience'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
