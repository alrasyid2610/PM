<?php

namespace App\Support;

/**
 * Normalisasi input satu item Budget Plan (WO, FWO, SQ WO, SQ FWO): Hari, Qty, Unit, Harga Satuan (2026-10-26).
 * `nominal_budget` tetap TOTAL item (= harga_satuan × qty × hari) sehingga semua perhitungan lama
 * (realisasi, selisih, persentase, PDF, SQ→SO, clone) tidak berubah. Total dihitung ulang di sini —
 * `nominal_budget` kiriman klien tidak dipercaya bila `harga_satuan` ada. Pemanggil lama yang hanya
 * mengirim `nominal_budget` dianggap hari 1, qty 1, harga satuan = nominal.
 */
class BudgetItemInput
{
    public static function fields(array $item): array
    {
        $qty  = (isset($item['qty']) && $item['qty'] !== '') ? (float) $item['qty'] : 1.0;
        $hari = (isset($item['hari']) && $item['hari'] !== '') ? (float) $item['hari'] : 1.0;
        if ($qty <= 0) $qty = 1.0;
        if ($hari <= 0) $hari = 1.0;

        if (isset($item['harga_satuan']) && $item['harga_satuan'] !== '') {
            $harga = (int) $item['harga_satuan'];
        } else {
            $harga = (int) ($item['nominal_budget'] ?? 0);
            $qty = 1.0;
            $hari = 1.0;
        }

        return [
            'qty'            => round($qty, 2),
            'hari'           => round($hari, 2),
            'id_satuan'      => !empty($item['id_satuan']) ? (int) $item['id_satuan'] : null,
            'harga_satuan'   => $harga,
            'nominal_budget' => (int) round($qty * $hari * $harga),
        ];
    }

    /** Teks ringkas untuk audit/tampilan: "2 hari × 2 orang × Rp 70.000" (kosong bila hari 1, qty 1, tanpa unit). */
    public static function label($qty, $satuan, $harga, $hari = 1): string
    {
        $qty = (float) $qty;
        $hari = (float) $hari;
        if ($qty == 1.0 && $hari == 1.0 && !$satuan) return '';
        $fmt = fn($n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
        return ($hari != 1.0 ? $fmt($hari) . ' hari × ' : '')
            . $fmt($qty) . ($satuan ? ' ' . $satuan : '')
            . ' × Rp ' . number_format((int) $harga, 0, ',', '.');
    }
}
