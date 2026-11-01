<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
    'name', 'email', 'password', 'role', 'school_id', 'avatar', 'link_code', 'xp',
];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'xp' => 'integer',
        'must_change_password' => 'boolean',
        'deactivated_at' => 'datetime',
    ];
}

    protected static function booted(): void
    {
        // Setiap siswa baru otomatis mendapat kode tautan untuk orang tua.
        static::creating(function (User $user) {
            if ($user->role === 'student' && empty($user->link_code)) {
                do {
                    $code = 'GD-' . strtoupper(Str::random(6));
                } while (static::where('link_code', $code)->exists());
                $user->link_code = $code;
            }
        });
    }

        /**
     * Batasi daftar pengguna ke yang boleh dikelola $actor.
     * Superadmin: semua. Admin sekolah: siswa dan guru sekolahnya, serta orang tua
     * yang minimal satu anaknya bersekolah di sana.
     */
    public function scopeManageableBy(Builder $query, User $actor): Builder
    {
        if ($actor->role === 'superadmin') {
            return $query;
        }

        // Peran lain, atau admin sekolah yang belum punya sekolah: tidak melihat siapa pun.
        // (Tanpa pengaman ini, where('school_id', null) akan memilih semua pengguna tanpa sekolah.)
        if ($actor->role !== 'school_admin' || ! $actor->school_id) {
            return $query->whereRaw('1 = 0');
        }

        $schoolId = $actor->school_id;

        return $query->where(function ($q) use ($schoolId) {
            $q->where(fn ($s) => $s->whereIn('role', ['student', 'teacher'])->where('school_id', $schoolId))
                ->orWhere(fn ($p) => $p->where('role', 'parent')
                    ->whereHas('children', fn ($c) => $c->where('school_id', $schoolId)));
        });
    }

    /** 10 karakter acak tanpa huruf/angka yang mirip (0, O, 1, l, I), selalu memuat huruf dan angka. */
    public static function generateTempPassword(): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $password = '';
            for ($i = 0; $i < 10; $i++) {
                $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (! preg_match('/[a-zA-Z]/', $password) || ! preg_match('/[0-9]/', $password));

        return $password;
    }

    // ---------- Relasi ----------
    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'badge_user')->withTimestamps();
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'lesson_user')->withTimestamps();
    }

    public function safetyActions(): BelongsToMany
    {
        return $this->belongsToMany(SafetyAction::class, 'safety_action_user')
            ->withTimestamps();
    }

    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'classroom_student', 'student_id', 'classroom_id')
            ->withTimestamps();
    }

    public function ownedClassrooms(): HasMany
    {
        return $this->hasMany(Classroom::class, 'teacher_id');
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_student', 'parent_id', 'student_id')
            ->withTimestamps();
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_student', 'student_id', 'parent_id')
            ->withTimestamps();
    }

    public function schoolRef(): BelongsTo
{
    return $this->belongsTo(School::class, 'school_id');
}

/** Agar $user->school tetap bisa dipakai di tampilan (mengembalikan nama sekolah). */
protected function school(): Attribute
{
    return Attribute::get(fn () => $this->schoolRef?->name);
}

    // ---------- Level dan XP ----------
    public function levelData(): array
    {
        $thresholds = [0, 200, 500, 1000, 2000];
        $titles = ['Rookie', 'Pengamat', 'Penjaga', 'Guardian', 'Grandmaster'];

        $index = 0;
        foreach ($thresholds as $i => $min) {
            if ($this->xp >= $min) {
                $index = $i;
            }
        }

        $floor = $thresholds[$index];
        $next = $thresholds[$index + 1] ?? null;
        $percent = $next ? (int) floor((($this->xp - $floor) / ($next - $floor)) * 100) : 100;

        return [
            'level' => $index + 1,
            'title' => $titles[$index],
            'floor' => $floor,
            'next' => $next,
            'percent' => $percent,
        ];
    }

    // ---------- Ringkasan progres (dipakai siswa, orang tua, guru) ----------
    public function bestScores()
    {
        return $this->attempts()->where('status', 'finished')->get()
            ->groupBy('scenario_id')
            ->map(fn ($group) => $group->max('score'));
    }

    public function progressSummary(): array
    {
        $best = $this->bestScores();

        return [
            'scenarios_done' => $best->count(),
            'avg_score' => $best->count() ? (int) round($best->avg()) : 0,
            'lessons_read' => $this->lessons()->count(),
            'actions_done' => $this->safetyActions()->count(),
            'badges' => $this->badges()->count(),
            'last_active' => $this->attempts()->max('updated_at'),
        ];
    }

    /** Penguasaan (0-100) per kategori: rata-rata skor terbaik, misi belum dimainkan = 0. */
    public function mastery()
    {
        $best = $this->bestScores();

        return Category::with(['scenarios' => fn ($q) => $q->where('is_published', true)])
            ->get()
            ->map(function ($category) use ($best) {
                $total = $category->scenarios->count();
                $sum = $category->scenarios->sum(fn ($s) => $best->get($s->id, 0));
                $category->mastery = $total ? (int) round($sum / $total) : 0;

                return $category;
            });
    }

    /** Paket data laporan untuk halaman pantauan orang tua dan guru. */
    public function report(): array
    {
        return [
            'student' => $this,
            'summary' => $this->progressSummary(),
            'mastery' => $this->mastery(),
            'attempts' => $this->attempts()->where('status', 'finished')
                ->with('scenario.category')->latest('finished_at')->take(8)->get(),
            'badges' => $this->badges()->get(),
            'level' => $this->levelData(),
        ];
    }
}
