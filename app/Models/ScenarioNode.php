<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScenarioNode extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_start' => 'boolean'];
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    public function choices(): HasMany
    {
        return $this->hasMany(ScenarioChoice::class, 'node_id')->orderBy('position');
    }

    public function isEnding(): bool
    {
        return $this->kind !== 'story';
    }
}
