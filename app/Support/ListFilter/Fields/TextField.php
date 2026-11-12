<?php

namespace App\Support\ListFilter\Fields;

use App\Support\ListFilter\FilterField;
use App\Support\ListFilter\JoinGuard;
use Illuminate\Database\Query\Builder;

/**
 * Filter teks "berisi" (LIKE %kata%). Boleh mencari di beberapa kolom sekaligus (OR) dan boleh membawa join
 * tambahan yang dipasang hanya saat filter ini dipakai.
 */
final class TextField implements FilterField
{
    /**
     * @param string[] $columns kolom yang dicari (OR bila lebih dari satu)
     * @param array<int, array{0:string,1:string,2:string}> $joins [tabel+alias, kolom kiri, kolom kanan] — left join
     */
    private function __construct(
        private string $key,
        private string $label,
        private array $columns,
        private array $joins = [],
    ) {
    }

    public static function make(string $key, string $label, string|array $columns, array $joins = []): self
    {
        return new self($key, $label, (array) $columns, $joins);
    }

    public function key(): string
    {
        return $this->key;
    }

    public function apply(Builder $query, string $term): void
    {
        foreach ($this->joins as [$table, $first, $second]) {
            JoinGuard::ensureLeft($query, $table, $first, $second);
        }

        $like = '%' . $term . '%';

        if (count($this->columns) === 1) {
            $query->where($this->columns[0], 'like', $like);
            return;
        }

        $query->where(function ($group) use ($like) {
            foreach ($this->columns as $column) {
                $group->orWhere($column, 'like', $like);
            }
        });
    }

    public function toClient(): array
    {
        return ['label' => $this->label, 'value' => $this->key];
    }
}
