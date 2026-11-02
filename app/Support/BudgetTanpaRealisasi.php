<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * "Tanpa realisasi" pada item Budget Plan (WO, FWO, SQ WO, SQ FWO) — per-qty, sejak 2026-11-02.
 *
 * Kasus: sewa alat qty 4 direncanakan di SQ dan ditagihkan ke customer, tapi saat pelaksanaan 2 unit pakai milik
 * sendiri sehingga tidak ada uang keluar. Item tetap 1 baris (qty 4); `qty_tanpa_realisasi` = 2 → yang direalisasikan
 * tinggal 2 unit. Diisi di modal Plan (kolom "Qty Tanpa Realisasi" + "Alasan") dan boleh diubah kapan saja selama plan
 * belum selesai (aktual bisa fleksibel).
 *
 * `nominal_tanpa_realisasi` = nilai bagian itu (nominal × qty tanpa / qty), diturunkan server tiap simpan.
 * Di SO: pembanding estimasi-vs-realisasi = nominal_budget − nominal_tanpa_realisasi (total Budget Plan tetap penuh);
 * bila seluruh qty tanpa realisasi ("penuh"), item tidak bisa diberi realisasi & tidak menghalangi Selesaikan Plan.
 * Di SQ hanya dicatat dan dibawa ke SO (Terbitkan SO, revisi SQ, Clone) — tidak mengubah hitungan SQ apa pun.
 *
 * Yang boleh mengisi: PIC Order SQ/SO terkait (SQ & SO punya `pic_order`), atau user dengan Aksi Khusus
 * `budget-tanpa-realisasi-semua`. User lain yang menyimpan plan tidak mengubah isian ini (nilai lama dipertahankan).
 * Jenis: 'wo', 'fwo', 'sq-wo', 'sq-fwo'.
 */
class BudgetTanpaRealisasi
{
    /** Kondisi SQL: seluruh qty item tanpa realisasi (kolom tanpa alias). */
    public const PENUH_SQL = 'qty_tanpa_realisasi > 0 AND qty_tanpa_realisasi >= qty';

    /** [pic_order users.id | null, no dokumen (SO/SQ)] dari induk WO/FWO/SQ WO/SQ FWO. */
    public static function acuan(string $jenis, int $parentId): array
    {
        switch ($jenis) {
            case 'wo':
                $idSo = DB::table('work_orders')->where('id_wo', $parentId)->value('id_so');
                break;
            case 'fwo':
                $idSo = DB::table('fieldworks as f')->join('work_orders as w', 'w.id_wo', '=', 'f.id_wo')
                    ->where('f.id_fwo', $parentId)->value('w.id_so');
                break;
            case 'sq-wo':
                $idSq = DB::table('sq_work_orders')->where('id_sq_wo', $parentId)->value('id_sq');
                break;
            case 'sq-fwo':
                $idSq = DB::table('sq_fieldworks as f')->join('sq_work_orders as w', 'w.id_sq_wo', '=', 'f.id_sq_wo')
                    ->where('f.id_sq_fwo', $parentId)->value('w.id_sq');
                break;
        }

        if (isset($idSo)) {
            $so = $idSo ? DB::table('sales_orders')->where('id_so', $idSo)->first(['pic_order', 'no_so']) : null;
            return [$so->pic_order ?? null, $so->no_so ?? 'SO'];
        }
        $sq = !empty($idSq) ? DB::table('sales_quotations')->where('id_sq', $idSq)->first(['pic_order', 'no_sq']) : null;
        return [$sq->pic_order ?? null, $sq->no_sq ?? 'SQ'];
    }

    /** Apakah user yang login boleh mengisi "Tanpa realisasi" untuk dokumen induk ini. */
    public static function boleh(string $jenis, int $parentId): bool
    {
        if (!Auth::check()) return false;
        [$pic] = self::acuan($jenis, $parentId);
        if ($pic && (int) $pic === (int) Auth::id()) return true;
        return userCan('budget-tanpa-realisasi-semua', 'can_update');
    }

