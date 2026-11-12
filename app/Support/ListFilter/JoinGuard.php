<?php

namespace App\Support\ListFilter;

use Illuminate\Database\Query\Builder;

/**
 * Memastikan 1 join hanya terpasang sekali. Filter yang butuh tabel tambahan (mis. PIC Order → users) mendeklarasikan
 * join-nya sendiri; kalau query dasar atau filter lain sudah memasang join dengan alias yang sama, tidak dipasang lagi.
 */
final class JoinGuard
{
    /** @param string $table contoh: 'users as pic_order_user' (alias wajib agar tidak bentrok dengan join lain) */
    public static function ensureLeft(Builder $query, string $table, string $first, string $second): void
    {
        foreach ($query->joins ?? [] as $join) {
            if ((string) $join->table === $table) {
                return;
            }
        }

        $query->leftJoin($table, $first, '=', $second);
    }
}
