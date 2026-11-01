<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\SafetyAction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RapikanUrutan extends Command
{
    protected $signature = 'urutan:rapikan';

    protected $description = 'Rapikan urutan materi (per kategori) dan aksi keamanan menjadi 1, 2, 3, ...';

    public function handle(): int
    {
        DB::transaction(function () {
            Lesson::normalizeOrder();
            SafetyAction::normalizeOrder();
        });

        $this->info('Urutan materi dan aksi sudah dirapikan.');

        return self::SUCCESS;
    }
}