<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        foreach (['/dashboard', '/siswa', '/orang-tua', '/guru', '/admin-sekolah', '/admin', '/pengguna', '/akun/keamanan'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_halaman_publik_bisa_dibuka_tamu(): void
    {
        foreach (['/', '/masuk', '/daftar', '/lupa-kata-sandi', '/kebijakan-privasi'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    #[DataProvider('matrix')]
    public function test_matriks_hak_akses(string $role, string $url, int $expected): void
    {
        $user = match ($role) {
            'student' => User::factory()->create(),
            'parent' => User::factory()->parent()->create(),
            'teacher' => User::factory()->teacher()->create(),
            'school_admin' => User::factory()->schoolAdmin()->create(),
            'superadmin' => User::factory()->superadmin()->create(),
        };

        $this->actingAs($user)->get($url)->assertStatus($expected);
    }

    public static function matrix(): array
    {
        $rows = [];
        $allow = [
            'student' => ['/siswa'],
            'parent' => ['/orang-tua'],
            'teacher' => ['/guru'],
            'school_admin' => ['/admin-sekolah', '/pengguna'],
            'superadmin' => ['/admin', '/pengguna', '/admin/sistem', '/admin/skenario'],
        ];
        $all = ['/siswa', '/orang-tua', '/guru', '/admin-sekolah', '/admin', '/admin/sistem', '/admin/skenario', '/pengguna'];

        foreach ($allow as $role => $urls) {
            foreach ($all as $url) {
                $rows["$role -> $url"] = [$role, $url, in_array($url, $urls, true) ? 200 : 403];
            }
        }

        return $rows;
    }

    public function test_orang_tua_tidak_bisa_melihat_anak_yang_tidak_tertaut(): void
    {
        $parent = User::factory()->parent()->create();
        $stranger = User::factory()->create();

        $this->actingAs($parent)->get("/orang-tua/anak/{$stranger->id}")->assertForbidden();

        $parent->children()->attach($stranger->id);
        $this->actingAs($parent)->get("/orang-tua/anak/{$stranger->id}")->assertOk();
    }

    public function test_akun_nonaktif_langsung_dikeluarkan(): void
    {
        $user = User::factory()->deactivated()->create();

        $this->actingAs($user)->get('/siswa')->assertRedirect(route('login'));
    }

    public function test_kata_sandi_sementara_memaksa_ganti_sebelum_halaman_lain(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)->get('/siswa')->assertRedirect(route('password.force'));
        $this->actingAs($user)->get('/ganti-kata-sandi')->assertOk();
    }
}
