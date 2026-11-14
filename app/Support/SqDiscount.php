<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Discount level dokumen SQ (sejak 2026-11-14) — bisa diinput persen ATAU Rupiah, saling mengisi.
 * Disimpan keduanya: `sales_quotations.discount_persen` (persen) dan `sales_quotations.discount` (Rupiah).
 * Dasar hitung (subtotal) = BOQ setelah discount item + BOQ Other + BOQ Sampling semua SQ WO.
 */
class SqDiscount
{
    /** Subtotal semua BOQ SQ — sama dengan "Total Nilai" di KPI detail SQ. */
    public static function subtotal(int $idSq): int
    {
        $woIds = DB::table('sq_work_orders')->where('id_sq', $idSq)->pluck('id_sq_wo');
        if ($woIds->isEmpty()) {
            return 0;
        }

        return (int) DB::table('sq_boq')->whereIn('id_sq_wo', $woIds)
            ->selectRaw('COALESCE(SUM(GREATEST(0, qty * harga - discount)), 0) as t')->value('t')
            + (int) DB::table('sq_boq_tambahan')->whereIn('id_sq_wo', $woIds)
            ->selectRaw('COALESCE(SUM(qty * harga), 0) as t')->value('t');
    }

    /**
     * Discount SQ yang berlaku (Rupiah). Bila persen tersimpan, nilainya dihitung ulang dari subtotal saat ini
     * (ikut berubah bila BOQ berubah). Selisih ≤ 2 Rupiah dari Rupiah tersimpan dianggap subtotal belum berubah
     * (pembulatan persen 4 desimal), jadi Rupiah yang diketik user tampil persis.
     */
    public static function effective($sq, int $subtotal): int
    {
        $stored = (int) ($sq->discount ?? 0);
        if (!isset($sq->discount_persen) || $sq->discount_persen === null) {
            return $stored;
        }

        $dihitung = (int) round($subtotal * (float) $sq->discount_persen / 100);

        return abs($dihitung - $stored) <= 2 ? $stored : $dihitung;
    }

    /**
     * Tentukan [Rupiah, persen] yang disimpan dari input.
     * $sumber = 'rp' → Rupiah yang diketik user jadi acuan (persen diturunkan); selain itu persen jadi acuan.
     */
    public static function resolve(?float $persen, ?int $rupiah, int $subtotal, ?string $sumber = null): array
    {
        if ($sumber === 'rp' && $rupiah !== null) {
            return [$rupiah, $subtotal > 0 ? round($rupiah / $subtotal * 100, 4) : null];
        }
        if ($persen !== null) {
            return [$subtotal > 0 ? (int) round($subtotal * $persen / 100) : 0, round($persen, 4)];
        }
        if ($rupiah !== null) {
            return [$rupiah, $subtotal > 0 ? round($rupiah / $subtotal * 100, 4) : null];
        }

        return [0, null];
    }
}
