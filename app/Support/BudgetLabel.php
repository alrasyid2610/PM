<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Master Label Budget (tabel `budget_label`, menu "Master Label Budget").
 *
 * Label Budget Plan (WO, FWO, SQ WO, SQ FWO) hanya boleh dipilih dari master (sejak 2026-11-19). Plan menyimpan
 * `id_budget_label` + salinan teks di kolom `label` (notifikasi, nama file PDF, riwayat membacanya langsung).
 * Satu induk (WO/FWO/SQ WO/SQ FWO) tidak boleh punya dua plan berlabel sama.
 * Plan lama tanpa id (label teks bebas) tetap sah selama labelnya tidak diganti.
 */
class BudgetLabel
{
    /** Tabel plan => kolom induknya, dipakai untuk hitung pemakaian & cek unik. */
    public const PLAN_TABLES = [
        'wo_budgets'     => 'id_wo',
        'fwo_budgets'    => 'id_fwo',
        'sq_wo_budgets'  => 'id_sq_wo',
        'sq_fwo_budgets' => 'id_sq_fwo',
    ];

    /** Label AKTIF urut `urutan` lalu nama: [['id','text'], ...] (untuk select di modal). */
    public static function daftar(): array
    {
        return DB::table('budget_label')
            ->where('is_aktif', 1)
            ->orderBy('urutan')->orderBy('nama')
            ->get(['id_budget_label', 'nama'])
            ->map(fn ($m) => ['id' => (int) $m->id_budget_label, 'text' => $m->nama])
            ->all();
    }

    /** Jumlah plan (semua modul) yang memakai 1 label master. */
    public static function pemakaian(int $id): int
    {
        $n = 0;
        foreach (array_keys(self::PLAN_TABLES) as $tbl) {
            $q = DB::table($tbl)->where('id_budget_label', $id);
            if (in_array($tbl, ['wo_budgets', 'fwo_budgets'], true)) $q->whereNull('deleted_at');
            $n += (int) $q->count();
        }
        return $n;
    }

    /**
     * Tentukan label untuk plan yang disimpan. Mengembalikan [idLabel|null, teksLabel] atau melempar array pesan error
     * lewat `['error' => '...']`.
     *
     * - `$tabel`/`$idInduk`: tabel plan & id induknya (untuk cek unik).
     * - `$plan`: baris plan saat ini (update) atau null (store).
     * Aturan: id harus ada di master & aktif (kecuali id yang sudah dipakai plan ini). Tanpa id hanya boleh bila plan lama
     * (id kosong) dan teks label TIDAK diubah.
     */
    public static function tentukan(Request $request, string $tabel, int $idInduk, ?object $plan = null, ?string $kolomPk = null): array
    {
        $id = $request->input('id_budget_label');

        if ($id === null || $id === '') {
            // Plan lama (label teks bebas) boleh disimpan selama labelnya tidak diganti
            if ($plan && empty($plan->id_budget_label) && trim((string) $request->input('label')) === (string) $plan->label) {
                return [null, (string) $plan->label];
            }
            return ['error' => 'Label budget harus dipilih dari Master Label Budget. Minta admin menambahkannya di menu Master Label Budget bila belum ada.'];
        }

        $m = DB::table('budget_label')->where('id_budget_label', (int) $id)->first();
        if (!$m || (!$m->is_aktif && (int) $m->id_budget_label !== (int) ($plan->id_budget_label ?? 0))) {
            return ['error' => 'Label budget tidak ditemukan atau sudah nonaktif. Pilih label lain dari Master Label Budget.'];
        }

        // Unik per induk
        $kolomInduk = self::PLAN_TABLES[$tabel];
        $dup = DB::table($tabel)->where($kolomInduk, $idInduk)->where('id_budget_label', $m->id_budget_label);
        if (in_array($tabel, ['wo_budgets', 'fwo_budgets'], true)) $dup->whereNull('deleted_at');
        if ($plan && $kolomPk) $dup->where($kolomPk, '!=', $plan->{$kolomPk});
        if ($dup->exists()) {
            return ['error' => "Label \"{$m->nama}\" sudah dipakai plan lain di data ini. Satu plan per label — pilih label lain atau ubah plan yang sudah ada."];
        }

        return [(int) $m->id_budget_label, $m->nama];
    }
}
