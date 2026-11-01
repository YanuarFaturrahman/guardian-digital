<?php

namespace App\Models;

use App\Models\Concerns\HasOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SafetyAction extends Model
{
    use HasOrder; // satu urutan untuk semua aksi (tanpa orderScope)

    protected $guarded = [];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'safety_action_user')->withTimestamps();
    }
}