<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class BackupController extends Controller
{
    public function __construct(private BackupService $backups)
    {
    }

    public function index()
    {
        return view('admin.system.backups', [
            'backups' => $this->backups->list(),
            'auto' => Setting::bool('backup_auto'),
            'keep' => (int) Setting::read('backup_keep'),
            'driver' => config('database.default'),
        ]);
    }

    public function store(Request $request)
    {
        @set_time_limit(300);

        try {
            $name = $this->backups->create('manual');
        } catch (Throwable $e) {
            report($e);
            ActivityLog::record('system.backup_failed', 'Backup manual gagal: ' . Str::limit($e->getMessage(), 300));

            return back()->withErrors(['backup' => 'Backup gagal: ' . $e->getMessage()]);
        }

        ActivityLog::record('system.backup_created', "{$request->user()->name} membuat backup manual: {$name}", null, ['file' => $name]);

        return back()->with('status', "Backup dibuat: {$name}");
    }

    public function download(Request $request, string $file)
    {
        abort_unless($this->backups->exists($file), 404);

        ActivityLog::record('system.backup_downloaded', "{$request->user()->name} mengunduh backup {$file}", null, ['file' => $file]);

        return response()->download($this->backups->path($file), $file);
    }

    public function destroy(Request $request, string $file)
    {
        abort_unless($this->backups->exists($file), 404);

        $this->backups->delete($file);

        ActivityLog::record('system.backup_deleted', "{$request->user()->name} menghapus backup {$file}", null, ['file' => $file]);

        return back()->with('status', 'Backup dihapus.');
    }
}
