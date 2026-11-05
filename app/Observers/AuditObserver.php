<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Badge;
use App\Models\Category;
use App\Models\Lesson;
use App\Models\SafetyAction;
use App\Models\Scenario;
use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Mencatat tambah / ubah / hapus pada konten dan sekolah ke log aktivitas.
 * Hanya dicatat jika ada pengguna yang sedang login (seeder dan perintah artisan diabaikan).
 */
class AuditObserver
{
    private const LABELS = [
        Category::class => 'kategori',
        Scenario::class => 'skenario',
        Lesson::class => 'materi',
        Badge::class => 'lencana',
        SafetyAction::class => 'aksi keamanan',
        School::class => 'sekolah',
    ];

    public function created(Model $model): void
    {
        $this->log($model, 'created', 'menambahkan');
    }

    public function updated(Model $model): void
    {
        $changes = Arr::except($model->getChanges(), ['updated_at']);

        if (! $changes) {
            return;
        }

        $diff = [];
        foreach ($changes as $field => $new) {
            $diff[$field] = [
                'dari' => $this->short($model->getOriginal($field)),
                'ke' => $this->short($new),
            ];
        }

        $this->log($model, 'updated', 'mengubah', ['perubahan' => $diff]);
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'deleted', 'menghapus');
    }

    private function log(Model $model, string $event, string $verb, array $properties = []): void
    {
        $actor = auth()->user();

        if (! $actor) {
            return;
        }

        $label = self::LABELS[$model::class] ?? Str::lower(class_basename($model));
        $category = $model instanceof School ? 'school' : 'content';
        $name = $model->name ?? $model->title ?? ('#' . $model->getKey());

        ActivityLog::record(
            "{$category}.{$event}",
            "{$actor->name} {$verb} {$label} \"{$name}\"",
            $model,
            $properties,
            $actor,
        );
    }

    private function short(mixed $value): mixed
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return is_string($value) ? Str::limit($value, 120) : $value;
    }
}
