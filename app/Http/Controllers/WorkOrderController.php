<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;


class WorkOrderController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string
    {
        return 'work_orders';
    }

    protected function auditExcludeFields(): array
    {
        return ['updated_at', 'created_at', 'id_wo'];
    }

    public function index()
    {
        return view('work-order.index', [
            'title' => 'Work Orders',
        ]);
    }


    public function store(Request $request)
    {

        // ==========================
        // VALIDATION
        // ==========================
        $request->validate([
            'id_sales_order' => 'required|integer',
            // 'tanggal_so' => 'required|date',
            // 'tanggal_mulai' => 'required|date',
            // 'tanggal_selesai' => 'required|date',
            'judul_order' => 'required|string',
            'id_pelanggan' => 'required|string',
            'id_site_pelanggan' => 'required|string',
        ]);

        // ==========================
        // INSERT
        // ==========================
        $no_wo = $this->generateNoWo();

        $id = DB::table('work_orders')->insertGetId([
            'no_wo' => $no_wo,
            'id_so' => $request->id_sales_order,
            'id_pelanggan_pekerjaan' => $request->id_pelanggan,
            'id_site_pelanggan_pekerjaan' => $request->id_site_pelanggan,
            'id_pic_pelanggan_pekerjaan' => $request->pic_pekerjaan,
            'judul_pekerjaan' => $request->judul_order,
            'keterangan' => $request->keterangan,
            'id_period' => $request->id_period ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $after = DB::table('work_orders')->where('id_wo', $id)->get()->toJson();
        saveAudit('work_orders', $id, 'Create', '', $after);

        return response()->json([
            'status' => 'success',
            'message' => 'Sales Order berhasil dibuat',
            'id_so' => $id
        ]);
    }

    public function create(Request $request)
    {
        if (!$request->filled('id_so')) {
            return redirect()->route('work-orders.index');
        }

        return view('work-order.create', [
            'title' => 'Create Work Order',
        ]);
    }

    public function update(Request $request, $id)
    {
        // dd($request->all(), $id, 'poke');

        // ==========================
        // VALIDATION
        // ==========================
        $request->validate([
            'judul_order' => 'required|string',
            'id_pelanggan' => 'required|string',
            'id_site_pelanggan' => 'required|string',
        ]);

        // ==========================
        // UPDATE
        // ==========================
        $before = DB::table('work_orders')->where('id_wo', $id)->get()->toJson();

        try {
            DB::beginTransaction();

            DB::table('work_orders')
                ->where('id_wo', $id)
                ->update([
                    'id_pelanggan_pekerjaan'      => $request->id_pelanggan,
                    'id_site_pelanggan_pekerjaan' => $request->id_site_pelanggan,
                    'id_pic_pelanggan_pekerjaan'  => $request->pic_pekerjaan,
                    'judul_pekerjaan'             => $request->judul_order,
                    'keterangan'                  => $request->keterangan,
                    'updated_at'                  => now(),
                ]);

            $after = DB::table('work_orders')->where('id_wo', $id)->get()->toJson();
            saveAudit('work_orders', $id, 'update', $before, $after);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Work Order berhasil diperbarui',
        ]);
    }


    public function bySo($id_so)
    {
        $data = DB::table('work_orders as wo')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'wo.id_pelanggan_pekerjaan')
            ->leftJoin('business_relation_sites as brs', 'brs.id_site', '=', 'wo.id_site_pelanggan_pekerjaan')
            ->leftJoin('business_relation_contacts as brc', 'brc.id_contact', '=', 'wo.id_pic_pelanggan_pekerjaan')
            ->where('wo.id_so', $id_so)
            ->select([
                'wo.id_wo',
                'wo.no_wo',
                'wo.judul_pekerjaan',
                'br.nama as nama_pelanggan',
                'brs.nama_lokasi as nama_site',
                'brc.nama_pic as nama_pic_pekerjaan',
                'wo.keterangan',
            ])
            ->get();

        return response()->json($data);
    }

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('work_orders')
            ->whereNull('deleted_at')
            ->where('no_wo', 'like', "%{$search}%")
            ->get();

        return response()->json(
            $data->map(function ($item) {
                return [
                    'id'    => $item->id_wo,         // HARUS 'id'
                    'text'  => $item->no_wo . " - " . $item->judul_pekerjaan,         // HARUS 'text' agar muncul di dropdown
                    'judul' => $item->judul_pekerjaan,   // Data tambahan (opsional)
                    'id_so' => $item->id_so,   // Data tambahan (opsional)
                    'id_pelanggan_pekerjaan' => $item->id_pelanggan_pekerjaan,   // Data tambahan (opsional)
                    'id_site_pelanggan_pekerjaan' => $item->id_site_pelanggan_pekerjaan,   // Data tambahan (opsional)
                    'keterangan' => $item->keterangan,   // Data tambahan (opsional)
                ];
            })
        );
    }

    public function data(Request $request)
    {
        // dd($request->all(), 'poke');
        // =========================
        // QUERY DASAR (JOIN BR + BRS)
        // =========================
        $query = DB::table('work_orders as wo')
            ->leftJoin('sales_orders as s', 'wo.id_so', '=', 's.id_so')
            ->leftJoin('business_relations as br', 'wo.id_pelanggan_pekerjaan', '=', 'br.id_br')
            ->leftJoin('business_relation_sites as brs', 'wo.id_site_pelanggan_pekerjaan', '=', 'brs.id_site')
            ->whereNull('wo.deleted_at')
            ->select([
                'wo.id_wo',
                's.id_so',
                's.no_so',
                'wo.no_wo',
                's.judul_order',
                'wo.id_pelanggan_pekerjaan',
                'wo.id_site_pelanggan_pekerjaan',
                'br.nama as Pelanggan',
                'brs.nama_lokasi as Site Pelanggan',
                's.tanggal_so',
                's.tanggal_mulai',
                's.tanggal_selesai',
                'wo.keterangan',
                's.created_at',
            ]);

        // dd($query->get());

        $data =  DataTables::of($query)
            ->addIndexColumn()
            ->make(true);

        $response = $data->getData(true);

        if (!isset($response['data'][0])) {
            $header = ['Result'];
        } else {
            $header = array_keys((array) $response['data'][0]);
        }

        return response()->json([
            'data' => $response['data'],
            'header' => $header
        ]);
    }


    public function destroy($id)
    {
        $before = DB::table('work_orders')->where('id_wo', $id)->get()->toJson();
        DB::table('work_orders')->where('id_wo', $id)->update(['deleted_at' => now()]);
        $after = DB::table('work_orders')->where('id_wo', $id)->get()->toJson();
        saveAudit('work_orders', $id, 'delete', $before, $after);
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function duplicate(int $id)
    {
        $source = DB::table('work_orders')->where('id_wo', $id)->first();

        if (!$source) {
            return response()->json(['message' => 'Work Order tidak ditemukan'], 404);
        }

        $no_wo = $this->generateNoWo();

        $newId = DB::table('work_orders')->insertGetId([
            'no_wo'                       => $no_wo,
            'id_so'                       => $source->id_so,
            'id_pelanggan_pekerjaan'      => $source->id_pelanggan_pekerjaan,
            'id_site_pelanggan_pekerjaan' => $source->id_site_pelanggan_pekerjaan,
            'id_pic_pelanggan_pekerjaan'  => $source->id_pic_pelanggan_pekerjaan,
            'judul_pekerjaan'             => $source->judul_pekerjaan,
            'keterangan'                  => $source->keterangan,
            'id_period'                   => $source->id_period,
            'created_at'                  => now(),
            'updated_at'                  => now(),
        ]);

        $after = DB::table('work_orders')->where('id_wo', $newId)->get()->toJson();
        saveAudit('work_orders', $newId, 'create', null, $after);

        return response()->json([
            'success' => true,
            'message' => 'Work Order berhasil disalin',
            'no_wo'   => $no_wo,
            'id_wo'   => $newId,
        ]);
    }

    private function generateNoWo()
    {
        $year = now()->format('y');
        $month = now()->format('m');

        $prefix = "WO-{$year}-";

        $latest = DB::table('work_orders')
            ->orderByDesc('created_at')
            ->first();

        if (!$latest) {
            return $prefix . '0001';
        }

        $number = (int) explode('-', $latest->no_wo)[2] + 1;
        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }


    public function detail($id)
    {
        $wo = DB::table('work_orders as wo')
            ->leftJoin('sales_orders as s', 'wo.id_so', '=', 's.id_so')
            ->leftJoin('business_relations as br', 'wo.id_pelanggan_pekerjaan', '=', 'br.id_br')
            ->leftJoin('business_relation_sites as brs', 'wo.id_site_pelanggan_pekerjaan', '=', 'brs.id_site')
            ->select([
                'wo.id_wo',
                's.id_so',
                's.tanggal_so',
                's.tanggal_mulai',
                's.tanggal_selesai',
                'wo.id_pelanggan_pekerjaan',
                'wo.id_site_pelanggan_pekerjaan',
                's.no_po',
                's.tanggal_po',
                'wo.no_wo',
                'wo.judul_pekerjaan',
                's.tidak_ada_po',
                's.no_so',
                'br.nama as Pelanggan',
                'brs.nama_lokasi as Site Pelanggan',
                's.judul_order',
                'wo.keterangan',
                's.created_at',
            ])
            ->where('wo.id_wo', $id)
            ->whereNull('wo.deleted_at')
            ->first();

        if (!$wo) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work Order tidak ditemukan'
            ], 404);
        }

        return response()->json($wo);
    }

    public function assignPeriod(Request $request, $id)
    {
        DB::table('work_orders')->where('id_wo', $id)->update([
            'id_period'  => $request->id_period ?: null,
            'updated_at' => now(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Period WO berhasil diperbarui']);
    }

    public function boqProgress(int $id)
    {
        $boqSections = DB::table('boq as b')
            ->leftJoin('testing_points as tp', 'b.id_testing_point', '=', 'tp.id_testing_point')
            ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
            ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
            ->where('b.id_wo', $id)
            ->select([
                'b.id_boq',
                DB::raw("TRIM(CONCAT_WS(' ', NULLIF(tms.judul_indonesia,''), NULLIF(ts.nomor,''), NULLIF(tp.nama,''))) as point_name"),
                'b.qty as boq_qty',
                'b.satuan',
                'b.harga',
            ])
            ->get();

        $boqIds = $boqSections->pluck('id_boq');

        $fwoQtyByBoq = $boqIds->isNotEmpty()
            ? DB::table('fieldwork_boq')
            ->whereIn('id_boq', $boqIds)
            ->selectRaw('id_boq, SUM(COALESCE(qty, 0)) as fwo_qty')
            ->groupBy('id_boq')
            ->pluck('fwo_qty', 'id_boq')
            : collect();

        $totalBoqQty    = (int) $boqSections->sum('boq_qty');
        $totalFwoQty    = (int) $boqSections->sum(fn($s) => (int)($fwoQtyByBoq[$s->id_boq] ?? 0));
        $pct            = $totalBoqQty > 0 ? round($totalFwoQty / $totalBoqQty * 100) : 0;
        $totalBoqAmount = (int) $boqSections->sum(fn($s) => (int)($s->boq_qty ?? 0) * (int)($s->harga ?? 0));

        // FWO contributions per BOQ item
        $fwoDetailsByBoq = $boqIds->isNotEmpty()
            ? DB::table('fieldwork_boq as fb')
            ->join('fieldworks as fw', 'fw.id_fwo', '=', 'fb.id_fwo')
            ->whereIn('fb.id_boq', $boqIds)
            ->select(['fb.id_boq', 'fw.id_fwo', 'fw.no_fwo', 'fw.tanggal_mulai', 'fb.qty'])
            ->orderBy('fw.id_fwo')
            ->get()
            ->groupBy('id_boq')
            : collect();

        // All FWOs linked to this WO
        $allFwos = DB::table('fieldworks')
            ->where('id_wo', $id)
            ->whereNull('deleted_at')
            ->select(['id_fwo', 'no_fwo', 'judul_pekerjaan', 'tanggal_mulai', 'tanggal_selesai'])
            ->orderBy('id_fwo')
            ->get();

        return response()->json([
            'total_boq_qty'    => $totalBoqQty,
            'total_fwo_qty'    => $totalFwoQty,
            'progress_pct'     => $pct,
            'total_boq_amount' => $totalBoqAmount,
            'fwos'             => $allFwos->map(fn($f) => [
                'id_fwo'          => $f->id_fwo,
                'no_fwo'          => $f->no_fwo,
                'judul_pekerjaan' => $f->judul_pekerjaan,
                'tanggal_mulai'   => $f->tanggal_mulai,
                'tanggal_selesai' => $f->tanggal_selesai,
            ])->values(),
            'sections'         => $boqSections->map(fn($s) => [
                'id_boq'       => $s->id_boq,
                'point_name'   => $s->point_name ?? '—',
                'boq_qty'      => (int)($s->boq_qty ?? 0),
                'satuan'       => $s->satuan,
                'harga'        => (int)($s->harga ?? 0),
                'total_amount' => (int)($s->boq_qty ?? 0) * (int)($s->harga ?? 0),
                'fwo_qty'      => (int)($fwoQtyByBoq[$s->id_boq] ?? 0),
                'progress_pct' => ($s->boq_qty ?? 0) > 0
                    ? round((int)($fwoQtyByBoq[$s->id_boq] ?? 0) / $s->boq_qty * 100)
                    : 0,
                // 'fwos' => ($fwoDetailsByBoq[$s->id_boq] ?? collect())->map(fn($f) => [
                //     'id_fwo'        => $f->id_fwo,
                //     'no_fwo'        => $f->no_fwo,
                //     'tanggal_mulai' => $f->tanggal_mulai,
                //     'qty'           => (int)$f->qty,
                // ])->values(),
            ])->values(),
        ]);
    }

    public function show($id)
    {

        $testingUnit = DB::table('work_orders as wo')
            ->leftJoin('sales_orders as so', 'so.id_so', '=', 'wo.id_so')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'wo.id_pelanggan_pekerjaan')
            ->leftJoin('business_relation_sites as brs', 'brs.id_site', '=', 'wo.id_site_pelanggan_pekerjaan')
            ->leftJoin('business_relation_contacts as brc', 'brc.id_contact', '=', 'wo.id_pic_pelanggan_pekerjaan')
            ->leftJoin('wo_periods as wp', 'wp.id_period', '=', 'wo.id_period')
            ->leftJoin('business_relation_sites as brs_period', 'brs_period.id_site', '=', 'wp.id_site')
            ->where('wo.id_wo', $id)
            ->whereNull('wo.deleted_at')
            ->select([
                'wo.id_wo',
                'wo.no_wo',
                'wo.id_so',
                'so.no_so',
                'wo.judul_pekerjaan',
                'wo.keterangan',
                'wo.id_pelanggan_pekerjaan',
                'br.nama as nama_pelanggan_pekerjaan',
                'wo.id_site_pelanggan_pekerjaan',
                'brs.nama_lokasi as nama_site_pelanggan_pekerjaan',
                'wo.id_pic_pelanggan_pekerjaan',
                'brc.nama_pic as nama_pic_pelanggan_pekerjaan',
                'wo.id_period',
                'wp.id_site as period_id_site',
                'brs_period.nama_lokasi as period_nama_site',
                'wp.tanggal_mulai as period_tanggal_mulai',
                'wp.tanggal_selesai as period_tanggal_selesai',
                'wp.interval_bulan as period_interval_bulan',
                'wp.keterangan as period_keterangan',
                'wo.created_at',
                'wo.updated_at',
            ])
            ->first();



        if (!$testingUnit) {
            return response()->json(['message' => 'Testing unit tidak ditemukan'], 404);
        }

        return response()->json($testingUnit);
    }
}
