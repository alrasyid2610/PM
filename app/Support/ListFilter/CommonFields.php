<?php

namespace App\Support\ListFilter;

use App\Support\ListFilter\Fields\TextField;

/**
 * Field yang dipakai di banyak modul didefinisikan SEKALI di sini (kunci, label bawaan, aturan join), supaya modul
 * hanya menyebut kolom miliknya. Mis. "PIC Order" dipakai SO dan SQ: join ke `users` ditulis di satu tempat.
 */
final class CommonFields
{
    /** Nomor dokumen (mis. `no_so` / 'No. SO' / `s.no_so`). */
    public static function nomor(string $key, string $label, string $column): FilterField
    {
        return TextField::make($key, $label, $column);
    }

    public static function judul(string $column, string $label = 'Judul'): FilterField
    {
        return TextField::make('judul', $label, $column);
    }

    public static function pelanggan(string|array $column = 'br.nama'): FilterField
    {
        return TextField::make('pelanggan', 'Pelanggan', $column);
    }

    public static function site(string|array $column, string $label = 'Site'): FilterField
    {
        return TextField::make('site', $label, $column);
    }

    /** PIC Order = user pada kolom `pic_order` tabel SO/SQ; mencari lewat nama user. */
    public static function picOrder(string $picOrderColumn): FilterField
    {
        return TextField::make(
            'pic_order',
            'PIC Order',
            'pic_order_user.name',
            [['users as pic_order_user', 'pic_order_user.id', $picOrderColumn]],
        );
    }
}
