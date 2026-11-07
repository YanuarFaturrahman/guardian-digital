<?php

namespace App\Console\Commands;

use App\Services\MaintenanceService;
use Illuminate\Console\Command;

class SystemMaintain extends Command
{
    protected $signature = 'system:maintain';

    protected $description = 'Bersihkan data sementara dan kedaluwarsa (sesi, token, cache, log, error, riwayat kesehatan)';

    public function handle(MaintenanceService $maintenance): int
    {
        $removed = $maintenance->purge();

        foreach ($removed as $key => $count) {
            $this->line(sprintf('  %-52s %d', MaintenanceService::LABELS[$key], $count));
        }

        $this->info('Selesai: ' . array_sum($removed) . ' baris dibuang.');

        return self::SUCCESS;
    }
}
