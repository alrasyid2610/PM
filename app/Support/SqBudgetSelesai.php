<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Penanda "PIC Order sudah mengisi/mengecek Budget SQ ini" (boleh Budget kosong — dipakai juga untuk
 * SQ yang memang tidak butuh Budget, mis. sample diantar langsung ke kantor). Satu penanda untuk
 * SELURUH SQ (semua WO & FWO di dalamnya), bukan per WO/FWO (keputusan user 2026-11-18).
 *
 * Dipakai sebagai syarat wajib Ajukan Approval (SalesQuotationController::syaratFinalkan()) dan
 * memicu notifikasi `sq.budget_selesai` ke pembuat SQ (role pic_input). Reset otomatis ke 'belum'
 * tiap ada perubahan struktural pada Budget WO atau Budget FWO manapun di SQ itu — dipanggil dari
 * SqWoBudgetController & SqFwoBudgetController (store/update/destroy).
 */
class SqBudgetSelesai
{
    public const BELUM = 'belum';
    public const SELESAI = 'selesai';

    public static function idSqByWo($idSqWo): ?int
    {
        $id = DB::table('sq_work_orders')->where('id_sq_wo', $idSqWo)->value('id_sq');
        return $id ? (int) $id : null;
    }

    public static function idSqByFwo($idSqFwo): ?int
    {
        $id = DB::table('sq_fieldworks as f')
            ->join('sq_work_orders as w', 'w.id_sq_wo', '=', 'f.id_sq_wo')
            ->where('f.id_sq_fwo', $idSqFwo)
            ->value('w.id_sq');
        return $id ? (int) $id : null;
    }

    /** Dipanggil tiap ada perubahan Budget WO (store/update/destroy plan). */
    public static function resetByWo($idSqWo): void
    {
        if ($idSq = self::idSqByWo($idSqWo)) self::reset($idSq);
    }

    /** Dipanggil tiap ada perubahan Budget FWO (store/update/destroy plan). */
    public static function resetByFwo($idSqFwo): void
    {
        if ($idSq = self::idSqByFwo($idSqFwo)) self::reset($idSq);
    }

    private static function reset(int $idSq): void
    {
        DB::table('sales_quotations')
            ->where('id_sq', $idSq)
            ->where('status_budget', self::SELESAI)
            ->update([
                'status_budget' => self::BELUM,
                'budget_selesai_oleh' => null,
                'budget_selesai_at' => null,
                'updated_at' => now(),
            ]);
    }

    /** Hanya PIC Order SQ ini, selama SQ masih Draft. */
    public static function bisaTandai(array $sq): bool
    {
        return ($sq['status'] ?? null) === 'draft'
            && (int) ($sq['pic_order'] ?? 0) === (int) (auth()->id() ?? 0);
    }
}
