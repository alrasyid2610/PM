<?php

namespace App\Support\ListFilter;

use Illuminate\Database\Query\Builder;

/**
 * Kumpulan filter 1 daftar (1 modul). Subclass HANYA mendeklarasikan `fields()`; engine di sini yang mengurus sisanya:
 * membersihkan input, menerapkan ke query, dan menghasilkan daftar untuk dropdown "Cari berdasarkan" di view.
 * Dengan begitu definisi filter ditulis SATU kali per modul (view dan controller membaca sumber yang sama).
 *
 * Pemakaian:
 *   controller:  $set = new SalesOrderFilters();  $filters = $set->normalize($request->input('filters'));
 *                $query = Base::query()->when(!$filters, <pembatas bawaan>);  $set->apply($query, $filters);
 *   view:        <x-crud-index :search-fields="(new \App\Support\ListFilter\Sets\SalesOrderFilters)->forView()" />
 *
 * Menambah filter = 1 baris di `fields()` modul itu. Tipe filter baru = kelas baru yang mengimplementasikan FilterField.
 */
abstract class FilterSet
{
    /** @var array<string,FilterField>|null */
    private ?array $indexed = null;

    /** @return FilterField[] urutannya = urutan di dropdown */
    abstract protected function fields(): array;

    /**
     * Bersihkan input `filters` dari klien: hanya kunci yang dikenal, kata kunci di-trim dan tidak kosong.
     * Hasil kosong = tidak sedang mencari (pembatas bawaan daftar, mis. "hanya yang aktif", tetap berlaku).
     *
     * @return array<int,array{by:string,q:string}>
     */
    public function normalize(mixed $raw): array
    {
        $clean = [];

        foreach ((array) $raw as $item) {
            $by = is_array($item) ? (string) ($item['by'] ?? '') : '';
            $q  = is_array($item) ? trim((string) ($item['q'] ?? '')) : '';

            if ($by === '' || $q === '' || !isset($this->index()[$by])) {
                continue;
            }

            $clean[] = ['by' => $by, 'q' => $q];
        }

        return $clean;
    }

    /** Terapkan semua filter (AND) ke query. `$filters` = hasil `normalize()`. */
    public function apply(Builder $query, array $filters): Builder
    {
        foreach ($filters as $filter) {
            $this->index()[$filter['by']]->apply($query, $filter['q']);
        }

        return $query;
    }

    /** Untuk prop `:search-fields` komponen `<x-crud-index>`. */
    public function forView(): array
    {
        return array_map(fn (FilterField $field) => $field->toClient(), $this->fields());
    }

    /** @return array<string,FilterField> */
    private function index(): array
    {
        if ($this->indexed === null) {
            $this->indexed = [];
            foreach ($this->fields() as $field) {
                $this->indexed[$field->key()] = $field;
            }
        }

        return $this->indexed;
    }
}
