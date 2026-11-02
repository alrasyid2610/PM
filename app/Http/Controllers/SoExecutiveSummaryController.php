<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Spatie\LaravelPdf\Facades\Pdf;

/**
 * Executive Summary SO (internal) — analisa pendapatan vs biaya operasional dari data SO yang berjalan,
 * dengan biaya dalam dua sisi: ESTIMASI (Budget Plan WO + FWO) dan REALISASI (Budget Actual WO + FWO).
 * Padanan Executive Summary SQ (SalesQuotationController::executiveSummaryPdf) yang hanya punya estimasi.
 *
 * Pendapatan bersih = BOQ (setelah discount item) + BOQ Other + BOQ Sampling − Discount SO (persen dihitung saat ini,
 * sama dengan SalesOrderController::soEffectiveDiscount). Semua SO boleh dicetak; selain `completed` dokumen diberi
 * catatan bahwa realisasi belum final. Realisasi = semua baris actual yang tercatat (belum difilter status
 * verifikasi — sama dengan kartu "Nilai Budget" di detail WO).
 */
class SoExecutiveSummaryController extends Controller
{
    public function pdf($id)
    {
        $so = DB::table('sales_orders as so')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'so.id_pelanggan')
            ->leftJoin('entitas as ent', 'ent.id_entitas', '=', 'br.id_entitas')
            ->leftJoin('sales_quotations as sq', 'sq.id_sq', '=', 'so.id_sq')
            ->where('so.id_so', $id)
            ->whereNull('so.deleted_at')
            ->select(['so.*', 'br.nama as nama_pelanggan', 'ent.nama as entitas_pelanggan', 'sq.no_sq', 'sq.revisi as sq_revisi'])
            ->first();

        abort_if(!$so, 404, 'Sales Order tidak ditemukan');
        $so->nama_pelanggan_display = brDisplayName($so->entitas_pelanggan, $so->nama_pelanggan);

        $wos = DB::table('work_orders')->where('id_so', $id)->whereNull('deleted_at')->orderBy('id_wo')->get();
        $woIds = $wos->pluck('id_wo');

        $boq = DB::table('boq')->whereIn('id_wo', $woIds)->whereNull('deleted_at')->get()->groupBy('id_wo');
        $tam = DB::table('boq_tambahan')->whereIn('id_wo', $woIds)->whereNull('deleted_at')->get()->groupBy('id_wo');

        // ── Biaya: item Budget Plan WO + FWO, masing-masing dengan total realisasinya ──
        $woItems = DB::table('wo_budgets as b')
            ->join('wo_budget_items as i', 'i.id_budget', '=', 'b.id_budget')
            ->leftJoin('budget_accounts as a', 'a.id_account', '=', 'i.id_account')
            ->whereIn('b.id_wo', $woIds)->whereNull('b.deleted_at')
            ->select(['b.id_wo', 'i.id_budget_item', 'i.nominal_budget', 'i.is_cash_advance', 'i.nominal_tanpa_realisasi', 'i.id_account', 'a.nama as akun_nama', 'a.kode as akun_kode'])
            ->get();
        $woReal = DB::table('wo_budget_actuals')->whereIn('id_budget_item', $woItems->pluck('id_budget_item'))
            ->selectRaw('id_budget_item, SUM(nominal_actual) as n')->groupBy('id_budget_item')->pluck('n', 'id_budget_item');

        $fwoItems = DB::table('fwo_budgets as b')
            ->join('fieldworks as f', 'f.id_fwo', '=', 'b.id_fwo')
            ->join('fwo_budget_items as i', 'i.id_budget', '=', 'b.id_budget')
            ->leftJoin('budget_accounts as a', 'a.id_account', '=', 'i.id_account')
            ->whereIn('f.id_wo', $woIds)->whereNull('f.deleted_at')->whereNull('b.deleted_at')
            ->select(['f.id_wo', 'i.id_budget_item', 'i.nominal_budget', 'i.is_cash_advance', 'i.nominal_tanpa_realisasi', 'i.id_account', 'a.nama as akun_nama', 'a.kode as akun_kode'])
            ->get();
        $fwoReal = DB::table('fwo_budget_actuals')->whereIn('id_budget_item', $fwoItems->pluck('id_budget_item'))
            ->selectRaw('id_budget_item, SUM(nominal_actual) as n')->groupBy('id_budget_item')->pluck('n', 'id_budget_item');

