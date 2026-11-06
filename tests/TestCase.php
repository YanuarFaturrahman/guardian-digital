<?php

namespace Tests;

use App\Models\Category;
use App\Models\Scenario;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tes tidak membutuhkan hasil build Vite (public/build/manifest.json).
        $this->withoutVite();
    }

    /** Anggap email benar-benar terkirim (SMTP), supaya fitur yang bergantung pada email aktif. */
    protected function useRealMailer(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.example.test',
        ]);
    }

    /**
     * Misi kecil untuk uji skor. Jalur terbaik: A lalu C = 100 poin (akhir baik).
     * Jalur terburuk: B lalu D = -30 poin (dipotong ke 0, akhir buruk).
     *
     * @return array{scenario: Scenario, a: \App\Models\ScenarioChoice, b: \App\Models\ScenarioChoice, c: \App\Models\ScenarioChoice, d: \App\Models\ScenarioChoice}
     */
    protected function makeScenario(int $xp = 100): array
    {
        $category = Category::create([
            'slug' => 'kat-' . uniqid(), 'name' => 'Penipuan', 'icon' => '🎣', 'color' => '#22d3ee',
            'tagline' => 't', 'description' => 'd',
        ]);

        $scenario = Scenario::create([
            'category_id' => $category->id, 'slug' => 'misi-' . uniqid(), 'title' => 'Misi Uji',
            'synopsis' => 's', 'channel' => 'WhatsApp', 'difficulty' => 1, 'xp_reward' => $xp, 'is_published' => true,
        ]);

        $start = $scenario->nodes()->create(['node_key' => 'start', 'is_start' => true, 'kind' => 'story', 'body' => 'Pesan mencurigakan masuk.']);
        $mid = $scenario->nodes()->create(['node_key' => 'mid', 'kind' => 'story', 'body' => 'Penipu mendesak.']);
        $good = $scenario->nodes()->create(['node_key' => 'good', 'kind' => 'good', 'body' => 'Selamat.']);
        $bad = $scenario->nodes()->create(['node_key' => 'bad', 'kind' => 'bad', 'body' => 'Kena tipu.']);

        $a = $start->choices()->create(['label' => 'Cek ke sumber resmi', 'points' => 50, 'next_node_id' => $mid->id, 'position' => 0]);
        $b = $start->choices()->create(['label' => 'Klik tautan', 'points' => -20, 'next_node_id' => $mid->id, 'position' => 1]);
        $c = $mid->choices()->create(['label' => 'Blokir dan laporkan', 'points' => 50, 'next_node_id' => $good->id, 'position' => 0]);
        $d = $mid->choices()->create(['label' => 'Kirim kode OTP', 'points' => -10, 'next_node_id' => $bad->id, 'position' => 1]);

        return compact('scenario', 'a', 'b', 'c', 'd');
    }
}
