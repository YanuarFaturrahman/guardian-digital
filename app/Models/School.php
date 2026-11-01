<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class School extends Model
{
    protected $guarded = [];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Kunci pencocokan: "SMA Negeri 1", "SMAN 1", "SMA N 1" menghasilkan kunci sama. */
    public static function makeKey(string $name): string
    {
        return Str::of($name)
            ->lower()
            ->replace('negeri', 'n')
            ->replaceMatches('/[^a-z0-9]/', '')
            ->toString();
    }

    /** Rapikan spasi; jika semua huruf kecil, jadikan huruf judul dan kapitalkan singkatan. */
    public static function cleanName(string $name): string
    {
        $name = Str::of($name)->squish()->toString();

        if ($name === mb_strtolower($name)) {
            $name = Str::title($name);
        }

        return preg_replace_callback(
            '/\b(sma|smk|smp|sd|man|ma|sman|smkn|smpn)\b/i',
            fn ($m) => strtoupper($m[1]),
            $name
        );
    }

    /** Cari sekolah dengan kunci sama; buat baru jika belum ada. */
    public static function findOrCreateByName(string $name): self
    {
        $clean = static::cleanName($name);

        return static::firstOrCreate(
            ['match_key' => static::makeKey($clean)],
            ['name' => $clean]
        );
    }
}