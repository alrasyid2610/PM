<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Angka untuk kartu "Nilai Budget" di baris KPI detail SO dan detail SQ (2026-10-31), sama dengan kartu di detail WO:
 * Budget FWO dan Budget WO — Estimasi (SUM nominal item) dan, khusus SO, Realisasi (SUM semua actual yang tercatat,
 * sama dengan total di kartu plan). `nilai` = Total Nilai dokumen untuk persentase = angka kartu "Total Nilai" di halaman
 * yang sama: BOQ (setelah discount item) + BOQ Other + BOQ Sampling, TANPA mengurangi discount level SO/SQ.
 */
class BudgetSummary
{
    /** SO: semua WO (tidak terhapus) milik SO — estimasi + realisasi Budget WO dan Budget FWO. */
    public static function forSo(int $idSo): array
    {
        $woIds = DB::table('work_orders')->where('id_so', $idSo)->whereNull('deleted_at')->pluck('id_wo');
        if ($woIds->isEmpty()) {
            return ['nilai' => 0, 'wo' => ['estimasi' => 0, 'realisasi' => 0, 'tanpa_realisasi' => 0], 'fwo' => ['estimasi' => 0, 'realisasi' => 0, 'tanpa_realisasi' => 0]];
        }

        $woEst = (int) DB::table('wo_budget_items as i')
            ->join('wo_budgets as b', 'b.id_budget', '=', 'i.id_budget')
            ->whereIn('b.id_wo', $woIds)->whereNull('b.deleted_at')->sum(DB::raw('i.nominal_budget - i.nominal_tanpa_realisasi'));
        $woReal = (int) DB::table('wo_budget_actuals as a')
            ->join('wo_budget_items as i', 'i.id_budget_item', '=', 'a.id_budget_item')
            ->join('wo_budgets as b', 'b.id_budget', '=', 'i.id_budget')
            ->whereIn('b.id_wo', $woIds)->whereNull('b.deleted_at')->sum('a.nominal_actual');

        $fwoEst = (int) DB::table('fwo_budget_items as i')
            ->join('fwo_budgets as b', 'b.id_budget', '=', 'i.id_budget')
            ->join('fieldworks as f', 'f.id_fwo', '=', 'b.id_fwo')
            ->whereIn('f.id_wo', $woIds)->whereNull('f.deleted_at')->whereNull('b.deleted_at')->sum(DB::raw('i.nominal_budget - i.nominal_tanpa_realisasi'));
        $fwoReal = (int) DB::table('fwo_budget_actuals as a')
            ->join('fwo_budget_items as i', 'i.id_budget_item', '=', 'a.id_budget_item')
            ->join('fwo_budgets as b', 'b.id_budget', '=', 'i.id_budget')
            ->join('fieldworks as f', 'f.id_fwo', '=', 'b.id_fwo')
            ->whereIn('f.id_wo', $woIds)->whereNull('f.deleted_at')->whereNull('b.deleted_at')->sum('a.nominal_actual');

        // Item bertanda "Tanpa realisasi": dikeluarkan dari estimasi (pembanding realisasi), ditampilkan terpisah
        $woTanpa = (int) DB::table('wo_budget_items as i')
            ->join('wo_budgets as b', 'b.id_budget', '=', 'i.id_budget')
            ->whereIn('b.id_wo', $woIds)->whereNull('b.deleted_at')->sum('i.nominal_tanpa_realisasi');
        $fwoTanpa = (int) DB::table('fwo_budget_items as i')
            ->join('fwo_budgets as b', 'b.id_budget', '=', 'i.id_budget')
            ->join('fieldworks as f', 'f.id_fwo', '=', 'b.id_fwo')
            ->whereIn('f.id_wo', $woIds)->whereNull('f.deleted_at')->whereNull('b.deleted_at')->sum('i.nominal_tanpa_realisasi');

        $nilai = (int) DB::table('boq')->whereIn('id_wo', $woIds)->whereNull('deleted_at')
                ->selectRaw('COALESCE(SUM(GREATEST(0, qty * harga - discount)), 0) as t')->value('t')
            + (int) DB::table('boq_tambahan')->whereIn('id_wo', $woIds)->whereNull('deleted_at')
                ->selectRaw('COALESCE(SUM(qty * harga), 0) as t')->value('t');

        return [
            'nilai' => $nilai,
            'wo'    => ['estimasi' => $woEst, 'realisasi' => $woReal, 'tanpa_realisasi' => $woTanpa],
            'fwo'   => ['estimasi' => $fwoEst, 'realisasi' => $fwoReal, 'tanpa_realisasi' => $fwoTanpa],
        ];
    }

    /** SQ: semua SQ WO milik SQ — hanya estimasi (SQ tidak punya realisasi). */
    public static function forSq(int $idSq): array
    {
        $woIds = DB::table('sq_work_orders')->where('id_sq', $idSq)->pluck('id_sq_wo');
        if ($woIds->isEmpty()) {
            return ['nilai' => 0, 'wo' => ['estimasi' => 0], 'fwo' => ['estimasi' => 0]];
        }

        $woEst = (int) DB::table('sq_wo_budget_items as i')
            ->join('sq_wo_budgets as b', 'b.id_sq_budget', '=', 'i.id_sq_budget')
            ->whereIn('b.id_sq_wo', $woIds)->sum('i.nominal_budget');
        $fwoEst = (int) DB::table('sq_fwo_budget_items as i')
            ->join('sq_fwo_budgets as b', 'b.id_sq_budget', '=', 'i.id_sq_budget')
            ->join('sq_fieldworks as f', 'f.id_sq_fwo', '=', 'b.id_sq_fwo')
            ->whereIn('f.id_sq_wo', $woIds)->sum('i.nominal_budget');

        $nilai = (int) DB::table('sq_boq')->whereIn('id_sq_wo', $woIds)
                ->selectRaw('COALESCE(SUM(GREATEST(0, CAST(qty * harga AS SIGNED) - CAST(discount AS SIGNED))), 0) as t')->value('t')
            + (int) DB::table('sq_boq_tambahan')->whereIn('id_sq_wo', $woIds)
                ->selectRaw('COALESCE(SUM(COALESCE(qty, 0) * COALESCE(harga, 0)), 0) as t')->value('t');

        return [
            'nilai' => $nilai,
            'wo'    => ['estimasi' => $woEst],
            'fwo'   => ['estimasi' => $fwoEst],
        ];
    }
}
