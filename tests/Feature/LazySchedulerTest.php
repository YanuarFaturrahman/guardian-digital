<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LazySchedulerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disk palsu: tes tidak menyentuh backup asli di storage/app/private/backups.
        Storage::fake('local');
    }

    public function test_nonaktif_secara_bawaan_tidak_membuat_backup(): void
    {
        Setting::put('backup_auto', true);
        $this->travelTo(now()->setTime(3, 30));

        $this->get('/')->assertOk();

        $this->assertCount(0, app(BackupService::class)->list());
    }

    public function test_membuat_backup_otomatis_sekali_per_hari_setelah_jam_2(): void
    {
        // VACUUM (SQLite) tidak bisa di dalam transaksi tes, jadi pembuat backup di-mock.
        $this->mock(BackupService::class, function ($m) {
            $m->shouldReceive('create')->once()->with('auto')->andReturn('backup-20261105-033000-auto.sql.gz');
            $m->shouldReceive('verify')->once()->andReturn(['ok' => true, 'message' => 'Arsip utuh.']);
            $m->shouldReceive('pruneAuto')->once()->andReturn(0);
        });

        config(['security.lazy_scheduler' => true]);
        Setting::put('backup_auto', true);
        $this->travelTo(now()->setTime(3, 30));

        $this->get('/')->assertOk();

        // permintaan berikutnya (walau jendela 10 menit sudah lewat) tidak membuat backup kedua pada hari yang sama
        Cache::forget('lazy-scheduler:tick');
        $this->get('/')->assertOk();

        $this->assertDatabaseHas('activity_logs', ['action' => 'system.backup_created']);
    }

    public function test_tidak_membackup_sebelum_jam_2_atau_jika_backup_otomatis_mati(): void
    {
        config(['security.lazy_scheduler' => true]);
        Setting::put('backup_auto', true);
        $this->travelTo(now()->setTime(1, 30));
        $this->get('/')->assertOk();
        $this->assertCount(0, app(BackupService::class)->list());

        Cache::flush();
        Setting::put('backup_auto', false);
        $this->travelTo(now()->setTime(4, 0));
        $this->get('/')->assertOk();
        $this->assertCount(0, app(BackupService::class)->list());
    }

    public function test_membersihkan_log_lama_setelah_jam_3(): void
    {
        config(['security.lazy_scheduler' => true]);
        $this->travelTo(now()->setTime(3, 30));
        ActivityLog::forceCreate(['action' => 'test.old', 'description' => 'lama', 'created_at' => now()->subDays(400)]);
        ActivityLog::forceCreate(['action' => 'test.new', 'description' => 'baru', 'created_at' => now()->subDay()]);

        $this->get('/')->assertOk();

        $this->assertDatabaseMissing('activity_logs', ['action' => 'test.old']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'test.new']);
    }
}
