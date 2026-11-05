<?php

namespace App\Support;

/** Pilihan tetap modul Kendaraan. Daftar nilai JS-nya ada di public/assets/js/kendaraan/form.js (harus sama). */
class KendaraanOptions
{
    public const JENIS = [
        'mobil'    => 'Mobil',
        'pickup'   => 'Pickup',
        'minibus'  => 'Minibus',
        'truk'     => 'Truk',
        'motor'    => 'Motor',
        'lainnya'  => 'Lainnya',
    ];

    public const KEPEMILIKAN = [
        'milik_sendiri' => 'Milik Sendiri',
        'sewa'          => 'Sewa',
    ];

    public static function jenisRule(): array
    {
        return ['required', 'in:' . implode(',', array_keys(self::JENIS))];
    }

    public static function kepemilikanRule(): array
    {
        return ['required', 'in:' . implode(',', array_keys(self::KEPEMILIKAN))];
    }
}
