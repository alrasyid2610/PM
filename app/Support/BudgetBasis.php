<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Nilai acuan untuk persentase Budget Plan (2026-10-26): nilai bersih dokumen induknya,
 * yaitu BOQ (setelah discount item) + BOQ Other + BOQ Sampling, dikurangi discount level dokumen.
 * SO  : discount efektif = persen × subtotal bila `discount_persen` ada, kalau tidak `discount` (Rp).
 * SQ  : `discount` (Rp). Angka sama dengan Grand Total di printout SO / SQ.
 */
class BudgetBasis
{
    public static function forSo(?int $idSo): array
    {
        $so = $idSo ? DB::table('sales_orders')->where('id_so', $idSo)->first() : null;
        if (!$so) return self::result(0, 'SO', null);

        $woIds = DB::table('work_orders')->where('id_so', $idSo)->whereNull('deleted_at')->pluck('id_wo');
        $subtotal = 0;
        if ($woIds->isNotEmpty()) {
            $subtotal = (int) DB::table('boq')->whereIn('id_wo', $woIds)->whereNull('deleted_at')
                ->selectRaw('COALESCE(SUM(GREATEST(0, qty * harga - discount)), 0) as t')->value('t')
                + (int) DB::table('boq_tambahan')->whereIn('id_wo', $woIds)->whereNull('deleted_at')
                ->selectRaw('COALESCE(SUM(qty * harga), 0) as t')->value('t');
        }

        $discount = $so->discount_persen !== null
            ? (int) round($subtotal * (float) $so->discount_persen / 100)
            : (int) ($so->discount ?? 0);

        return self::result(max(0, $subtotal - $discount), 'SO', $so->no_so ?? null);
    }

    public static function forSq(?int $idSq): array
    {
        $sq = $idSq ? DB::table('sales_quotations')->where('id_sq', $idSq)->first() : null;
        if (!$sq) return self::result(0, 'SQ', null);

        $woIds = DB::table('sq_work_orders')->where('id_sq', $idSq)->pluck('id_sq_wo');
        $subtotal = 0;
        if ($woIds->isNotEmpty()) {
            $subtotal = (int) DB::table('sq_boq')->whereIn('id_sq_wo', $woIds)
                ->selectRaw('COALESCE(SUM(GREATEST(0, qty * harga - discount)), 0) as t')->value('t')
                + (int) DB::table('sq_boq_tambahan')->whereIn('id_sq_wo', $woIds)
                ->selectRaw('COALESCE(SUM(qty * harga), 0) as t')->value('t');
        }

        return self::result(max(0, $subtotal - (int) ($sq->discount ?? 0)), 'SQ', $sq->no_sq ?? null);
    }

    private static function result(int $nilai, string $jenis, ?string $no): array
    {
        return ['nilai' => $nilai, 'jenis' => $jenis, 'no' => $no];
    }
}