        // Satukan WO + FWO ke satu bentuk: estimasi (nominal_budget) & realisasi per item
        $items = $woItems->map(fn ($i) => (object) [
            'id_wo' => $i->id_wo, 'id_account' => $i->id_account, 'akun_nama' => $i->akun_nama, 'akun_kode' => $i->akun_kode,
            'ca' => (int) $i->is_cash_advance === 1, 'tanpa' => (int) $i->nominal_tanpa_realisasi, 'est' => (int) $i->nominal_budget - (int) $i->nominal_tanpa_realisasi, 'real' => (int) ($woReal[$i->id_budget_item] ?? 0),
        ])->concat($fwoItems->map(fn ($i) => (object) [
            'id_wo' => $i->id_wo, 'id_account' => $i->id_account, 'akun_nama' => $i->akun_nama, 'akun_kode' => $i->akun_kode,
            'ca' => (int) $i->is_cash_advance === 1, 'tanpa' => (int) $i->nominal_tanpa_realisasi, 'est' => (int) $i->nominal_budget - (int) $i->nominal_tanpa_realisasi, 'real' => (int) ($fwoReal[$i->id_budget_item] ?? 0),
        ]));

        $rows = [];
        $tot = ['boq_gross' => 0, 'boq_disc' => 0, 'other' => 0, 'sampling' => 0, 'est' => 0, 'real' => 0, 'ca_est' => 0, 'ca_real' => 0, 'tanpa' => 0];
        foreach ($wos as $wo) {
            $b = $boq->get($wo->id_wo, collect());
            $gross = (int) $b->sum(fn ($r) => (int) $r->qty * (int) $r->harga);
            $disc = (int) $b->sum(fn ($r) => min((int) $r->discount, (int) $r->qty * (int) $r->harga));
            $t = $tam->get($wo->id_wo, collect());
            $other = (int) $t->where('jenis', 'lainnya')->sum(fn ($r) => (int) $r->qty * (int) $r->harga);
            $sampling = (int) $t->where('jenis', 'sampling')->sum(fn ($r) => (int) $r->qty * (int) $r->harga);

            $wi = $items->where('id_wo', $wo->id_wo);
            $est = (int) $wi->sum('est');
            $real = (int) $wi->sum('real');
            $pendapatan = ($gross - $disc) + $other + $sampling;

            $rows[] = (object) [
                'no' => $wo->no_wo, 'judul' => $wo->judul_pekerjaan, 'status' => $wo->status,
                'pendapatan' => $pendapatan, 'est' => $est, 'real' => $real,
                'margin' => $pendapatan - $real,
                'margin_pct' => $pendapatan > 0 ? ($pendapatan - $real) / $pendapatan * 100 : null,
            ];
            $tot['boq_gross'] += $gross; $tot['boq_disc'] += $disc; $tot['other'] += $other; $tot['sampling'] += $sampling;
            $tot['est'] += $est; $tot['real'] += $real; $tot['tanpa'] += (int) $wi->sum('tanpa');
            $tot['ca_est'] += (int) $wi->where('ca', true)->sum('est');
            $tot['ca_real'] += (int) $wi->where('ca', true)->sum('real');
        }

        $subtotal = ($tot['boq_gross'] - $tot['boq_disc']) + $tot['other'] + $tot['sampling'];
        $discountSo = $so->discount_persen !== null
            ? (int) round($subtotal * (float) $so->discount_persen / 100)
            : (int) ($so->discount ?? 0);
        $pendapatan = max(0, $subtotal - $discountSo);

        $akun = $items->groupBy(fn ($i) => $i->id_account ?: 0)->map(function ($g) {
            $f = $g->first();
            return (object) [
                'nama' => $f->akun_nama ? trim(($f->akun_kode ? $f->akun_kode . ' — ' : '') . $f->akun_nama) : 'Tanpa akun',
                'est' => (int) $g->sum('est'),
                'real' => (int) $g->sum('real'),
            ];
        })->sortByDesc(fn ($a) => max($a->est, $a->real))->values();

        return Pdf::view('pdf.sales-order.executive-summary', [
            'so' => $so,
            'rows' => collect($rows),
            'tot' => $tot,
            'subtotal' => $subtotal,
            'discountSo' => $discountSo,
            'pendapatan' => $pendapatan,
            'biayaEst' => $tot['est'],
            'biayaReal' => $tot['real'],
            'marginEst' => $pendapatan - $tot['est'],
            'marginReal' => $pendapatan - $tot['real'],
            'akun' => $akun,
            'selesai' => $so->status === 'completed',
        ])
            // Kop sama dengan Executive Summary SQ & FWO
            ->headerView('pdf.layouts.sections.header')
            ->margins(top: 30, right: 0, bottom: 15, left: 0)
            ->format('a4')
            ->name("Executive-Summary-{$so->no_so}.pdf");
    }
}
