<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /** Nilai bawaan sekaligus penentu tipe (bool / int / string). */
    public const DEFAULTS = [
        'maintenance_mode' => false,
        'maintenance_message' => '',
        'registration_open' => true,
        'max_upload_kb' => 2048,
        'backup_auto' => false,
        'backup_keep' => 7,
        'log_retention_days' => 180,
        'external_api_enabled' => false,
        'external_api_url' => '',
        'external_api_key' => '',
    ];

    /** Disimpan terenkripsi di database. */
    public const SECRETS = ['external_api_key'];

    private static ?array $memo = null;

    private static function stored(): array
    {
        if (static::$memo !== null) {
            return static::$memo;
        }

        try {
            return static::$memo = Cache::remember(
                'system.settings',
                3600,
                fn () => static::query()->pluck('value', 'key')->all()
            );
        } catch (Throwable) {
            // Tabel belum dimigrasi: pakai nilai bawaan, jangan merusak halaman.
            return [];
        }
    }

    public static function read(string $key, mixed $default = null): mixed
    {
        $default ??= self::DEFAULTS[$key] ?? null;
        $raw = static::stored()[$key] ?? null;

        if ($raw === null) {
            return $default;
        }

        if (in_array($key, self::SECRETS, true)) {
            try {
                return $raw === '' ? '' : Crypt::decryptString($raw);
            } catch (Throwable) {
                return '';
            }
        }

        $type = self::DEFAULTS[$key] ?? null;

        return match (true) {
            is_bool($type) => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            is_int($type) => (int) $raw,
            default => $raw,
        };
    }

    public static function bool(string $key): bool
    {
        return (bool) static::read($key);
    }

    public static function put(string $key, mixed $value): void
    {
        $raw = is_bool($value) ? ($value ? '1' : '0') : (string) $value;

        if (in_array($key, self::SECRETS, true) && $raw !== '') {
            $raw = Crypt::encryptString($raw);
        }

        static::query()->updateOrCreate(['key' => $key], ['value' => $raw]);

        static::$memo = null;
        Cache::forget('system.settings');
    }
}
