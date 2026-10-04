<?php

namespace App\Support;

/**
 * Pilihan Status Kepegawaian (dipakai di personnel & users). Nilai disimpan
 * sebagai kode; label hanya untuk tampilan. Field tidak wajib (nullable).
 */
class StatusKepegawaian
{
    public const OPTIONS = [
        'hl' => 'HL',
        'kontrak_gol_1' => 'Kontrak Gol. 1',
        'kontrak_gol_2' => 'Kontrak Gol. 2',
        'kontrak_gol_3' => 'Kontrak Gol. 3',
        'magang' => 'Magang',
    ];

    public static function rule(): string
    {
        return 'nullable|in:' . implode(',', array_keys(self::OPTIONS));
    }

    /** Untuk formGroup.select() (format [{value, label}]). */
    public static function forSelect(): array
    {
        return array_map(fn($v, $l) => ['value' => $v, 'label' => $l], array_keys(self::OPTIONS), self::OPTIONS);
    }
}
