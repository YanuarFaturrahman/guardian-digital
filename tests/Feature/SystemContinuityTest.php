<?php

namespace Tests\Feature;

use App\Mail\SystemAlertMail;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\ErrorLog;
use App\Models\HealthCheck;
use App\Models\Setting;
use App\Models\User;
use App\Services\BackupService;
use App\Services\HealthService;
use App\Services\MaintenanceService;
use App\Support\ErrorReporter;
use App\Support\SystemAlert;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

/** Fitur keberlangsungan di menu Sistem: kesehatan, error, pemeliharaan, pengumuman, verifikasi backup, peringatan. */
class SystemContinuityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->superadmin()->create();
    }

    // ---------------------------------------------------------------- akses

    public static function adminPages(): array
    {
        return [
            ['/admin/sistem/kesehatan'],
            ['/admin/sistem/error'],
            ['/admin/sistem/pemeliharaan'],
            ['/admin/sistem/pengumuman'],
            ['/admin/sistem/pengumuman/baru'],
        ];
    }

    #[DataProvider('adminPages')]
    public function test_tamu_dan_non_superadmin_tidak_bisa_membuka_halaman_sistem_baru(string $url): void
    {
        $this->get($url)->assertRedirect(route('login'));

        foreach ([User::factory()->create(), User::factory()->teacher()->create(), User::factory()->schoolAdmin()->create()] as $user) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
    }

    #[DataProvider('adminPages')]
    public function test_superadmin_bisa_membuka_halaman_sistem_baru_dan_tab_tampil(string $url): void
    {
        $this->actingAs($this->admin())->get($url)->assertOk()
            ->assertSee('Kesehatan')->assertSee('Error Aplikasi')->assertSee('Pengumuman')->assertSee('Pemeliharaan');
    }

    public function test_aksi_tulis_juga_dibatasi_untuk_superadmin(): void
    {
        $teacher = User::factory()->teacher()->create();
        $error = ErrorLog::create(['fingerprint' => sha1('a'), 'class' => 'X', 'message' => 'm']);

        $this->actingAs($teacher)->post('/admin/sistem/kesehatan/periksa')->assertForbidden();
        $this->actingAs($teacher)->post('/admin/sistem/pemeliharaan/bersihkan')->assertForbidden();
        $this->actingAs($teacher)->post("/admin/sistem/error/{$error->id}/selesai")->assertForbidden();
        $this->actingAs($teacher)->post('/admin/sistem/pengumuman', ['message' => 'Halo semuanya', 'level' => 'info', 'audience' => 'all'])->assertForbidden();
        $this->assertNull($error->fresh()->resolved_at);
        $this->assertSame(0, Announcement::count());
    }

    public function test_ringkasan_sistem_menampilkan_kartu_baru(): void
    {
        HealthCheck::create(['status' => 'ok']);

        $this->actingAs($this->admin())->get('/admin/sistem')->assertOk()
            ->assertSee('Status sistem terakhir')->assertSee('Uptime 7 hari')->assertSee('Error belum selesai')->assertSee('Pengumuman tampil');
    }

    // ------------------------------------------------------------ kesehatan

    public function test_pemeriksaan_kesehatan_normal_tidak_down(): void
    {
        $result = app(HealthService::class)->run();
        $byKey = collect($result['checks'])->keyBy('key');

        $this->assertSame('ok', $byKey['database']['status']);
        $this->assertSame('ok', $byKey['cache']['status']);
        $this->assertNotSame('down', $result['status']);
        $this->assertNotNull($result['db_ms']);
        // Butir saran tidak ikut menentukan status keseluruhan.
        $this->assertFalse($byKey['backup']['critical']);
        $this->assertTrue($byKey['database']['critical']);
    }

    public function test_ambang_ruang_disk_berdasarkan_ruang_bebas_absolut(): void
    {
        $gb = 1024 ** 3;
        $mb = 1024 ** 2;

        $this->assertSame('ok', HealthService::classifyDisk(10 * $gb), 'disk besar yang tinggal beberapa persen tetap aman');
        $this->assertSame('ok', HealthService::classifyDisk(1 * $gb));
        $this->assertSame('warn', HealthService::classifyDisk(900 * $mb));
        $this->assertSame('warn', HealthService::classifyDisk(200 * $mb));
        $this->assertSame('fail', HealthService::classifyDisk(199 * $mb));
        $this->assertSame('fail', HealthService::classifyDisk(0));
    }

    public function test_database_putus_membuat_status_down(): void
    {
        config(['database.connections.rusak' => ['driver' => 'sqlite', 'database' => '/folder/tidak/ada/x.sqlite', 'prefix' => '', 'foreign_key_constraints' => false]]);
        $original = DB::getDefaultConnection();
        DB::setDefaultConnection('rusak');

        try {
            $result = app(HealthService::class)->run(false);
            $this->assertSame('down', $result['status']);
            $this->assertSame('fail', collect($result['checks'])->firstWhere('key', 'database')['status']);

            // Endpoint publik juga melapor 503, tanpa membocorkan detail error.
            $this->getJson('/status')->assertStatus(503)->assertJson(['status' => 'down'])->assertJsonMissing(['x.sqlite']);
        } finally {
            DB::setDefaultConnection($original);
        }
    }

    public function test_endpoint_status_publik_ringan(): void
    {
        $response = $this->getJson('/status')->assertOk()->assertJson(['status' => 'ok'])->assertJsonStructure(['status', 'time']);

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        // Tidak membuka detail konfigurasi dan tidak membuat sesi/cookie (monitor memanggilnya terus-menerus).
        $this->assertSame(['status', 'time'], array_keys($response->json()));
        $response->assertHeaderMissing('Set-Cookie');
    }

    public function test_endpoint_status_melapor_pemeliharaan_dengan_503(): void
    {
        Setting::put('maintenance_mode', true);

        $this->getJson('/status')->assertStatus(503)->assertJson(['status' => 'maintenance']);
    }

    public function test_periksa_sekarang_mencatat_riwayat_manual(): void
    {
        $this->actingAs($this->admin())->post('/admin/sistem/kesehatan/periksa')
            ->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('health_checks', ['source' => 'manual']);
    }

    public function test_perintah_kesehatan_mencatat_denyut_penjadwal(): void
    {
        $this->assertNull(app(HealthService::class)->lastSchedulerBeat());

        $this->artisan('system:health-check')->assertExitCode(0);

        $this->assertDatabaseHas('health_checks', ['source' => 'scheduler']);
        $this->assertNotNull(app(HealthService::class)->lastSchedulerBeat());
    }

    public function test_pemeriksaan_manual_bukan_denyut_penjadwal(): void
    {
        app(HealthService::class)->record('manual');

        $this->assertNull(app(HealthService::class)->lastSchedulerBeat());
    }

    public function test_uptime_dihitung_dari_riwayat(): void
    {
        $this->assertNull(HealthCheck::uptime(7));

        foreach (['ok', 'ok', 'degraded', 'down'] as $status) {
            HealthCheck::create(['status' => $status]);
        }
        HealthCheck::create(['status' => 'down', 'created_at' => now()->subDays(20)]);

        $this->assertSame(75.0, HealthCheck::uptime(7));   // 3 dari 4 tidak "down"
        $this->assertSame(60.0, HealthCheck::uptime(30));  // 3 dari 5
    }

    public function test_halaman_kesehatan_menampilkan_riwayat_dan_alamat_status(): void
    {
        HealthCheck::create(['status' => 'degraded', 'db_ms' => 12, 'issues' => ['Ruang disk: menipis']]);

        $this->actingAs($this->admin())->get('/admin/sistem/kesehatan')->assertOk()
            ->assertSee('Hasil pemeriksaan')->assertSee(route('status'), false)->assertSee('Ruang disk: menipis');
    }

    // ------------------------------------------------------------- peringatan

    private function enableAlerts(): User
    {
        // Email dianggap terkirim hanya jika mailer bukan log/array dan host bukan lokal.
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.contoh.com']);
        Setting::put('alerts_enabled', true);
        Mail::fake();
        Cache::flush();

        return User::factory()->superadmin()->create(['email' => 'root@contoh.com']);
    }

    public function test_peringatan_terkirim_sekali_per_masa_jeda(): void
    {
        $this->enableAlerts();

        $this->assertTrue(SystemAlert::send('uji', 'Judul', 'Isi'));
        $this->assertFalse(SystemAlert::send('uji', 'Judul', 'Isi'), 'jenis yang sama ditahan selama masa jeda');
        $this->assertTrue(SystemAlert::send('lain', 'Judul lain', 'Isi'), 'jenis berbeda tetap terkirim');

        Mail::assertSent(SystemAlertMail::class, 2);
        Mail::assertSent(SystemAlertMail::class, fn ($m) => $m->hasTo('root@contoh.com') && $m->alertSubject === 'Judul');
    }

    public function test_peringatan_tidak_dikirim_jika_dimatikan_atau_email_tidak_terkirim(): void
    {
        $this->enableAlerts();
        Setting::put('alerts_enabled', false);
        $this->assertFalse(SystemAlert::send('a', 'J', 'I'));

        Setting::put('alerts_enabled', true);
        config(['mail.default' => 'log']);
        $this->assertFalse(SystemAlert::send('b', 'J', 'I'));

        Mail::assertNothingSent();
    }

    public function test_peringatan_tidak_dikirim_ke_superadmin_nonaktif(): void
    {
        $this->enableAlerts();
        User::where('email', 'root@contoh.com')->update(['deactivated_at' => now()]);

        $this->assertFalse(SystemAlert::send('c', 'J', 'I'));
        Mail::assertNothingSent();
    }

    public function test_peringatan_dikirim_saat_status_baru_memburuk_bukan_saat_masih_buruk(): void
    {
        $this->enableAlerts();

        $svc = \Mockery::mock(HealthService::class)->makePartial();
        $svc->shouldReceive('run')->andReturn([
            'status' => 'down', 'db_ms' => null, 'disk_free_pct' => null,
            'checks' => [['key' => 'database', 'label' => 'Database', 'status' => 'fail', 'detail' => 'putus', 'critical' => true]],
        ]);

        $svc->record('scheduler'); // sebelumnya belum ada riwayat -> kirim
        Cache::flush();
        $svc->record('scheduler'); // sebelumnya sudah down -> jangan kirim lagi

        Mail::assertSent(SystemAlertMail::class, 1);
        Mail::assertSent(SystemAlertMail::class, fn ($m) => str_contains($m->alertBody, 'Database: putus'));
    }

    // ---------------------------------------------------------------- error

    public function test_error_dicatat_dan_dikelompokkan_per_sidik_jari(): void
    {
        $make = fn () => new RuntimeException('Gagal memproses');

        ErrorReporter::capture($make());
        ErrorReporter::capture($make());
        ErrorReporter::capture(new RuntimeException('Beda lokasi'));

        $this->assertSame(2, ErrorLog::count());
        $grouped = ErrorLog::where('message', 'Gagal memproses')->first();
        $this->assertSame(2, $grouped->occurrences);
        $this->assertSame(RuntimeException::class, $grouped->class);
        $this->assertStringContainsString('SystemContinuityTest.php', $grouped->file);
        $this->assertStringNotContainsString(base_path(), $grouped->file, 'path absolut tidak disimpan');
        $this->assertNotEmpty($grouped->trace);
    }

    public function test_error_yang_kambuh_setelah_selesai_dibuka_kembali(): void
    {
        $make = fn () => new RuntimeException('Kambuh');
        ErrorReporter::capture($make());
        ErrorLog::query()->update(['resolved_at' => now()]);

        ErrorReporter::capture($make());

        $this->assertNull(ErrorLog::first()->resolved_at);
        $this->assertSame(2, ErrorLog::first()->occurrences);
    }

    public function test_data_pribadi_disamarkan_di_catatan_error(): void
    {
        ErrorReporter::capture(new RuntimeException('Tidak bisa kirim ke budi.santoso@contoh.co.id sekarang'));
        $this->assertSame('Tidak bisa kirim ke [email] sekarang', ErrorLog::first()->message);

        // Nilai parameter SQL (mis. email di klausa WHERE) tidak ikut tersimpan.
        $sql = new QueryException('sqlite', 'select * from users where email = ?', ['rahasia@contoh.com'], new \PDOException('no such table: users'));
        $this->assertStringNotContainsString('rahasia@contoh.com', ErrorReporter::cleanMessage($sql));

        $this->assertSame('/atur-ulang-kata-sandi/*', ErrorReporter::maskPath('/atur-ulang-kata-sandi/abcdefghijklmnopqrstuvwx1234?email=a@b.c'));
        $this->assertSame('/siswa/skenario', ErrorReporter::maskPath('/siswa/skenario?x=1'));
    }

    public function test_pesan_error_dipotong_dan_pesan_kosong_diberi_label(): void
    {
        $this->assertLessThanOrEqual(500, strlen(ErrorReporter::cleanMessage(new RuntimeException(str_repeat('x', 3000)))));
        $this->assertSame('(tanpa pesan)', ErrorReporter::cleanMessage(new RuntimeException('')));
    }

    public function test_error_nyata_di_http_tercatat_tetapi_404_tidak(): void
    {
        Route::get('/_uji-error', fn () => throw new RuntimeException('Meledak di route uji'));

        $this->get('/_uji-error')->assertStatus(500);
        $this->get('/halaman-yang-tidak-ada')->assertNotFound();

        $this->assertSame(1, ErrorLog::count());
        $row = ErrorLog::first();
        $this->assertSame('Meledak di route uji', $row->message);
        $this->assertSame('GET', $row->method);
        $this->assertSame('/_uji-error', $row->url);
    }

    public function test_kegagalan_menyimpan_error_tidak_menimbulkan_error_baru(): void
    {
        Schema::drop('error_logs');

        ErrorReporter::capture(new RuntimeException('tabel tidak ada'));

        $this->addToAssertionCount(1); // sampai di sini tanpa melempar apa pun
    }

    public function test_admin_mengelola_catatan_error(): void
    {
        $admin = $this->admin();
        $open = ErrorLog::create(['fingerprint' => sha1('1'), 'class' => 'App\\Foo', 'message' => 'Masalah terbuka', 'file' => 'app/Foo.php', 'line' => 10]);
        $done = ErrorLog::create(['fingerprint' => sha1('2'), 'class' => 'App\\Bar', 'message' => 'Masalah selesai', 'resolved_at' => now()]);

        $this->actingAs($admin)->get('/admin/sistem/error')->assertOk()->assertSee('Masalah terbuka')->assertDontSee('Masalah selesai');
        $this->actingAs($admin)->get('/admin/sistem/error?status=resolved')->assertOk()->assertSee('Masalah selesai')->assertDontSee('Masalah terbuka');
        $this->actingAs($admin)->get('/admin/sistem/error?status=all&q=Foo')->assertOk()->assertSee('Masalah terbuka')->assertDontSee('Masalah selesai');

        $this->actingAs($admin)->post("/admin/sistem/error/{$open->id}/selesai")->assertRedirect();
        $this->assertNotNull($open->fresh()->resolved_at);
        $this->actingAs($admin)->post("/admin/sistem/error/{$open->id}/selesai")->assertRedirect();
        $this->assertNull($open->fresh()->resolved_at, 'tombol yang sama membuka kembali');

        $this->actingAs($admin)->post('/admin/sistem/error/selesaikan-semua')->assertRedirect();
        $this->assertSame(0, ErrorLog::open()->count());

        $this->actingAs($admin)->delete('/admin/sistem/error/bersihkan-selesai')->assertRedirect();
        $this->assertSame(0, ErrorLog::count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'system.errors_cleared']);
    }

    public function test_hapus_satu_catatan_error(): void
    {
        $e = ErrorLog::create(['fingerprint' => sha1('x'), 'class' => 'X', 'message' => 'm']);

        $this->actingAs($this->admin())->delete("/admin/sistem/error/{$e->id}")->assertRedirect();

        $this->assertModelMissing($e);
    }

    public function test_pesan_error_di_halaman_admin_dilepas_dari_html(): void
    {
        ErrorLog::create(['fingerprint' => sha1('xss'), 'class' => 'X', 'message' => '<script>alert(1)</script>']);

        $this->actingAs($this->admin())->get('/admin/sistem/error')->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }

    // ----------------------------------------------------------- pemeliharaan

    public function test_pembersihan_membuang_data_kedaluwarsa_dan_menyisakan_yang_aktif(): void
    {
        config(['session.driver' => 'database', 'session.lifetime' => 120]);
        $row = fn ($id, $ts) => ['id' => $id, 'payload' => '', 'last_activity' => $ts];
        DB::table('sessions')->insert([$row('lama', now()->subHours(5)->timestamp), $row('baru', now()->subMinutes(5)->timestamp)]);

        DB::table('password_reset_tokens')->insert([
            ['email' => 'lama@x.com', 'token' => 't', 'created_at' => now()->subHours(3)],
            ['email' => 'baru@x.com', 'token' => 't', 'created_at' => now()->subMinutes(5)],
        ]);

        ActivityLog::forceCreate(['action' => 'test.old', 'description' => 'lama', 'created_at' => now()->subDays(400)]);
        ActivityLog::forceCreate(['action' => 'test.new', 'description' => 'baru', 'created_at' => now()->subDay()]);

        ErrorLog::create(['fingerprint' => sha1('o'), 'class' => 'X', 'message' => 'lama', 'last_seen_at' => now()->subDays(40)]);
        ErrorLog::create(['fingerprint' => sha1('n'), 'class' => 'X', 'message' => 'baru', 'last_seen_at' => now()->subDay()]);

        HealthCheck::create(['status' => 'ok', 'created_at' => now()->subDays(45)]);
        HealthCheck::create(['status' => 'ok']);

        $service = app(MaintenanceService::class);
        $pending = $service->pending();
        $this->assertSame(1, $pending['sessions']);
        $this->assertSame(1, $pending['password_resets']);
        $this->assertSame(1, $pending['activity_logs']);
        $this->assertSame(1, $pending['error_logs']);
        $this->assertSame(1, $pending['health_checks']);

        $removed = $service->purge();

        $this->assertSame($pending, $removed);
        $this->assertSame(['baru'], DB::table('sessions')->pluck('id')->all());
        $this->assertSame(['baru@x.com'], DB::table('password_reset_tokens')->pluck('email')->all());
        $this->assertDatabaseMissing('activity_logs', ['action' => 'test.old']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'test.new']);
        $this->assertSame(['baru'], ErrorLog::pluck('message')->all());
        $this->assertSame(1, HealthCheck::count());
        $this->assertSame(0, array_sum($service->pending()), 'setelah dibersihkan tidak ada lagi yang tertunda');
    }

    public function test_cache_database_yang_kedaluwarsa_dibersihkan(): void
    {
        Schema::hasTable('cache') || $this->markTestSkipped('tabel cache tidak ada');
        config(['cache.default' => 'database']);
        DB::table('cache')->insert([
            ['key' => 'lama', 'value' => 's', 'expiration' => now()->subHour()->timestamp],
            ['key' => 'baru', 'value' => 's', 'expiration' => now()->addHour()->timestamp],
        ]);

        $this->assertSame(1, app(MaintenanceService::class)->pending()['cache']);
        app(MaintenanceService::class)->purge();

        $this->assertSame(['baru'], DB::table('cache')->pluck('key')->all());
    }

    public function test_batas_simpan_dari_pengaturan_dipakai(): void
    {
        Setting::put('error_retention_days', 3);
        ErrorLog::create(['fingerprint' => sha1('5'), 'class' => 'X', 'message' => 'lima hari', 'last_seen_at' => now()->subDays(5)]);
        ErrorLog::create(['fingerprint' => sha1('1'), 'class' => 'X', 'message' => 'satu hari', 'last_seen_at' => now()->subDay()]);

        app(MaintenanceService::class)->purge();

        $this->assertSame(['satu hari'], ErrorLog::pluck('message')->all());
    }

    public function test_perintah_pemeliharaan_berjalan(): void
    {
        ErrorLog::create(['fingerprint' => sha1('o'), 'class' => 'X', 'message' => 'lama', 'last_seen_at' => now()->subDays(90)]);

        $this->artisan('system:maintain')->assertExitCode(0);

        $this->assertSame(0, ErrorLog::count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'system.maintenance_run']);
    }

    public function test_halaman_dan_tombol_pemeliharaan(): void
    {
        $admin = $this->admin();
        ErrorLog::create(['fingerprint' => sha1('o'), 'class' => 'X', 'message' => 'lama', 'last_seen_at' => now()->subDays(90)]);

        $this->actingAs($admin)->get('/admin/sistem/pemeliharaan')->assertOk()
            ->assertSee('Catatan error melewati batas simpan')->assertSee('Ukuran database')->assertSee('users');

        $this->actingAs($admin)->post('/admin/sistem/pemeliharaan/bersihkan')->assertRedirect()->assertSessionHas('status');
        $this->assertSame(0, ErrorLog::count());

        $this->actingAs($admin)->post('/admin/sistem/pemeliharaan/cache-pengaturan')->assertRedirect()->assertSessionHas('status');
        $this->actingAs($admin)->post('/admin/sistem/pemeliharaan/cache-tampilan')->assertRedirect()->assertSessionHas('status');
        $this->actingAs($admin)->post('/admin/sistem/pemeliharaan/kosongkan-cache')->assertRedirect()->assertSessionHas('status');

        foreach (['system.views_cleared', 'system.settings_cache_refreshed', 'system.cache_flushed'] as $action) {
            $this->assertDatabaseHas('activity_logs', ['action' => $action]);
        }
    }

    public function test_ukuran_tabel_tersedia(): void
    {
        $users = collect(app(MaintenanceService::class)->tableSizes(50))->firstWhere('name', 'users');

        $this->assertNotNull($users);

        // MySQL melaporkan ukuran data+indeks; SQLite hanya jumlah baris.
        in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)
            ? $this->assertGreaterThan(0, $users['bytes'])
            : $this->assertNull($users['bytes']);
    }

    // ------------------------------------------------------------ pengumuman

    private function announce(array $attrs = []): Announcement
    {
        return Announcement::create($attrs + ['message' => 'Pemeliharaan malam ini', 'level' => 'info', 'audience' => 'all', 'is_active' => true]);
    }

    public function test_admin_membuat_mengubah_menonaktifkan_dan_menghapus_pengumuman(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/sistem/pengumuman', [
            'message' => 'Pemeliharaan Minggu pukul 01.00', 'level' => 'warning', 'audience' => 'student', 'is_active' => '1',
        ])->assertRedirect(route('admin.system.announcements.index'));

        $a = Announcement::firstOrFail();
        $this->assertSame('warning', $a->level);
        $this->assertSame($admin->id, $a->created_by);

        $this->actingAs($admin)->get("/admin/sistem/pengumuman/{$a->id}/ubah")->assertOk()->assertSee('Pemeliharaan Minggu pukul 01.00');
        $this->actingAs($admin)->put("/admin/sistem/pengumuman/{$a->id}", [
            'message' => 'Diundur ke Senin pukul 01.00', 'level' => 'danger', 'audience' => 'all', 'is_active' => '1',
        ])->assertRedirect();
        $this->assertSame('Diundur ke Senin pukul 01.00', $a->fresh()->message);

        $this->actingAs($admin)->post("/admin/sistem/pengumuman/{$a->id}/aktif")->assertRedirect();
        $this->assertFalse($a->fresh()->is_active);

        $this->actingAs($admin)->delete("/admin/sistem/pengumuman/{$a->id}")->assertRedirect();
        $this->assertSame(0, Announcement::count());

        foreach (['created', 'updated', 'disabled', 'deleted'] as $event) {
            $this->assertDatabaseHas('activity_logs', ['action' => "system.announcement_{$event}"]);
        }
    }

    public function test_validasi_pengumuman(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/sistem/pengumuman', ['message' => '', 'level' => 'info', 'audience' => 'all'])->assertSessionHasErrors('message');
        $this->actingAs($admin)->post('/admin/sistem/pengumuman', ['message' => str_repeat('a', 301), 'level' => 'info', 'audience' => 'all'])->assertSessionHasErrors('message');
        $this->actingAs($admin)->post('/admin/sistem/pengumuman', ['message' => 'Halo semuanya', 'level' => 'biru', 'audience' => 'all'])->assertSessionHasErrors('level');
        $this->actingAs($admin)->post('/admin/sistem/pengumuman', ['message' => 'Halo semuanya', 'level' => 'info', 'audience' => 'alien'])->assertSessionHasErrors('audience');
        $this->actingAs($admin)->post('/admin/sistem/pengumuman', [
            'message' => 'Halo semuanya', 'level' => 'info', 'audience' => 'all',
            'starts_at' => '2026-11-10T10:00', 'ends_at' => '2026-11-10T09:00',
        ])->assertSessionHasErrors('ends_at');

        $this->assertSame(0, Announcement::count());
    }

    public function test_jendela_waktu_dan_status_aktif(): void
    {
        $this->announce(['message' => 'Tampil']);
        $this->announce(['message' => 'Nonaktif', 'is_active' => false]);
        $this->announce(['message' => 'Belum mulai', 'starts_at' => now()->addHour()]);
        $this->announce(['message' => 'Sudah berakhir', 'ends_at' => now()->subHour()]);
        $this->announce(['message' => 'Sedang berlangsung', 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);

        $this->assertEqualsCanonicalizing(['Tampil', 'Sedang berlangsung'], Announcement::live()->pluck('message')->all());
    }

    public function test_pengumuman_tampil_sesuai_peran_dan_tamu(): void
    {
        $this->announce(['message' => 'Untuk semua']);
        $this->announce(['message' => 'Khusus siswa', 'audience' => 'student']);
        $this->announce(['message' => 'Khusus guru', 'audience' => 'teacher']);

        $this->get('/')->assertSee('Untuk semua')->assertDontSee('Khusus siswa')->assertDontSee('Khusus guru');
        $this->actingAs(User::factory()->create())->get('/siswa')->assertSee('Untuk semua')->assertSee('Khusus siswa')->assertDontSee('Khusus guru');
        $this->actingAs(User::factory()->teacher()->create())->get('/guru')->assertSee('Untuk semua')->assertSee('Khusus guru')->assertDontSee('Khusus siswa');
    }

    public function test_pengumuman_baru_langsung_tampil_walau_halaman_sudah_di_cache(): void
    {
        $this->get('/')->assertDontSee('Info mendadak');

        $a = $this->announce(['message' => 'Info mendadak']);
        $this->get('/')->assertSee('Info mendadak');

        $a->update(['is_active' => false]);
        $this->get('/')->assertDontSee('Info mendadak');
    }

    public function test_pengumuman_darurat_tidak_bisa_ditutup_dan_isi_dilepas_dari_html(): void
    {
        $this->announce(['message' => 'Gangguan <b>besar</b>', 'level' => 'danger']);
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Gangguan &lt;b&gt;besar&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('Gangguan <b>besar</b>', $html);
        $this->assertStringContainsString('data-dismissible="0"', $html);
        $this->assertStringNotContainsString('data-dismiss ', $html, 'tidak ada tombol tutup untuk tingkat darurat');

        Announcement::query()->delete();
        $this->announce(['message' => 'Info biasa']);
        $this->assertStringContainsString('data-dismiss', $this->get('/')->getContent());
    }

    public function test_pengumuman_darurat_diurutkan_paling_atas(): void
    {
        $this->announce(['message' => 'AAA info']);
        $this->announce(['message' => 'BBB darurat', 'level' => 'danger']);

        $this->assertSame(['BBB darurat', 'AAA info'], array_column(Announcement::visibleFor(null), 'message'));
    }

    // ----------------------------------------------------- verifikasi backup

    private function putBackup(string $name, string $content): string
    {
        Storage::fake('local');
        Storage::disk('local')->put('backups/' . $name, $content);

        return $name;
    }

    private function sqlDump(): string
    {
        return "-- Guardian Digital: cadangan database\n-- Dibuat: 2026-11-07 01:00:00 (Asia/Jakarta)\n\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n"
            . "-- Tabel users\nDROP TABLE IF EXISTS `users`;\nCREATE TABLE `users` (`id` int);\n\n"
            . "-- Tabel settings\nDROP TABLE IF EXISTS `settings`;\nCREATE TABLE `settings` (`key` int);\n\nSET FOREIGN_KEY_CHECKS=1;\n";
    }

    public function test_backup_sql_yang_utuh_lolos_verifikasi(): void
    {
        $name = $this->putBackup('backup-20261107-010101-manual.sql.gz', gzencode($this->sqlDump(), 9));

        $result = app(BackupService::class)->verify($name);

        $this->assertTrue($result['ok'], $result['message']);
        $this->assertStringContainsString('2 tabel', $result['message']);
    }

    public function test_backup_besar_dengan_banyak_potongan_tetap_terhitung_benar(): void
    {
        $dump = "-- Guardian Digital: cadangan database\n\n";
        for ($i = 0; $i < 3000; $i++) {
            $dump .= "-- Tabel t{$i}\nDROP TABLE IF EXISTS `t{$i}`;\nCREATE TABLE `t{$i}` (`id` int);\nINSERT INTO `t{$i}` VALUES (" . str_repeat('9', 200) . ");\n\n";
        }
        $dump .= "SET FOREIGN_KEY_CHECKS=1;\n";
        $name = $this->putBackup('backup-20261107-010102-auto.sql.gz', gzencode($dump, 1));

        $result = app(BackupService::class)->verify($name);

        $this->assertTrue($result['ok'], $result['message']);
        $this->assertStringContainsString('3000 tabel', $result['message']);
    }

    public function test_backup_terpotong_rusak_atau_bukan_backup_gagal_verifikasi(): void
    {
        $gz = gzencode($this->sqlDump(), 9);
        $svc = app(BackupService::class);

        $name = $this->putBackup('backup-20261107-010103-manual.sql.gz', substr($gz, 0, -12));
        $this->assertFalse($svc->verify($name)['ok']);
        $this->assertStringContainsString('terpotong', $svc->verify($name)['message']);

        $name = $this->putBackup('backup-20261107-010104-manual.sql.gz', random_bytes(300));
        $this->assertFalse($svc->verify($name)['ok']);

        $name = $this->putBackup('backup-20261107-010105-manual.sql.gz', gzencode('halo dunia', 9));
        $this->assertStringContainsString('bukan backup Guardian Digital', $svc->verify($name)['message']);

        $name = $this->putBackup('backup-20261107-010106-manual.sql.gz', gzencode("-- Guardian Digital: cadangan database\nCREATE TABLE x", 9));
        $this->assertStringContainsString('tidak selesai', $svc->verify($name)['message']);

        $this->assertFalse($svc->verify('backup-20261107-999999-manual.sql.gz')['ok'], 'berkas tidak ada');
        $this->assertFalse($svc->verify('../../.env')['ok'], 'nama tidak valid ditolak');
    }

    public function test_verifikasi_backup_sqlite(): void
    {
        $svc = app(BackupService::class);

        $ok = $this->putBackup('backup-20261107-010107-manual.sqlite.gz', gzencode("SQLite format 3\0" . str_repeat("\0", 100)));
        $this->assertTrue($svc->verify($ok)['ok']);

        $bad = $this->putBackup('backup-20261107-010108-manual.sqlite.gz', gzencode('bukan sqlite'));
        $this->assertFalse($svc->verify($bad)['ok']);
    }

    public function test_tombol_verifikasi_backup_di_halaman_admin(): void
    {
        $admin = $this->admin();
        $name = $this->putBackup('backup-20261107-010109-manual.sql.gz', gzencode($this->sqlDump(), 9));

        $this->actingAs($admin)->get('/admin/sistem/backup')->assertOk()->assertSee('Verifikasi');

        $this->actingAs($admin)->post("/admin/sistem/backup/{$name}/verifikasi")
            ->assertRedirect()->assertSessionHas('status');
        $this->assertDatabaseHas('activity_logs', ['action' => 'system.backup_verified']);

        $bad = $this->putBackup('backup-20261107-010110-manual.sql.gz', 'rusak');
        $this->actingAs($admin)->post("/admin/sistem/backup/{$bad}/verifikasi")
            ->assertRedirect()->assertSessionHasErrors('backup');

        $this->actingAs($admin)->post('/admin/sistem/backup/backup-20261107-999999-manual.sql.gz/verifikasi')->assertNotFound();
        $this->actingAs(User::factory()->teacher()->create())->post("/admin/sistem/backup/{$name}/verifikasi")->assertForbidden();
    }

    // ------------------------------------------------- pengaturan & penjadwal

    public function test_pengaturan_pemantauan_tersimpan(): void
    {
        $admin = $this->admin();
        $base = ['max_upload_kb' => 2048, 'backup_keep' => 7, 'log_retention_days' => 180];

        $this->actingAs($admin)->get('/admin/sistem/pengaturan')->assertOk()->assertSee('Pemantauan')->assertSee('peringatan email');

        $this->actingAs($admin)->put('/admin/sistem/pengaturan', $base + [
            'alerts_enabled' => '1', 'error_retention_days' => 14, 'health_retention_days' => 60,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertTrue(Setting::bool('alerts_enabled'));
        $this->assertSame(14, Setting::read('error_retention_days'));
        $this->assertSame(60, Setting::read('health_retention_days'));

        // Formulir lama (tanpa kolom baru) tidak menimpa nilai yang sudah ada.
        $this->actingAs($admin)->put('/admin/sistem/pengaturan', $base)->assertSessionHasNoErrors();
        $this->assertSame(14, Setting::read('error_retention_days'));
        $this->assertFalse(Setting::bool('alerts_enabled'), 'kotak centang tidak dikirim = dimatikan');

        $this->actingAs($admin)->put('/admin/sistem/pengaturan', $base + ['error_retention_days' => 0])->assertSessionHasErrors('error_retention_days');
        $this->actingAs($admin)->put('/admin/sistem/pengaturan', $base + ['health_retention_days' => 999])->assertSessionHasErrors('health_retention_days');
    }

    public function test_tugas_baru_terdaftar_di_penjadwal(): void
    {
        Artisan::call('schedule:list');
        $out = Artisan::output();

        $this->assertStringContainsString('system:health-check', $out);
        $this->assertStringContainsString('system:maintain', $out);
    }

    public function test_penjadwal_malas_menjalankan_pemeriksaan_kesehatan_dan_pembersihan(): void
    {
        config(['security.lazy_scheduler' => true]);
        $this->travelTo(now()->setTime(4, 30));
        ErrorLog::create(['fingerprint' => sha1('o'), 'class' => 'X', 'message' => 'lama', 'last_seen_at' => now()->subDays(90)]);

        $this->get('/')->assertOk();

        $this->assertDatabaseHas('health_checks', ['source' => 'scheduler']);
        $this->assertSame(0, ErrorLog::count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'system.maintenance_run']);
    }

    public function test_migrasi_keberlangsungan_aman_dijalankan_ulang(): void
    {
        $migration = require database_path('migrations/2026_11_07_100000_create_continuity_tables.php');

        $migration->up();
        $migration->up();

        foreach (['health_checks', 'error_logs', 'announcements'] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }
    }
}
