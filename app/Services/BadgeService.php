<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\User;
use Illuminate\Support\Collection;

class BadgeService
{
    /**
     * Periksa semua lencana yang belum dimiliki user, berikan yang syaratnya terpenuhi.
     * Nama lencana baru disimpan di session agar muncul sebagai notifikasi.
     */
    public function evaluate(User $user, bool $notify = true): Collection
    {
        $earnedIds = $user->badges()->pluck('badges.id');
        $new = collect();

        foreach (Badge::whereNotIn('id', $earnedIds)->get() as $badge) {
            if ($this->qualifies($user, $badge)) {
                $user->badges()->attach($badge->id);
                $new->push($badge);
            }
        }

        // Notifikasi hanya relevan saat ada request web (bukan saat seeding di terminal).
        if ($notify && $new->isNotEmpty() && ! app()->runningInConsole()) {
            $names = array_merge(session('new_badges', []), $new->pluck('name')->all());
            session()->flash('new_badges', $names);
        }

        return $new;
    }

    private function qualifies(User $user, Badge $badge): bool
    {
        $finished = fn () => $user->attempts()->where('status', 'finished');

        return match ($badge->type) {
            'xp' => $user->xp >= $badge->threshold,
            'scenarios' => $finished()->distinct()->count('scenario_id') >= $badge->threshold,
            'perfect' => $finished()->where('score', '>=', 100)
                ->distinct()->count('scenario_id') >= $badge->threshold,
            'good_end' => $finished()->where('ending_kind', 'good')->count() >= $badge->threshold,
            'category' => $finished()
                ->whereHas('scenario', fn ($q) => $q->where('category_id', $badge->category_id))
                ->distinct()->count('scenario_id') >= $badge->threshold,
            'lessons' => $user->lessons()->count() >= $badge->threshold,
            'actions' => $user->safetyActions()->count() >= $badge->threshold,
            default => false,
        };
    }
}
