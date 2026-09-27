<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Penguncian Sales Quotation: hanya SQ berstatus Draft yang boleh diubah
 * (header, WO, BOQ, BOQ Other/Sampling, Budget). Final/Completed/Cancel
 * terkunci — perubahan lewat revisi. Tiap method mengembalikan JsonResponse
 * 423 kalau terkunci, atau null kalau boleh lanjut.
 */
class SqLock
{
    public static function bySq($idSq): ?JsonResponse
    {
        $status = DB::table('sales_quotations')->where('id_sq', $idSq)->value('status');
        return self::check($status);
    }

    public static function byWo($idSqWo): ?JsonResponse
    {
        $status = DB::table('sq_work_orders as w')
            ->join('sales_quotations as sq', 'sq.id_sq', '=', 'w.id_sq')
            ->where('w.id_sq_wo', $idSqWo)
            ->value('sq.status');
        return self::check($status);
    }

    public static function byTambahan($id): ?JsonResponse
    {
        $idWo = DB::table('sq_boq_tambahan')->where('id_sq_boq_tambahan', $id)->value('id_sq_wo');
        return $idWo ? self::byWo($idWo) : null;
    }

    public static function byBudget($id): ?JsonResponse
    {
        $idWo = DB::table('sq_wo_budgets')->where('id_sq_budget', $id)->value('id_sq_wo');
        return $idWo ? self::byWo($idWo) : null;
    }

    /** SQ FWO (Fase 3) — sama seperti byWo(), naik lewat sq_fieldworks → sq_work_orders → sales_quotations. */
    public static function byFwo($idSqFwo): ?JsonResponse
    {
        $status = DB::table('sq_fieldworks as f')
            ->join('sq_work_orders as w', 'w.id_sq_wo', '=', 'f.id_sq_wo')
            ->join('sales_quotations as sq', 'sq.id_sq', '=', 'w.id_sq')
            ->where('f.id_sq_fwo', $idSqFwo)
            ->value('sq.status');
        return self::check($status);
    }

    public static function byFwoBoq($id): ?JsonResponse
    {
        $idFwo = DB::table('sq_fieldwork_boq')->where('id_sq_fwo_boq', $id)->value('id_sq_fwo');
        return $idFwo ? self::byFwo($idFwo) : null;
    }

    public static function byFwoBudget($id): ?JsonResponse
    {
        $idFwo = DB::table('sq_fwo_budgets')->where('id_sq_budget', $id)->value('id_sq_fwo');
        return $idFwo ? self::byFwo($idFwo) : null;
    }

    private static function check(?string $status): ?JsonResponse
    {
        if ($status === null || $status === 'draft') return null;

        $label = ['final' => 'Final', 'completed' => 'Completed', 'cancel' => 'Cancel'][$status] ?? $status;
        return response()->json([
            'message' => "Sales Quotation berstatus {$label} dan tidak bisa diubah. Hanya SQ berstatus Draft yang bisa diedit.",
        ], 423);
    }
}
