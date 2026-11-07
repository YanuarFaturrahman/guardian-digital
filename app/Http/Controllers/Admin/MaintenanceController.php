<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\BackupService;
use App\Services\MaintenanceService;
use Illuminate\Http\Request;
use Throwable;

/** Tab "Pemeliharaan": pembersihan data kedaluwarsa, cache, dan ukuran database. */
class MaintenanceController extends Controller
{
    public function __construct(private MaintenanceService $maintenance)
    {
    }

    public function index()
    {
        $pending = $this->maintenance->pending();

        return view('admin.system.maintenance', [
            'pending' => $pending,
            'pendingTotal' => array_sum($pending),
            'labels' => MaintenanceService::LABELS,
            'tables' => $this->maintenance->tableSizes(),
            'dbSize' => $this->maintenance->databaseSize(),
            'cacheDriver' => config('cache.default'),
            'sessionDriver' => config('session.driver'),
        ]);
    }

    public function purge(Request $request)
    {
        $removed = $this->maintenance->purge();
        $total = array_sum($removed);

        return back()->with('status', $total
            ? "Pembersihan selesai: {$total} baris data kedaluwarsa dibuang."
            : 'Tidak ada data kedaluwarsa yang perlu dibersihkan.');
    }

    public function clearViews(Request $request)
    {
        try {
            $this->maintenance->clearViews();
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['maintenance' => 'Cache tampilan gagal dibersihkan: ' . $e->getMessage()]);
        }

        ActivityLog::record('system.views_cleared', "{$request->user()->name} membersihkan cache tampilan");

        return back()->with('status', 'Cache tampilan dibersihkan. Halaman dikompilasi ulang saat dibuka berikutnya.');
    }

    public function refreshSettings(Request $request)
    {
        $this->maintenance->refreshSettingsCache();

        ActivityLog::record('system.settings_cache_refreshed', "{$request->user()->name} memuat ulang cache pengaturan");

        return back()->with('status', 'Cache pengaturan dan pengumuman dimuat ulang.');
    }

    public function flushCache(Request $request)
    {
        try {
            $this->maintenance->flushCache();
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['maintenance' => 'Cache gagal dikosongkan: ' . $e->getMessage()]);
        }

        ActivityLog::record('system.cache_flushed', "{$request->user()->name} mengosongkan seluruh cache aplikasi");

        return back()->with('status', 'Seluruh cache aplikasi dikosongkan.');
    }
}
