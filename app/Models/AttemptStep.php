<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptStep extends Model
{
    protected $guarded = [];

    public function node(): BelongsTo
    {
        return $this->belongsTo(ScenarioNode::class, 'node_id');
    }

    public function choice(): BelongsTo
    {
        return $this->belongsTo(ScenarioChoice::class, 'choice_id');
    }
}
