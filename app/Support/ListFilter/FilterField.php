<?php

namespace App\Support\ListFilter;

use Illuminate\Database\Query\Builder;

/**
 * Satu kolom yang bisa dicari di bar filter daftar (tombol kaca pembesar di halaman index).
 *
 * Kontrak kecil sengaja: `FilterSet` hanya bergantung pada interface ini, jadi tipe filter baru (tanggal, rentang angka,
 * dst.) cukup berupa kelas baru yang mengimplementasikannya — engine & modul yang sudah ada tidak perlu diubah.
 */
interface FilterField
{
    /** Kunci yang dikirim klien sebagai `filters[i][by]` (juga nilai opsi di dropdown "Cari berdasarkan"). */
    public function key(): string;

    /** Terapkan 1 kata kunci ke query. `$term` sudah di-trim dan tidak kosong. */
    public function apply(Builder $query, string $term): void;

    /** Bentuk untuk prop `:search-fields` di view (`label`, `value`, opsional `type` + `options`). */
    public function toClient(): array;
}
