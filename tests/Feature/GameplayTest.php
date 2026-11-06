<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Badge;
use App\Models\User;
use App\Services\GameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameplayTest extends TestCase
{
    use RefreshDatabase;

    private function play(User $user, array $m, array $path): Attempt
    {
        $attempt = app(GameService::class)->start($user, $m['scenario']);
        foreach ($path as $choice) {
            $attempt = app(GameService::class)->choose($attempt, $m[$choice]);
        }

        return $attempt;
    }

    public function test_jalur_terbaik_menghasilkan_skor_100_akhir_baik_dan_xp(): void
    {
        $m = $this->makeScenario(xp: 100);
        $user = User::factory()->create();

        $attempt = $this->play($user, $m, ['a', 'c']);

        $this->assertSame('finished', $attempt->status);
        $this->assertSame(100, $attempt->score);
        $this->assertSame('good', $attempt->ending_kind);
        $this->assertSame(110, $attempt->xp_gained, '100 XP misi + 10 bonus pertama kali');
        $this->assertSame(110, $user->fresh()->xp);
    }

    public function test_skor_negatif_dipotong_ke_nol_dan_akhir_buruk(): void
    {
        $m = $this->makeScenario();
        $user = User::factory()->create();

        $attempt = $this->play($user, $m, ['b', 'd']);

        $this->assertSame(0, $attempt->score);
        $this->assertSame('bad', $attempt->ending_kind);
        $this->assertSame(10, $attempt->xp_gained, 'hanya bonus pertama kali');
    }

    public function test_xp_hanya_bertambah_jika_skor_terbaik_meningkat(): void
    {
        $m = $this->makeScenario(xp: 100);
        $user = User::factory()->create();

        $this->play($user, $m, ['a', 'd']);         // skor 40 -> 40 XP + 10 bonus
        $this->assertSame(50, $user->fresh()->xp);

        $second = $this->play($user->fresh(), $m, ['b', 'd']);  // skor lebih buruk -> 0 XP baru
        $this->assertSame(0, $second->xp_gained);
        $this->assertSame(50, $user->fresh()->xp);

        $third = $this->play($user->fresh(), $m, ['a', 'c']);   // skor 100 -> selisih 60 XP, tanpa bonus
        $this->assertSame(60, $third->xp_gained);
        $this->assertSame(110, $user->fresh()->xp);
    }

    public function test_pilihan_dari_adegan_lain_ditolak(): void
    {
        $m = $this->makeScenario();
        $user = User::factory()->create();
        $attempt = app(GameService::class)->start($user, $m['scenario']);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(GameService::class)->choose($attempt, $m['c']); // C ada di adegan "mid", bukan "start"
    }

    public function test_misi_yang_sudah_selesai_tidak_bisa_dimainkan_lagi_tanpa_ulang(): void
    {
        $m = $this->makeScenario();
        $user = User::factory()->create();
        $attempt = $this->play($user, $m, ['a', 'c']);

        // Kirim ulang form setelah selesai hanya mengalihkan; skor dan XP tidak berubah.
        $this->actingAs($user)->post("/siswa/main/{$attempt->id}/pilih/{$m['a']->id}")->assertRedirect();
        $this->assertSame(100, $attempt->fresh()->score);
        $this->assertSame(110, $user->fresh()->xp);
        $this->assertSame(2, $attempt->fresh()->steps()->count());
    }

    public function test_alur_http_mulai_pilih_dan_hasil(): void
    {
        $m = $this->makeScenario();
        $user = User::factory()->create();

        $this->actingAs($user)->post("/siswa/skenario/{$m['scenario']->slug}/mulai")->assertRedirect();
        $attempt = Attempt::firstOrFail();

        $this->actingAs($user)->get("/siswa/main/{$attempt->id}")->assertOk();
        $this->actingAs($user)->post("/siswa/main/{$attempt->id}/pilih/{$m['a']->id}")->assertRedirect();
        $this->actingAs($user)->post("/siswa/main/{$attempt->id}/pilih/{$m['c']->id}")->assertRedirect();
        $this->actingAs($user)->get("/siswa/hasil/{$attempt->id}")->assertOk();
    }

    public function test_siswa_tidak_bisa_membuka_atau_memainkan_percobaan_milik_orang_lain(): void
    {
        $m = $this->makeScenario();
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $attempt = app(GameService::class)->start($owner, $m['scenario']);

        $this->actingAs($intruder)->get("/siswa/main/{$attempt->id}")->assertForbidden();
        $this->actingAs($intruder)->post("/siswa/main/{$attempt->id}/pilih/{$m['a']->id}")->assertForbidden();
        $this->actingAs($intruder)->get("/siswa/hasil/{$attempt->id}")->assertForbidden();
    }

    public function test_misi_yang_belum_terbit_tidak_bisa_dibuka(): void
    {
        $m = $this->makeScenario();
        $m['scenario']->update(['is_published' => false]);

        $this->actingAs(User::factory()->create())->get("/siswa/skenario/{$m['scenario']->slug}")->assertNotFound();
    }

    public function test_lencana_diberikan_sekali_saat_syarat_terpenuhi(): void
    {
        $m = $this->makeScenario();
        $first = Badge::create(['slug' => 'misi-pertama', 'name' => 'Misi Pertama', 'description' => 'x', 'icon' => '🎯', 'type' => 'scenarios', 'threshold' => 1]);
        $perfect = Badge::create(['slug' => 'sempurna', 'name' => 'Sempurna', 'description' => 'x', 'icon' => '💯', 'type' => 'perfect', 'threshold' => 1]);
        $goodEnd = Badge::create(['slug' => 'akhir-baik', 'name' => 'Akhir Baik', 'description' => 'x', 'icon' => '🧠', 'type' => 'good_end', 'threshold' => 2]);
        $xp = Badge::create(['slug' => 'xp-100', 'name' => 'XP 100', 'description' => 'x', 'icon' => '🥈', 'type' => 'xp', 'threshold' => 100]);

        $user = User::factory()->create();
        $this->play($user, $m, ['a', 'c']);

        $owned = $user->fresh()->badges()->pluck('badges.slug')->all();
        $this->assertContains('misi-pertama', $owned);
        $this->assertContains('sempurna', $owned);
        $this->assertContains('xp-100', $owned);
        $this->assertNotContains('akhir-baik', $owned, 'baru satu akhir baik, butuh dua');

        $this->play($user->fresh(), $m, ['a', 'c']);
        $user = $user->fresh();
        $this->assertContains('akhir-baik', $user->badges()->pluck('badges.slug')->all());
        $this->assertSame(1, $user->badges()->where('badges.id', $first->id)->count(), 'lencana tidak ganda');
    }

    public function test_lencana_belum_diberikan_jika_syarat_belum_terpenuhi(): void
    {
        $m = $this->makeScenario();
        Badge::create(['slug' => 'sempurna', 'name' => 'Sempurna', 'description' => 'x', 'icon' => '💯', 'type' => 'perfect', 'threshold' => 1]);

        $user = User::factory()->create();
        $this->play($user, $m, ['b', 'd']);

        $this->assertSame(0, $user->fresh()->badges()->count());
    }
}
