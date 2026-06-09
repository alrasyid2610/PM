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
        $no_wo    = $this->generateNoWo();
        $interval = $request->interval_bulan ?: null;
        $id_site  = $request->id_site_pelanggan ?: null;
        $no_urut  = null;
        if ($id_site && $interval) {
            if ($request->filled('no_urut_period')) {
                $no_urut = (int) $request->no_urut_period;
            } else {
                $existing = DB::table('work_orders')
                    ->where('id_so', $request->id_sales_order)
                    ->where('id_site_pelanggan_pekerjaan', $id_site)
                    ->where('interval_bulan', $interval)
                    ->whereNull('deleted_at')
                    ->count();
                $no_urut = $existing + 1;
            }
        }

        $id = DB::table('work_orders')->insertGetId([
            'no_wo'                       => $no_wo,
            'id_so'                       => $request->id_sales_order,
            'id_pelanggan_pekerjaan'      => $request->id_pelanggan,
            'id_site_pelanggan_pekerjaan' => $id_site,
            'id_pic_pelanggan_pekerjaan'  => $request->pic_pekerjaan,
            'judul_pekerjaan'             => $request->judul_order,
            'keterangan'                  => $request->keterangan,
            'interval_bulan'              => $interval,
            'no_urut_period'              => $no_urut,
            'created_at'                  => now(),
            'updated_at'                  => now(),
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
        $wo     = DB::table('work_orders')->where('id_wo', $id)->first();
        $before = DB::table('work_orders')->where('id_wo', $id)->get()->toJson();

        $interval = $request->interval_bulan ?: null;
        $id_site  = $request->id_site_pelanggan ?: $wo->id_site_pelanggan_pekerjaan;
        $no_urut  = null;
        if ($id_site && $interval) {
            if ($request->filled('no_urut_period')) {
                $no_urut = (int) $request->no_urut_period;
            } else {
                $existing = DB::table('work_orders')
                    ->where('id_so', $wo->id_so)
                    ->where('id_site_pelanggan_pekerjaan', $id_site)
                    ->where('interval_bulan', $interval)
                    ->where('id_wo', '!=', $id)
                    ->whereNull('deleted_at')
                    ->count();
                $no_urut = $existing + 1;
            }
        }

        try {
            DB::beginTransaction();

            DB::table('work_orders')
                ->where('id_wo', $id)
                ->update([
                    'id_pelanggan_pekerjaan'      => $request->id_pelanggan,
                    'id_site_pelanggan_pekerjaan' => $id_site,
                    'id_pic_pelanggan_pekerjaan'  => $request->pic_pekerjaan,
                    'judul_pekerjaan'             => $request->judul_order,
                    'keterangan'                  => $request->keterangan,
                    'interval_bulan'              => $interval,
                    'no_urut_period'              => $no_urut,
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
            ->whereNull('wo.deleted_at')
            ->select([
                'wo.id_wo',
                'wo.no_wo',
                'wo.judul_pekerjaan',
                'wo.id_site_pelanggan_pekerjaan',
                'wo.interval_bulan',
                'wo.no_urut_period',
                'br.nama as nama_pelanggan',
                'brs.nama_lokasi as nama_site',
                'brc.nama_pic as nama_pic_pekerjaan',
                'wo.keterangan',
            ])
            ->orderBy('wo.id_wo')
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

        $dupInterval = $source->interval_bulan;
        $dupNoUrut   = null;
        if ($source->id_site_pelanggan_pekerjaan && $dupInterval) {
            $existing = DB::table('work_orders')
                ->where('id_so', $source->id_so)
                ->where('id_site_pelanggan_pekerjaan', $source->id_site_pelanggan_pekerjaan)
                ->where('interval_bulan', $dupInterval)
                ->whereNull('deleted_at')
                ->count();
            $dupNoUrut = $existing + 1;
        }

        $newId = DB::table('work_orders')->insertGetId([
            'no_wo'                       => $no_wo,
            'id_so'                       => $source->id_so,
            'id_pelanggan_pekerjaan'      => $source->id_pelanggan_pekerjaan,
            'id_site_pelanggan_pekerjaan' => $source->id_site_pelanggan_pekerjaan,
            'id_pic_pelanggan_pekerjaan'  => $source->id_pic_pelanggan_pekerjaan,
            'judul_pekerjaan'             => $source->judul_pekerjaan,
            'keterangan'                  => $source->keterangan,
            'interval_bulan'              => $dupInterval,
            'no_urut_period'              => $dupNoUrut,
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

    public function boqProgress(int $id)
    {
        $boqSections = DB::table('boq as b')
            ->leftJoin('testing_points as tp', 'b.id_testing_point', '=', 'tp.id_testing_point')
            ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
            ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
            ->where('b.id_wo', $id)
            ->select([
                'b.id_boq',
                'b.id_testing_point',
                DB::raw("TRIM(CONCAT_WS(' ', NULLIF(tms.judul_indonesia,''), NULLIF(ts.nomor,''), NULLIF(tp.nama,''))) as point_name"),
                'b.qty as boq_qty',
                'b.satuan',
                'b.harga',
            ])
            ->get();

        $tpIds = $boqSections->pluck('id_testing_point');

        // Join via id_testing_point + fw.id_wo agar tetap akurat
        // setelah BOQ di-save ulang (id_boq berubah tapi id_testing_point stabil).
        $fwoQtyByTp = $tpIds->isNotEmpty()
            ? DB::table('fieldwork_boq as fb')
            ->join('fieldworks as fw', 'fw.id_fwo', '=', 'fb.id_fwo')
            ->whereIn('fb.id_testing_point', $tpIds)
            ->where('fw.id_wo', $id)
            ->whereNull('fw.deleted_at')
            ->selectRaw('fb.id_testing_point, SUM(COALESCE(fb.qty, 0)) as fwo_qty')
            ->groupBy('fb.id_testing_point')
            ->pluck('fwo_qty', 'id_testing_point')
            : collect();

        $totalBoqQty    = (int) $boqSections->sum('boq_qty');
        $totalFwoQty    = (int) $boqSections->sum(fn($s) => (int)($fwoQtyByTp[$s->id_testing_point] ?? 0));
        $pct            = $totalBoqQty > 0 ? round($totalFwoQty / $totalBoqQty * 100) : 0;
        $totalBoqAmount = (int) $boqSections->sum(fn($s) => (int)($s->boq_qty ?? 0) * (int)($s->harga ?? 0));

        $fwoDetailsByBoq = collect();

        // All FWOs linked to this WO
        $allFwos = DB::table('fieldworks')
            ->where('id_wo', $id)
            ->whereNull('deleted_at')
            ->select(['id_fwo', 'no_fwo', 'judul_pekerjaan', 'keterangan', 'tanggal_mulai', 'tanggal_selesai', 'status'])
            ->orderBy('id_fwo')
            ->get();

        $totalFwoCount     = $allFwos->count();
        $totalFwoCompleted = $allFwos->where('status', 'completed')->count();

        $outputStats = DB::table('output_pekerjaan')
            ->where('id_wo', $id)
            ->selectRaw("
                SUM(CASE WHEN status = 'belum_siap' THEN 1 ELSE 0 END) as belum_siap,
                SUM(CASE WHEN status = 'siap'       THEN 1 ELSE 0 END) as siap,
                SUM(CASE WHEN status = 'terkirim'   THEN 1 ELSE 0 END) as terkirim
            ")
            ->first();

        return response()->json([
            'total_boq_qty'      => $totalBoqQty,
            'total_fwo_qty'      => $totalFwoQty,
            'progress_pct'       => $pct,
            'total_boq_amount'   => $totalBoqAmount,
            'total_fwo'          => $totalFwoCount,
            'fwo_completed'      => $totalFwoCompleted,
            'output_belum_siap'  => (int) ($outputStats->belum_siap ?? 0),
            'output_siap'        => (int) ($outputStats->siap ?? 0),
            'output_terkirim'    => (int) ($outputStats->terkirim ?? 0),
            'fwos'              => $allFwos->map(fn($f) => [
                'id_fwo'          => $f->id_fwo,
                'no_fwo'          => $f->no_fwo,
                'judul_pekerjaan' => $f->judul_pekerjaan,
                'keterangan'      => $f->keterangan,
                'tanggal_mulai'   => $f->tanggal_mulai,
                'tanggal_selesai' => $f->tanggal_selesai,
                'status'          => $f->status,
            ])->values(),
            'sections'         => $boqSections->map(fn($s) => [
                'id_boq'       => $s->id_boq,
                'point_name'   => $s->point_name ?? '—',
                'boq_qty'      => (int)($s->boq_qty ?? 0),
                'satuan'       => $s->satuan,
                'harga'        => (int)($s->harga ?? 0),
                'total_amount' => (int)($s->boq_qty ?? 0) * (int)($s->harga ?? 0),
                'fwo_qty'      => (int)($fwoQtyByTp[$s->id_testing_point] ?? 0),
                'progress_pct' => ($s->boq_qty ?? 0) > 0
                    ? round((int)($fwoQtyByTp[$s->id_testing_point] ?? 0) / $s->boq_qty * 100)
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
                'wo.interval_bulan',
                'wo.no_urut_period',
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
