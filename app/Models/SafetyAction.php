<?php

namespace App\Models;

use App\Models\Concerns\HasOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SafetyAction extends Model
{
    use HasOrder; // satu urutan untuk semua aksi (tanpa orderScope)

    protected $guarded = [];

    /** Tingkat urgensi: makin kecil angkanya, makin mendesak dan makin besar XP-nya. */
    public const PRIORITIES = [
        1 => [
            'label' => 'Darurat',
            'icon' => '🔴',
            'when' => 'Lakukan hari ini',
            'desc' => 'Kunci utama akunmu. Kalau bagian ini lemah, penipu paling mudah membobol akun.',
            'xp' => 50,
            'color' => '#f43f5e',
        ],
        2 => [
            'label' => 'Penting',
            'icon' => '🟠',
            'when' => 'Lakukan minggu ini',
            'desc' => 'Menutup celah yang paling sering dimanfaatkan penipu dan pencuri data.',
            'xp' => 35,
            'color' => '#f59e0b',
        ],
        3 => [
            'label' => 'Tambahan',
            'icon' => '🟢',
            'when' => 'Jadikan kebiasaan',
            'desc' => 'Kebiasaan baik untuk melindungi dirimu dan orang-orang di sekitarmu.',
            'xp' => 20,
            'color' => '#10b981',
        ],
    ];

    protected function casts(): array
    {
        return ['priority' => 'integer'];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'safety_action_user')->withTimestamps();
    }

    /** Info tingkat untuk aksi ini (nilai tak dikenal dianggap Tambahan). */
    public function tier(): array
    {
        return self::PRIORITIES[$this->priority] ?? self::PRIORITIES[3];
    }
}