<?php

namespace App\Http\Controllers;

use App\Support\DataScope;
use App\Support\ListFilter\Sets\KelengkapanFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

/**
 * Report "Kelengkapan SO & SQ" (sejak 2026-11-16) — read-only. Satu baris per SO / SQ yang belum dihapus,
 * dengan penanda Y/N: sudah punya WO, FWO, Budget WO, Budget FWO. "Ada" = minimal 1 data aktif di bawahnya
 * (data yang sudah dihapus tidak dihitung; tabel SQ memang tidak soft delete).
 * Mengikuti segregasi data: SO/SQ yang tidak boleh dilihat user tidak muncul (lihat DataScope).
 */
class ReportKelengkapanController extends Controller
{
    private const UTF = 'utf8mb4_unicode_ci';

    public function index()
    {
        return view('report-kelengkapan.index');
    }

    public function data(Request $request)
    {
        $set     = new KelengkapanFilters();
        $filters = $set->normalize($request->input('filters'));

        $query = DB::query()->fromSub($this->unionQuery(), 'r')
            ->select('r.*')
            ->orderBy('r.jenis')->orderBy('r.nomor');
        $set->apply($query, $filters);

        // Kolom tampil otomatis dari key JSON (kunci `id_*` disembunyikan tapi dipakai untuk link nomor).
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('nomor', function ($row) {
                $url = $row->jenis === 'SO'
                    ? url('sales-orders') . '?open=' . $row->id_dokumen
                    : url('sales-quotations') . '?open=' . $row->id_dokumen;

                return '<a href="' . e($url) . '" class="pm-link-record">' . e($row->nomor) . '</a>';
            })
            ->editColumn('ada_wo', fn ($row) => $this->yn($row->ada_wo))
            ->editColumn('ada_fwo', fn ($row) => $this->yn($row->ada_fwo))
            ->editColumn('ada_budget_wo', fn ($row) => $this->yn($row->ada_budget_wo))
            ->editColumn('ada_budget_fwo', fn ($row) => $this->yn($row->ada_budget_fwo))
            ->rawColumns(['nomor', 'ada_wo', 'ada_fwo', 'ada_budget_wo', 'ada_budget_fwo'])
            ->make(true);
    }

    private function yn(string $v): string
    {
        return $v === 'Y'
            ? '<span class="pm-badge pm-badge--green">Y</span>'
            : '<span class="pm-badge pm-badge--red">N</span>';
    }

    /** SO + SQ digabung (UNION ALL), nilai Y/N dihitung dengan EXISTS. */
    private function unionQuery()
    {
        $c = self::UTF;

        $so = DB::table('sales_orders as s')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 's.id_pelanggan')
            ->leftJoin('business_relation_sites as brs', 'brs.id_site', '=', 's.id_site_pelanggan')
            ->leftJoin('users as ui', 'ui.id', '=', 's.pic_input')
            ->leftJoin('users as uo', 'uo.id', '=', 's.pic_order')
            ->whereNull('s.deleted_at')
            ->tap(fn ($q) => DataScope::so($q, 's'))
            ->selectRaw("'SO' as jenis")
            ->selectRaw('s.id_so as id_dokumen')
            ->selectRaw("s.no_so COLLATE {$c} as nomor")
            ->selectRaw('s.created_at as tanggal_buat')
            ->selectRaw("br.nama COLLATE {$c} as pelanggan")
            ->selectRaw("brs.nama_lokasi COLLATE {$c} as site")
            ->selectRaw('ui.name as pic_input')
            ->selectRaw('uo.name as pic_order')
            ->selectRaw("IF(EXISTS (SELECT 1 FROM work_orders w
                        WHERE w.id_so = s.id_so AND w.deleted_at IS NULL), 'Y', 'N') as ada_wo")
            ->selectRaw("IF(EXISTS (SELECT 1 FROM work_orders w
                        JOIN fieldworks f ON f.id_wo = w.id_wo AND f.deleted_at IS NULL
                        WHERE w.id_so = s.id_so AND w.deleted_at IS NULL), 'Y', 'N') as ada_fwo")
            ->selectRaw("IF(EXISTS (SELECT 1 FROM work_orders w
                        JOIN wo_budgets b ON b.id_wo = w.id_wo AND b.deleted_at IS NULL
                        WHERE w.id_so = s.id_so AND w.deleted_at IS NULL), 'Y', 'N') as ada_budget_wo")
            ->selectRaw("IF(EXISTS (SELECT 1 FROM work_orders w
                        JOIN fieldworks f ON f.id_wo = w.id_wo AND f.deleted_at IS NULL
                        JOIN fwo_budgets b ON b.id_fwo = f.id_fwo AND b.deleted_at IS NULL
                        WHERE w.id_so = s.id_so AND w.deleted_at IS NULL), 'Y', 'N') as ada_budget_fwo");

        $sq = DB::table('sales_quotations as q')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'q.id_pelanggan')
            ->leftJoin('business_relation_sites as brs', 'brs.id_site', '=', 'q.id_site_pelanggan')
            ->leftJoin('users as ui', 'ui.id', '=', 'q.pic_input')
            ->leftJoin('users as uo', 'uo.id', '=', 'q.pic_order')
            ->whereNull('q.deleted_at')
            ->tap(fn ($q) => DataScope::sq($q, 'q'))
            ->selectRaw("'SQ' as jenis")
            ->selectRaw('q.id_sq as id_dokumen')
            ->selectRaw("q.no_sq COLLATE {$c} as nomor")
            ->selectRaw('q.created_at as tanggal_buat')
            ->selectRaw("br.nama COLLATE {$c} as pelanggan")
            ->selectRaw("brs.nama_lokasi COLLATE {$c} as site")
            ->selectRaw('ui.name as pic_input')
            ->selectRaw('uo.name as pic_order')
            ->selectRaw("IF(EXISTS (SELECT 1 FROM sq_work_orders w
                        WHERE w.id_sq = q.id_sq), 'Y', 'N') as ada_wo")
            ->selectRaw("IF(EXISTS (SELECT 1 FROM sq_work_orders w
                        JOIN sq_fieldworks f ON f.id_sq_wo = w.id_sq_wo
                        WHERE w.id_sq = q.id_sq), 'Y', 'N') as ada_fwo")
            ->selectRaw("IF(EXISTS (SELECT 1 FROM sq_work_orders w
                        JOIN sq_wo_budgets b ON b.id_sq_wo = w.id_sq_wo
                        WHERE w.id_sq = q.id_sq), 'Y', 'N') as ada_budget_wo")
            ->selectRaw("IF(EXISTS (SELECT 1 FROM sq_work_orders w
                        JOIN sq_fieldworks f ON f.id_sq_wo = w.id_sq_wo
                        JOIN sq_fwo_budgets b ON b.id_sq_fwo = f.id_sq_fwo
                        WHERE w.id_sq = q.id_sq), 'Y', 'N') as ada_budget_fwo");

        return $so->unionAll($sq);
    }
}
