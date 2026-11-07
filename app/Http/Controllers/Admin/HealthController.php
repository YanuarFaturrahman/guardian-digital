<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HealthCheck;
use App\Services\HealthService;
use Illuminate\Http\Request;

/** Tab "Kesehatan": status langsung, riwayat pemeriksaan, dan uptime. */
class HealthController extends Controller
{
    public function index(HealthService $health)
    {
        $result = $health->run();

        return view('admin.system.health', [
            'result' => $result,
            'beat' => $health->lastSchedulerBeat(),
            'uptime24h' => HealthCheck::uptime(1),
            'uptime7d' => HealthCheck::uptime(7),
            'uptime30d' => HealthCheck::uptime(30),
            'history' => HealthCheck::latest('id')->limit(48)->get(),
            'avgDb' => (int) round((float) HealthCheck::where('created_at', '>=', now()->subDay())->whereNotNull('db_ms')->avg('db_ms')),
            'statusUrl' => route('status'),
        ]);
    }

    public function check(Request $request, HealthService $health)
    {
        $result = $health->record('manual');

        return back()->with('status', 'Pemeriksaan selesai: status sistem ' . [
            'ok' => 'SEHAT', 'degraded' => 'TERGANGGU', 'down' => 'TIDAK BISA DIAKSES',
        ][$result['status']] . '.');
    }
}
