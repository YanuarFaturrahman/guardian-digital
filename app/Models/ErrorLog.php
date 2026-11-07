<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /** Hapus error yang terakhir terlihat lebih dari N hari lalu. */
    public static function prune(int $days): int
    {
        return static::where('last_seen_at', '<', now()->subDays(max(1, $days)))->delete();
    }
}
