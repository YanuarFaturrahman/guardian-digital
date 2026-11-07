<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ErrorLog;
use Illuminate\Http\Request;

/** Tab "Error Aplikasi": error yang tertangkap, dikelompokkan, bisa ditandai selesai. */
class ErrorLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:open,resolved,all'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $status = $filters['status'] ?? 'open';

        $entries = ErrorLog::query()
            ->when($status === 'open', fn ($q) => $q->whereNull('resolved_at'))
            ->when($status === 'resolved', fn ($q) => $q->whereNotNull('resolved_at'))
            ->when($filters['q'] ?? null, fn ($q, $s) => $q->where(
                fn ($w) => $w->where('message', 'like', "%{$s}%")
                    ->orWhere('class', 'like', "%{$s}%")
                    ->orWhere('file', 'like', "%{$s}%")
                    ->orWhere('url', 'like', "%{$s}%")
            ))
            ->orderByDesc('last_seen_at')
            ->simplePaginate(20)
            ->withQueryString();

        return view('admin.system.errors', [
            'entries' => $entries,
            'status' => $status,
            'q' => $filters['q'] ?? '',
            'openCount' => ErrorLog::open()->count(),
            'resolvedCount' => ErrorLog::whereNotNull('resolved_at')->count(),
            'last24h' => ErrorLog::where('last_seen_at', '>=', now()->subDay())->count(),
        ]);
    }

    public function resolve(Request $request, ErrorLog $error)
    {
        $error->update(['resolved_at' => $error->isResolved() ? null : now()]);

        ActivityLog::record(
            'system.error_' . ($error->isResolved() ? 'resolved' : 'reopened'),
            "{$request->user()->name} " . ($error->isResolved() ? 'menandai selesai' : 'membuka kembali') . " error {$error->class}",
            null,
            ['error_id' => $error->id]
        );

        return back()->with('status', $error->isResolved() ? 'Error ditandai selesai.' : 'Error dibuka kembali.');
    }

    public function destroy(Request $request, ErrorLog $error)
    {
        $error->delete();

        return back()->with('status', 'Catatan error dihapus.');
    }

    public function resolveAll(Request $request)
    {
        $count = ErrorLog::open()->update(['resolved_at' => now()]);

        ActivityLog::record('system.errors_resolved_all', "{$request->user()->name} menandai {$count} error sebagai selesai");

        return back()->with('status', "{$count} error ditandai selesai.");
    }

    public function clearResolved(Request $request)
    {
        $count = ErrorLog::whereNotNull('resolved_at')->delete();

        ActivityLog::record('system.errors_cleared', "{$request->user()->name} menghapus {$count} catatan error yang sudah selesai");

        return back()->with('status', "{$count} catatan error yang sudah selesai dihapus.");
    }
}
