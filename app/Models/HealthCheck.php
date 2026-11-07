<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthCheck extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'issues' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** Persentase pemeriksaan yang tidak "down" dalam N hari terakhir (null jika belum ada data). */
    public static function uptime(int $days): ?float
    {
        $q = static::where('created_at', '>=', now()->subDays($days));
        $total = (clone $q)->count();

        if ($total === 0) {
            return null;
        }

        return round(100 * (clone $q)->where('status', '!=', 'down')->count() / $total, 2);
    }

    public static function prune(int $days): int
    {
        return static::where('created_at', '<', now()->subDays(max(1, $days)))->delete();
    }
}
