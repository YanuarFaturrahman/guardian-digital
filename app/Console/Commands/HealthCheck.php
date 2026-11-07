<?php

namespace App\Console\Commands;

use App\Services\HealthService;
use Illuminate\Console\Command;

class HealthCheck extends Command
{
    protected $signature = 'system:health-check';

    protected $description = 'Periksa kesehatan aplikasi, simpan riwayatnya, dan kirim peringatan bila memburuk';

    public function handle(HealthService $health): int
    {
        $result = $health->record('scheduler');

        foreach ($result['checks'] as $c) {
            $mark = ['ok' => '<info>OK</info>   ', 'warn' => '<comment>WARN</comment> ', 'fail' => '<error>GAGAL</error>'][$c['status']];
            $this->line("  {$mark} {$c['label']}: {$c['detail']}");
        }

        $this->newLine();
        $this->line('Status: ' . $result['status']);

        return $result['status'] === 'down' ? self::FAILURE : self::SUCCESS;
    }
}
