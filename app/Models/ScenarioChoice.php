<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScenarioChoice extends Model
{
    protected $guarded = [];

    public function node(): BelongsTo
    {
        return $this->belongsTo(ScenarioNode::class, 'node_id');
    }

    public function next(): BelongsTo
    {
        return $this->belongsTo(ScenarioNode::class, 'next_node_id');
    }
}
