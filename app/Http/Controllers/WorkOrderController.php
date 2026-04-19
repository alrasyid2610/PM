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

    public function create()
    {
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


    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('work_orders')
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
                's.tidak_ada_po',
                's.no_so',
                'br.nama as Pelanggan',
                'brs.nama_lokasi as Site Pelanggan',
                's.judul_order',
                'wo.keterangan',
                's.created_at',
            ])
            ->where('wo.id_wo', $id)
            ->first();

        if (!$wo) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work Order tidak ditemukan'
            ], 404);
        }

        return response()->json($wo);
    }

    public function show($id)
    {

        $testingUnit = DB::table('work_orders as wo')
            ->leftJoin('sales_orders as so', 'so.id_so', '=', 'wo.id_so')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'wo.id_pelanggan_pekerjaan')
            ->leftJoin('business_relation_sites as brs', 'brs.id_site', '=', 'wo.id_site_pelanggan_pekerjaan')
            ->leftJoin('business_relation_contacts as brc', 'brc.id_contact', '=', 'wo.id_pic_pelanggan_pekerjaan')
            ->where('wo.id_wo', $id)
            ->select([
                'wo.id_wo',
                'wo.no_wo',
                'wo.id_so',
                'so.no_so',                         // ← label SO
                'wo.judul_pekerjaan',
                'wo.keterangan',
                'wo.id_pelanggan_pekerjaan',
                'br.nama as nama_pelanggan_pekerjaan', // ← label pelanggan
                'wo.id_site_pelanggan_pekerjaan',
                'brs.nama_lokasi as nama_site_pelanggan_pekerjaan', // ← label site
                'wo.id_pic_pelanggan_pekerjaan',
                'brc.nama_pic as nama_pic_pelanggan_pekerjaan',     // ← label PIC
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
