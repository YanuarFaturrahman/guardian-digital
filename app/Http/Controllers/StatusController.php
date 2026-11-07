<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\HealthService;

/**
 * Endpoint publik /status untuk monitor uptime. Hanya mengembalikan status ringkas
 * (tanpa detail konfigurasi): 200 jika layanan sehat atau terganggu ringan, 503 jika
 * database tidak bisa dijangkau atau mode pemeliharaan aktif.
 */
class StatusController extends Controller
{
    public function show(HealthService $health)
    {
        $headers = ['Cache-Control' => 'no-store'];

        if (Setting::bool('maintenance_mode')) {
            return response()->json(['status' => 'maintenance', 'time' => now()->toIso8601String()], 503, $headers);
        }

        $result = $health->run(withAdvisory: false);

        return response()->json(
            ['status' => $result['status'], 'time' => now()->toIso8601String()],
            $result['status'] === 'down' ? 503 : 200,
            $headers
        );
    }
}
