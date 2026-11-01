<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $guarded = [];

    public function scenarios(): HasMany
    {
        return $this->hasMany(Scenario::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    public function badges(): HasMany
{
    return $this->hasMany(Badge::class);
}
}
