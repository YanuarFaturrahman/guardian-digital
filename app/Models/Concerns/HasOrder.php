<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait HasOrder
{
    /**
     * Kolom yang membagi urutan menjadi kelompok, misalnya ['category_id'] agar setiap
     * kategori punya urutan 1, 2, 3 sendiri. Kosong = satu urutan untuk semua item.
     */
    protected function orderScope(): array
    {
        return [];
    }

    /** Buat item baru dan sisipkan di urutan $position (kosong = paling akhir). */
    public static function createInOrder(array $attributes, ?int $position = null): static
    {
        return DB::transaction(function () use ($attributes, $position) {
            $model = static::create($attributes);
            $model->arrange($position);

            return $model->refresh();
        });
    }

    /**
     * Ubah item lalu pindahkan ke urutan $position. Jika $position kosong: tetap di urutan
     * semula (kelompok sama) atau paling akhir (pindah kelompok).
     */
    public function updateInOrder(array $attributes, ?int $position = null): void
    {
        DB::transaction(function () use ($attributes, $position) {
            $oldScope = $this->only($this->orderScope());
            $oldPosition = (int) $this->position;

            $this->update($attributes);

            $sameGroup = $this->only($this->orderScope()) == $oldScope;

            if (! $sameGroup) {
                // Tutup celah yang ditinggalkan item ini di kelompok lama
                static::renumber(static::orderedGroup($oldScope)->pluck($this->getKeyName())->all());
            }

            $this->arrange($position ?? ($sameGroup ? $oldPosition : null));
        });
    }

    /** Hapus item, lalu rapikan urutan sisanya agar tidak ada celah. */
    public function deleteInOrder(): void
    {
        DB::transaction(function () {
            $scope = $this->only($this->orderScope());

            $this->delete();

            static::renumber(static::orderedGroup($scope)->pluck($this->getKeyName())->all());
        });
    }

    /** Rapikan semua kelompok menjadi 1, 2, 3, ... (dipakai perintah urutan:rapikan). */
    public static function normalizeOrder(): void
    {
        $columns = (new static)->orderScope();

        static::query()->orderBy('position')->orderBy('id')
            ->get(array_merge(['id'], $columns))
            ->groupBy(fn ($row) => implode('|', $row->only($columns)))
            ->each(fn ($group) => static::renumber($group->pluck('id')->all()));
    }

    /** Sisipkan item ini di $position (mulai dari 1) pada kelompoknya, lalu beri nomor ulang. */
    protected function arrange(?int $position): void
    {
        $key = $this->getKeyName();

        $ids = static::orderedGroup($this->only($this->orderScope()))
            ->where($key, '!=', $this->getKey())
            ->lockForUpdate()
            ->pluck($key)
            ->all();

        $index = $position === null ? count($ids) : max(0, min($position - 1, count($ids)));
        array_splice($ids, $index, 0, [$this->getKey()]);

        static::renumber($ids);
    }

    /** Semua item satu kelompok, berurutan (urutan sama dipecah berdasarkan id). */
    protected static function orderedGroup(array $scope): Builder
    {
        return static::query()->where($scope)->orderBy('position')->orderBy('id');
    }

    /** Beri nomor 1..N sesuai urutan $ids. Hanya baris yang nilainya berubah yang di-update. */
    protected static function renumber(array $ids): void
    {
        $current = static::query()->whereKey($ids)->pluck('position', 'id');

        foreach (array_values($ids) as $i => $id) {
            if ((int) $current->get($id) !== $i + 1) {
                static::query()->whereKey($id)->update(['position' => $i + 1]);
            }
        }
    }
}