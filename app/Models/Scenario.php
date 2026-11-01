<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scenario extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(ScenarioNode::class);
    }

    public function redFlags(): HasMany
    {
        return $this->hasMany(RedFlag::class)->orderBy('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function difficultyLabel(): string
    {
        return match ($this->difficulty) {
            1 => 'Mudah',
            2 => 'Sedang',
            default => 'Sulit',
        };
    }

    public function problems(): array
{
    $nodes = $this->nodes()->with('choices')->get();
    $problems = [];

    if ($nodes->where('is_start', true)->count() !== 1) {
        $problems[] = 'Harus ada tepat satu adegan awal.';
    }
    if ($nodes->where('kind', '!=', 'story')->isEmpty()) {
        $problems[] = 'Belum ada adegan akhir (baik, netral, atau buruk).';
    }
    foreach ($nodes as $node) {
        if ($node->kind === 'story' && $node->choices->isEmpty()) {
            $problems[] = "Adegan \"{$node->node_key}\" belum punya pilihan.";
        }
        foreach ($node->choices as $choice) {
            if (! $choice->next_node_id) {
                $problems[] = "Ada pilihan di adegan \"{$node->node_key}\" tanpa tujuan.";
            }
        }
    }

    return $problems;
}
}
