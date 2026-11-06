<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    /** Kategori = bagian depan kolom action (auth.login -> auth). */
    public const CATEGORIES = [
        'auth' => 'Masuk & keluar',
        'user' => 'Pengguna',
        'content' => 'Konten',
        'school' => 'Sekolah',
        'system' => 'Sistem',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Catat satu aktivitas. Tidak pernah melempar error, supaya kegagalan
     * mencatat log tidak ikut menggagalkan aksi utama pengguna.
     */
    public static function record(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?User $actor = null,
    ): void {
        try {
            $actor ??= auth()->user();
            $request = request();

            static::create([
                'user_id' => $actor?->id,
                'actor_name' => $actor?->name,
                'actor_role' => $actor?->role,
                'action' => $action,
                'description' => Str::limit($description, 497),
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'properties' => $properties ?: null,
                'ip_address' => $request?->ip(),
                'user_agent' => Str::limit((string) $request?->userAgent(), 255, ''),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Hapus log yang lebih tua dari batas simpan (minimal 7 hari). */
    public static function prune(): int
    {
        $days = max(7, (int) Setting::read('log_retention_days'));

        return static::where('created_at', '<', now()->subDays($days))->delete();
    }
}
