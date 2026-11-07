<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

class Announcement extends Model
{
    protected $guarded = [];

    public const LEVELS = [
        'info' => 'Informasi',
        'warning' => 'Peringatan',
        'danger' => 'Penting / darurat',
    ];

    public const AUDIENCES = [
        'all' => 'Semua pengunjung (termasuk yang belum masuk)',
        'student' => 'Siswa',
        'parent' => 'Orang tua',
        'teacher' => 'Guru',
        'school_admin' => 'Admin sekolah',
        'superadmin' => 'Superadmin',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** Aktif, sudah mulai, dan belum berakhir. */
    public function scopeLive($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function isLive(): bool
    {
        return $this->is_active
            && ($this->starts_at === null || $this->starts_at->lte(now()))
            && ($this->ends_at === null || $this->ends_at->gte(now()));
    }

    /**
     * Pengumuman yang tampil untuk satu peran (null = tamu). Disimpan di cache singkat
     * supaya tidak menambah query di setiap halaman, dan tidak pernah merusak halaman.
     */
    public static function visibleFor(?string $role): array
    {
        try {
            return Cache::remember('announcements:' . ($role ?? 'guest'), 60, function () use ($role) {
                return static::live()
                    ->whereIn('audience', array_values(array_unique(array_filter(['all', $role]))))
                    ->orderByRaw("case level when 'danger' then 0 when 'warning' then 1 else 2 end")
                    ->latest('id')
                    ->limit(5)
                    ->get(['id', 'message', 'level'])
                    ->map(fn ($a) => $a->only(['id', 'message', 'level']))
                    ->all();
            });
        } catch (Throwable) {
            return [];
        }
    }

    public static function forgetCache(): void
    {
        foreach (array_merge(['guest'], array_keys(self::AUDIENCES)) as $role) {
            Cache::forget('announcements:' . $role);
        }
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());
    }
}
