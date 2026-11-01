<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    protected $guarded = [];

    /** Jenis aturan yang dikenali BadgeService::qualifies(): kunci => [label, petunjuk threshold] */
    public const TYPES = [
        'xp' => ['Total XP', 'Jumlah XP yang harus dikumpulkan siswa'],
        'scenarios' => ['Misi selesai', 'Jumlah misi berbeda yang harus diselesaikan'],
        'perfect' => ['Skor sempurna', 'Jumlah misi berbeda yang harus diselesaikan dengan skor 100'],
        'good_end' => ['Akhir cerita baik', 'Berapa kali siswa harus meraih akhir baik (misi yang sama dihitung berulang)'],
        'category' => ['Misi per kategori', 'Jumlah misi berbeda yang harus diselesaikan pada kategori yang dipilih'],
        'lessons' => ['Materi dibaca', 'Jumlah materi yang harus diselesaikan'],
        'actions' => ['Aksi keamanan', 'Jumlah aksi keamanan yang harus diselesaikan'],
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'badge_user')->withTimestamps();
    }

    /** Teks syarat untuk tampilan admin, mis. "Total XP ≥ 500" */
    public function requirementText(): string
    {
        $text = (self::TYPES[$this->type][0] ?? $this->type) . ' ≥ ' . $this->threshold;

        if ($this->type === 'category' && $this->category) {
            $text .= ' (' . $this->category->name . ')';
        }

        return $text;
    }
}