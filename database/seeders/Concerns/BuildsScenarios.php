<?php

namespace Database\Seeders\Concerns;

use App\Models\Category;
use App\Models\Scenario;

trait BuildsScenarios
{
    /**
     * Struktur $def:
     *  slug, category, title, synopsis, channel, difficulty, xp,
     *  nodes: [key => [start?, kind?, speaker?, title?, body, lesson?, choices?]]
     *  choice: [label, to, points, feedback]
     *  red_flags: [[judul, penjelasan], ...]
     */
    protected function buildScenario(array $def): void
    {
        $category = Category::where('slug', $def['category'])->firstOrFail();

        // Hapus versi lama (node, pilihan, dan red flag ikut terhapus)
        Scenario::where('slug', $def['slug'])->delete();

        $scenario = Scenario::create([
            'category_id' => $category->id,
            'slug' => $def['slug'],
            'title' => $def['title'],
            'synopsis' => $def['synopsis'],
            'channel' => $def['channel'],
            'difficulty' => $def['difficulty'],
            'xp_reward' => $def['xp'],
            'is_published' => true,
        ]);

        // Tahap 1: buat semua adegan
        $nodes = [];
        foreach ($def['nodes'] as $key => $n) {
            $nodes[$key] = $scenario->nodes()->create([
                'node_key' => $key,
                'is_start' => $n['start'] ?? false,
                'kind' => $n['kind'] ?? 'story',
                'speaker' => $n['speaker'] ?? null,
                'title' => $n['title'] ?? null,
                'body' => $n['body'],
                'lesson' => $n['lesson'] ?? null,
            ]);
        }

        // Tahap 2: buat pilihan (butuh id adegan tujuan, jadi setelah tahap 1)
        foreach ($def['nodes'] as $key => $n) {
            foreach ($n['choices'] ?? [] as $i => $c) {
                $nodes[$key]->choices()->create([
                    'next_node_id' => $nodes[$c['to']]->id,
                    'label' => $c['label'],
                    'points' => $c['points'],
                    'feedback' => $c['feedback'],
                    'position' => $i,
                ]);
            }
        }

        foreach ($def['red_flags'] as $i => $flag) {
            $scenario->redFlags()->create([
                'title' => $flag[0],
                'explanation' => $flag[1],
                'position' => $i,
            ]);
        }
    }
}
