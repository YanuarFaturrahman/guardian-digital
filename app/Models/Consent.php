<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Consent extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['granted_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Simpan persetujuan baru. Persetujuan aktif sebelumnya dengan jenis yang sama
     * untuk akun ini ditandai dicabut, supaya selalu ada satu yang berlaku.
     */
    public static function grant(User $user, string $type, array $by = []): self
    {
        static::where('user_id', $user->id)->where('type', $type)->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $request = request();
        $consent = static::create([
            'user_id' => $user->id,
            'type' => $type,
            'policy_version' => config('privacy.policy_version'),
            'given_by_name' => $by['name'] ?? null,
            'given_by_email' => $by['email'] ?? null,
            'given_by_user_id' => $by['user_id'] ?? null,
            'ip_address' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 255, ''),
            'granted_at' => now(),
        ]);

        $user->unsetRelation('consents');

        return $consent;
    }

    public static function revoke(User $user, string $type): void
    {
        static::where('user_id', $user->id)->where('type', $type)->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $user->unsetRelation('consents');
    }
}
