<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class RunBackup extends Command
{
    protected $signature = 'backup:run {--type=auto : "auto" atau "manual"}';

    protected $description = 'Buat cadangan database dan hapus cadangan otomatis yang melebihi batas simpan';

    public function handle(BackupService $backups): int
    {
        $type = $this->option('type') === 'manual' ? 'manual' : 'auto';

        try {
            $name = $backups->create($type);
        } catch (Throwable $e) {
            report($e);
            ActivityLog::record('system.backup_failed', "Backup {$type} gagal: " . Str::limit($e->getMessage(), 300));
            $this->error('Backup gagal: ' . $e->getMessage());

            return self::FAILURE;
        }

        ActivityLog::record('system.backup_created', "Backup {$type} dibuat: {$name}", null, ['file' => $name]);

        $removed = $type === 'auto' ? $backups->pruneAuto((int) Setting::read('backup_keep')) : 0;

        $this->info("Backup dibuat: {$name}" . ($removed ? " ({$removed} backup lama dihapus)" : ''));

        return self::SUCCESS;
    }
}