    /**
     * Kolom "Tanpa realisasi" untuk 1 item yang akan disimpan (insert/update), dari input modal.
     *
     * @param array       $item     baris item dari request
     * @param array       $fields   hasil BudgetItemInput::fields($item) (qty & nominal_budget sudah dinormalisasi)
     * @param object|null $existing baris item lama (update) atau null (item baru)
     * @throws ValidationException bila tidak valid (jadi respons 422)
     */
    public static function kolom(string $jenis, int $parentId, array $item, array $fields, ?object $existing): array
    {
        $qty     = (float) $fields['qty'];
        $nominal = (int) $fields['nominal_budget'];
        $ca      = !empty($item['is_cash_advance']);
        $nama    = !empty($item['id_account']) ? (DB::table('budget_accounts')->where('id_account', $item['id_account'])->value('nama') ?: 'item') : 'item';

        $lamaQty    = $existing ? round((float) $existing->qty_tanpa_realisasi, 2) : 0.0;
        $lamaAlasan = $existing->alasan_tanpa_realisasi ?? null;
        $pic        = self::boleh($jenis, $parentId);

        if ($pic) {
            $qt     = round(max(0, (float) ($item['qty_tanpa_realisasi'] ?? 0)), 2);
            $alasan = trim((string) ($item['alasan_tanpa_realisasi'] ?? ''));
            if ($qt > 0 && mb_strlen($alasan) < 3) {
                self::tolak("Item \"{$nama}\": alasan \"Tanpa realisasi\" wajib diisi (minimal 3 karakter).");
            }
        } else {
            // Bukan PIC Order: isian lama dipertahankan apa adanya
            $qt     = $lamaQty;
            $alasan = (string) $lamaAlasan;
        }

        if ($qt > $qty + 0.0001) {
            self::tolak("Item \"{$nama}\": Qty Tanpa Realisasi ({$qt}) tidak boleh melebihi Qty ({$qty})."
                . ($pic ? '' : ' Minta PIC Order menyesuaikan Qty Tanpa Realisasi dulu.'));
        }
        if ($qt > 0 && $ca) {
            self::tolak("Item \"{$nama}\": Cash Advance tidak bisa dipakai bersama \"Tanpa realisasi\".");
        }

        if ($qt <= 0) {
            return [
                'tanpa_realisasi' => 0, 'qty_tanpa_realisasi' => 0, 'nominal_tanpa_realisasi' => 0,
                'alasan_tanpa_realisasi' => null, 'ditandai_oleh' => null, 'ditandai_at' => null,
            ];
        }

        $berubah = !$existing || abs($qt - $lamaQty) > 0.0001 || $alasan !== (string) $lamaAlasan;

        return [
            'tanpa_realisasi'         => 1,
            'qty_tanpa_realisasi'     => $qt,
            'nominal_tanpa_realisasi' => $qt >= $qty - 0.0001 ? $nominal : (int) round($nominal * $qt / $qty),
            'alasan_tanpa_realisasi'  => mb_substr($alasan, 0, 255),
            'ditandai_oleh'           => $berubah ? Auth::id() : $existing->ditandai_oleh,
            'ditandai_at'             => $berubah ? now() : $existing->ditandai_at,
        ];
    }

    /** Teks ringkas untuk audit: " [Tanpa Realisasi 2 dari 4: alasan]" (kosong bila tidak ada). */
    public static function teks($item): string
    {
        if ((float) ($item->qty_tanpa_realisasi ?? 0) <= 0) return '';
        $f = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
        return ' [Tanpa Realisasi ' . $f($item->qty_tanpa_realisasi) . ' dari ' . $f($item->qty ?? 1)
            . ($item->alasan_tanpa_realisasi ? ': ' . $item->alasan_tanpa_realisasi : '') . ']';
    }

    private static function tolak(string $pesan): never
    {
        throw ValidationException::withMessages(['items' => [$pesan]]);
    }
}
