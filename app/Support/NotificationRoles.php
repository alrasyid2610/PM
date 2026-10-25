<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Mengambil "peran penerima dari data record" (PIC Order SO, PIC Pekerjaan WO, Penyelia FWO, dst.)
 * untuk dikirim ke NotificationDispatcher::fire() sebagai argumen $roles.
 * Bentuk hasil: ['pic_order' => [12], 'wo_pic' => [7], 'fwo_personel' => [3, 9], ...]
 * Peran 'actor' (user yang melakukan aksi) ditambahkan otomatis oleh dispatcher.
 */
class NotificationRoles
{
    private static function ids(mixed ...$values): array
    {
        return array_values(array_unique(array_map('intval', array_filter($values))));
    }

    public static function so(?int $idSo): array
    {
        if (!$idSo) return [];
        $so = DB::table('sales_orders')->where('id_so', $idSo)
            ->first(['pic_input', 'pic_order', 'pic_marketing_internal', 'pic_marketing_eksternal']);
        if (!$so) return [];

        return [
            'pic_input'           => self::ids($so->pic_input),
            'pic_order'           => self::ids($so->pic_order),
            'marketing_internal'  => self::ids($so->pic_marketing_internal),
            'marketing_eksternal' => self::ids($so->pic_marketing_eksternal),
        ];
    }

    public static function sq(?int $idSq): array
    {
        if (!$idSq) return [];
        $sq = DB::table('sales_quotations')->where('id_sq', $idSq)
            ->first(['pic_input', 'pic_order', 'pic_marketing_internal', 'pic_marketing_eksternal', 'diajukan_oleh']);
        if (!$sq) return [];

        return [
            'pengaju'             => self::ids($sq->diajukan_oleh),
            'pic_input'           => self::ids($sq->pic_input),
            'pic_order'           => self::ids($sq->pic_order),
            'marketing_internal'  => self::ids($sq->pic_marketing_internal),
            'marketing_eksternal' => self::ids($sq->pic_marketing_eksternal),
        ];
    }

    /** Peran WO + peran SO induknya. */
    public static function wo(?int $idWo): array
    {
        if (!$idWo) return [];
        $wo = DB::table('work_orders')->where('id_wo', $idWo)->first(['id_so', 'id_pic_pelanggan_pekerjaan']);
        if (!$wo) return [];

        return self::so($wo->id_so ? (int) $wo->id_so : null)
            + ['wo_pic' => self::ids($wo->id_pic_pelanggan_pekerjaan)];
    }

    /** Peran FWO + peran WO & SO induknya. Personel hanya yang punya akun login (personnel.id_user). */
    public static function fwo(?int $idFwo): array
    {
        if (!$idFwo) return [];
        $fwo = DB::table('fieldworks')->where('id_fwo', $idFwo)->first(['id_wo', 'id_penyelia']);
        if (!$fwo) return [];

        $personel = DB::table('fieldwork_personels as fp')
            ->join('personnel as p', 'p.id_personnel', '=', 'fp.id_personnel')
            ->where('fp.id_fwo', $idFwo)
            ->whereNotNull('p.id_user')
            ->pluck('p.id_user')->all();

        return self::wo($fwo->id_wo ? (int) $fwo->id_wo : null)
            + [
                'fwo_penyelia' => self::ids($fwo->id_penyelia),
                'fwo_personel' => self::ids(...$personel),
            ];
    }
}
