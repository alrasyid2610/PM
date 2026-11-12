<?php

namespace App\Support\ListFilter\Fields;

use App\Support\ListFilter\FilterField;
use Illuminate\Database\Query\Builder;

/**
 * Filter status berbentuk dropdown. Aturan nilai khusus (sama di semua modul):
 *   - `all`     → tidak menyaring apa pun
 *   - `deleted` → hanya data yang sudah soft-delete (bila modul punya kolom `deleted_at`)
 *   - lainnya   → data yang belum dihapus dengan status itu
 * Status "efektif" yang dihitung (mis. FWO: planned yang tanggal mulainya tiba = on_progress) diberikan lewat `$statusSql`.
 */
final class StatusField implements FilterField
{
    /**
     * @param array<string,string> $options nilai => label (termasuk `all` dan `deleted` bila dipakai)
     */
    private function __construct(
        private string $key,
        private string $label,
        private array $options,
        private ?string $statusColumn,
        private ?string $deletedColumn,
        private ?string $statusSql,
    ) {
    }

    /**
     * @param string|null $statusColumn  kolom status mentah (mis. `s.status`)
     * @param string|null $deletedColumn kolom soft-delete (mis. `s.deleted_at`); null = modul tanpa soft delete
     * @param string|null $statusSql     ekspresi SQL status efektif; bila diisi dipakai menggantikan `$statusColumn`
     */
    public static function make(string $key, string $label, array $options, ?string $statusColumn, ?string $deletedColumn = null, ?string $statusSql = null): self
    {
        return new self($key, $label, $options, $statusColumn, $deletedColumn, $statusSql);
    }

    public function key(): string
    {
        return $this->key;
    }

    public function apply(Builder $query, string $term): void
    {
        if ($term === 'all') {
            return;
        }

        if ($this->deletedColumn && $term === 'deleted') {
            $query->whereNotNull($this->deletedColumn);
            return;
        }

        if ($this->deletedColumn) {
            $query->whereNull($this->deletedColumn);
        }

        if ($this->statusSql) {
            $query->whereRaw('(' . $this->statusSql . ') = ?', [$term]);
        } else {
            $query->where($this->statusColumn, $term);
        }
    }

    public function toClient(): array
    {
        $options = [];
        foreach ($this->options as $value => $label) {
            $options[] = ['label' => $label, 'value' => (string) $value];
        }

        return ['label' => $this->label, 'value' => $this->key, 'type' => 'select', 'options' => $options];
    }
}
