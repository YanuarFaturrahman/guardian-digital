<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Attempt;
use App\Models\User;
use App\Services\GameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_kebijakan_privasi_publik_dan_memuat_versi(): void
    {
        $this->get('/kebijakan-privasi')->assertOk()->assertSee(config('privacy.policy_version'));
    }

    public function test_pengguna_yang_belum_menyetujui_diarahkan_ke_halaman_persetujuan(): void
    {
        $user = User::factory()->create(['privacy_accepted_at' => null, 'privacy_policy_version' => null]);

        $this->actingAs($user)->get('/siswa')->assertRedirect(route('privacy.accept'));
        $this->actingAs($user)->get('/kebijakan-privasi')->assertOk();

        $this->actingAs($user)->post('/privasi/persetujuan', [])->assertSessionHasErrors('accept_privacy');
        $this->actingAs($user)->post('/privasi/persetujuan', ['accept_privacy' => '1'])->assertRedirect(route('dashboard'));

        $this->actingAs($user->fresh())->get('/siswa')->assertOk();
    }

    public function test_versi_kebijakan_baru_meminta_persetujuan_ulang(): void
    {
        $user = User::factory()->create(['privacy_policy_version' => '2020-01-01']);

        $this->actingAs($user)->get('/siswa')->assertRedirect(route('privacy.accept'));
    }

    public function test_siswa_lama_tanpa_tanggal_lahir_wajib_mengisinya(): void
    {
        $user = User::factory()->create(['birth_date' => null]);

        $this->actingAs($user)->get('/siswa')->assertRedirect(route('privacy.accept'));
        $this->actingAs($user)->post('/privasi/persetujuan', ['accept_privacy' => '1'])->assertSessionHasErrors('birth_date');
        $this->actingAs($user)->post('/privasi/persetujuan', ['accept_privacy' => '1', 'birth_date' => '2000-01-01'])->assertRedirect();
        $this->actingAs($user->fresh())->get('/siswa')->assertOk();
    }

    public function test_siswa_di_bawah_umur_tertahan_sampai_orang_tua_menyetujui(): void
    {
        $child = User::factory()->minor(14)->create();

        $this->actingAs($child)->get('/siswa')->assertRedirect(route('consent.pending'));
        $this->actingAs($child)->get('/siswa/skenario')->assertRedirect(route('consent.pending'));
        $this->actingAs($child)->get('/persetujuan-orang-tua')->assertOk()->assertSee($child->link_code ?? '');
        // Hak atas data tetap bisa diakses walau belum disetujui.
        $this->actingAs($child)->get('/akun/privasi')->assertOk();
        $this->actingAs($child)->get('/akun/data')->assertOk();
    }

    public function test_siswa_dewasa_tidak_butuh_persetujuan_orang_tua(): void
    {
        $adult = User::factory()->create(['birth_date' => now()->subYears(19)->toDateString()]);

        $this->actingAs($adult)->get('/siswa')->assertOk();
    }

    public function test_orang_tua_menautkan_anak_dengan_pernyataan_persetujuan(): void
    {
        $child = User::factory()->minor(14)->create(['link_code' => 'GD-ABC123']);
        $parent = User::factory()->parent()->create();

        // Tanpa mencentang pernyataan: ditolak, tidak tertaut, anak tetap tertahan.
        $this->actingAs($parent)->post('/orang-tua/hubungkan', ['code' => 'GD-ABC123'])->assertSessionHasErrors('consent');
        $this->assertSame(0, $parent->children()->count());
        $this->assertNull($child->fresh()->parental_consent_at);

        $this->actingAs($parent)->post('/orang-tua/hubungkan', ['code' => 'GD-ABC123', 'consent' => '1'])->assertSessionHasNoErrors();

        $child->refresh();
        $this->assertNotNull($child->parental_consent_at);
        $this->assertSame($parent->id, (int) $child->parental_consent_by);
        $this->assertSame(1, $parent->children()->count());
        $this->actingAs($child)->get('/siswa')->assertOk();
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.parental_consent_given']);
    }

    public function test_menautkan_siswa_dewasa_tidak_butuh_pernyataan(): void
    {
        $adult = User::factory()->create(['link_code' => 'GD-DEWASA']);
        $parent = User::factory()->parent()->create();

        $this->actingAs($parent)->post('/orang-tua/hubungkan', ['code' => 'GD-DEWASA'])->assertSessionHasNoErrors();
        $this->assertSame(1, $parent->children()->count());
    }

    public function test_hanya_wali_pemberi_persetujuan_yang_bisa_unduh_cabut_dan_hapus(): void
    {
        $child = User::factory()->minor(12)->create(['link_code' => 'GD-KIDS01']);
        $guardian = User::factory()->parent()->create(['password' => 'KataSandiWali9']);
        $other = User::factory()->parent()->create(['password' => 'KataSandiLain9']);

        $this->actingAs($guardian)->post('/orang-tua/hubungkan', ['code' => 'GD-KIDS01', 'consent' => '1']);
        $other->children()->attach($child->id); // tertaut, tetapi bukan pemberi persetujuan

        $this->actingAs($other)->get("/orang-tua/anak/{$child->id}/data")->assertForbidden();
        $this->actingAs($other)->post("/orang-tua/anak/{$child->id}/cabut-persetujuan")->assertForbidden();
        $this->actingAs($other)->delete("/orang-tua/anak/{$child->id}/hapus", ['password' => 'KataSandiLain9', 'confirm_text' => 'HAPUS'])->assertForbidden();

        $this->actingAs($guardian)->get("/orang-tua/anak/{$child->id}/data")->assertOk();

        // Cabut persetujuan: anak terkunci lagi.
        $this->actingAs($guardian)->post("/orang-tua/anak/{$child->id}/cabut-persetujuan")->assertRedirect();
        $this->assertNull($child->fresh()->parental_consent_at);
        $this->actingAs($child->fresh())->get('/siswa')->assertRedirect(route('consent.pending'));
    }

    public function test_wali_bisa_menghapus_akun_anak_dengan_kata_sandi_dan_konfirmasi(): void
    {
        $child = User::factory()->minor(12)->create(['link_code' => 'GD-KIDS02']);
        $guardian = User::factory()->parent()->create(['password' => 'KataSandiWali9']);
        $this->actingAs($guardian)->post('/orang-tua/hubungkan', ['code' => 'GD-KIDS02', 'consent' => '1']);

        $this->actingAs($guardian)->delete("/orang-tua/anak/{$child->id}/hapus", ['password' => 'salah', 'confirm_text' => 'HAPUS'])
            ->assertSessionHasErrors('password');
        $this->actingAs($guardian)->delete("/orang-tua/anak/{$child->id}/hapus", ['password' => 'KataSandiWali9', 'confirm_text' => 'hapus'])
            ->assertSessionHasErrors('confirm_text');
        $this->assertNotNull(User::find($child->id));

        $this->actingAs($guardian)->delete("/orang-tua/anak/{$child->id}/hapus", ['password' => 'KataSandiWali9', 'confirm_text' => 'HAPUS'])
            ->assertRedirect(route('family.dashboard'));
        $this->assertNull(User::find($child->id));
    }

    public function test_pengguna_mengunduh_hanya_datanya_sendiri(): void
    {
        $m = $this->makeScenario();
        $me = User::factory()->create(['name' => 'Rani', 'email' => 'rani@contoh.test']);
        $other = User::factory()->create(['name' => 'Bima', 'email' => 'bima@contoh.test']);
        $game = app(GameService::class);
        foreach ([$me, $other] as $u) {
            $a = $game->start($u, $m['scenario']);
            $a = $game->choose($a, $m['a']);
            $game->choose($a, $m['c']);
        }
        ActivityLog::record('auth.login', 'Rani masuk', $me, [], $me);

        $response = $this->actingAs($me)->get('/akun/data');
        $response->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));

        $json = json_decode($response->streamedContent(), true);
        $this->assertSame('rani@contoh.test', $json['profil']['email']);
        $this->assertCount(1, $json['riwayat_misi']);
        $this->assertSame('Misi Uji', $json['riwayat_misi'][0]['misi']);
        $this->assertCount(2, $json['riwayat_misi'][0]['keputusan']);
        $this->assertStringNotContainsString('bima@contoh.test', $response->streamedContent());
        $this->assertStringNotContainsString('"password"', $response->streamedContent());
        $this->assertStringNotContainsString('two_factor', $response->streamedContent());
    }

    public function test_hapus_akun_menghapus_data_dan_menganonimkan_catatan(): void
    {
        $m = $this->makeScenario();
        $me = User::factory()->create(['name' => 'Rani', 'email' => 'rani@contoh.test', 'password' => 'KataSandiRani9']);
        $other = User::factory()->create(['name' => 'Bima']);
        $game = app(GameService::class);
        $a = $game->start($me, $m['scenario']);
        $a = $game->choose($a, $m['a']);
        $game->choose($a, $m['c']);
        $game->start($other, $m['scenario']);
        ActivityLog::record('auth.login', 'Rani masuk', $me, [], $me);
        ActivityLog::record('auth.login_failed', 'Gagal masuk dengan email rani@contoh.test', null, ['email' => 'rani@contoh.test']);

        $this->actingAs($me)->delete('/akun', ['password' => 'salah', 'confirm_text' => 'HAPUS'])->assertSessionHasErrors('password');
        $this->actingAs($me)->delete('/akun', ['password' => 'KataSandiRani9', 'confirm_text' => 'HAPUS'])->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertNull(User::find($me->id));
        $this->assertSame(0, Attempt::where('user_id', $me->id)->count());
        $this->assertSame(1, Attempt::where('user_id', $other->id)->count(), 'data orang lain tidak tersentuh');
        $this->assertNotNull(User::find($other->id));
        $this->assertDatabaseMissing('activity_logs', ['description' => 'Rani masuk']);
        $this->assertSame(0, ActivityLog::where('description', 'like', '%rani@contoh.test%')->count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.self_deleted']);
    }

    public function test_hapus_akun_butuh_kata_ketik_hapus(): void
    {
        $me = User::factory()->create(['password' => 'KataSandiRani9']);

        $this->actingAs($me)->delete('/akun', ['password' => 'KataSandiRani9', 'confirm_text' => ''])->assertSessionHasErrors('confirm_text');
        $this->assertNotNull(User::find($me->id));
    }

    public function test_akun_admin_tidak_bisa_menghapus_diri_sendiri(): void
    {
        $admin = User::factory()->superadmin()->create(['password' => 'KataSandiAdmin99']);

        $this->actingAs($admin)->delete('/akun', ['password' => 'KataSandiAdmin99', 'confirm_text' => 'HAPUS'])->assertSessionHasErrors('password');
        $this->assertNotNull(User::find($admin->id));
    }
}
