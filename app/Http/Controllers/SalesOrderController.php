<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SebastianBergmann\Environment\Console;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;


class SalesOrderController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string
    {
        return 'sales_orders';
    }

    protected function auditExcludeFields(): array
    {
        return ['updated_at', 'created_at', 'id_so'];
    }
    //
    public function index()
    {
        return view('sales-order.index', [
            'title' => 'Sales Orders',
        ]);
    }


    public function create()
    {
        return view('sales-order.create', [
            'title' => 'Create Sales Order',
        ]);
    }


    public function store(Request $request)
    {
        // ==========================
        // VALIDATION
        // ==========================
        $request->validate([
            'tanggal_so' => 'required|date',
            'id_pelanggan' => 'required|integer',
            'status' => 'required|string'
        ]);

        $soNumber = $this->generateSoNumber();

        // ==========================
        // INSERT
        // ==========================
        $id = DB::table('sales_orders')->insertGetId([
            'no_so' => $soNumber,
            'tanggal_so' => $request->tanggal_so,
            'judul_order' => $request->judul_order,
            'tidak_ada_po' => $request->tidak_ada_po ?? 0,
            'no_po' => $request->no_po,
            'tanggal_po' => $request->tanggal_po,

            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,

            'id_office' => $request->id_office,

            // CUSTOMER
            'id_pelanggan' => $request->id_pelanggan,
            'id_site_pelanggan' => $request->id_site_pelanggan,
            'id_pic_pelanggan' => $request->id_pic_pelanggan,

            // DELIVERY
            'id_pelanggan_delivery' => $request->id_pelanggan_delivery,
            'id_site_pelanggan_delivery' => $request->id_site_pelanggan_delivery,
            'id_pic_pelanggan_delivery' => $request->id_pic_pelanggan_delivery,

            // PAYMENT
            'id_pelanggan_payment' => $request->id_pelanggan_payment,
            'id_site_pelanggan_payment' => $request->id_site_pelanggan_payment,
            'id_pic_pelanggan_payment' => $request->id_pic_pelanggan_payment,

            // PIC
            'pic_input' => $request->pic_input,
            'pic_order' => $request->pic_order,
            'pic_marketing_internal' => $request->pic_marketing_internal,
            'pic_marketing_eksternal' => $request->pic_marketing_eksternal,

            // STATUS
            'status' => $request->status,
            'keterangan_status' => $request->keterangan_status,
            'keterangan' => $request->keterangan,

            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $after = DB::table('sales_orders')->where('id_so', $id)->get()->toJson();
        saveAudit('sales_orders', $id, 'Create', '', $after);

        return response()->json([
            'status' => 'success',
            'message' => 'Sales Order berhasil dibuat',
            'id_so' => $id
        ]);
    }


    private function generateSoNumber()
    {
        $year = now()->format('y');
        $month = now()->format('m');

        $prefix = "SO-{$year}-";

        $lastSo = DB::table('sales_orders')
            ->where('no_so', 'like', $prefix . '%')
            ->orderByDesc('id_so')
            ->first();

        if ($lastSo) {
            $lastNumber = (int) substr($lastSo->no_so, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $running = str_pad($newNumber, 4, '0', STR_PAD_LEFT);

        return $prefix . $running;
    }



    public function show($id)
    {
        $so = DB::table('sales_orders as so')
            ->leftJoin('business_relations as pelanggan', 'so.id_pelanggan', '=', 'pelanggan.id_br')
            ->leftJoin('business_relation_sites as site_pelanggan', 'so.id_site_pelanggan', '=', 'site_pelanggan.id_site')
            ->leftJoin('office as o', 'o.id_office', '=', 'so.id_office')
            ->leftJoin('business_relation_contacts as brc', 'brc.id_contact', '=', 'so.id_pic_pelanggan')
            ->leftJoin('business_relations as del', 'so.id_pelanggan_delivery', '=', 'del.id_br')
            ->leftJoin('business_relation_sites as site_del', 'so.id_site_pelanggan_delivery', '=', 'site_del.id_site')
            ->leftJoin('business_relation_contacts as brc_del', 'brc_del.id_contact', '=', 'so.id_pic_pelanggan_delivery')
            ->leftJoin('business_relations as pay', 'so.id_pelanggan_payment', '=', 'pay.id_br')
            ->leftJoin('business_relation_sites as site_pay', 'so.id_site_pelanggan_payment', '=', 'site_pay.id_site')
            ->leftJoin('business_relation_contacts as brc_pay', 'brc_pay.id_contact', '=', 'so.id_pic_pelanggan_payment')
            ->leftJoin('business_relation_contacts as pic_i', 'pic_i.id_contact', '=', 'so.pic_input')
            ->leftJoin('business_relation_contacts as pic_o', 'pic_o.id_contact', '=', 'so.pic_input')
            ->leftJoin('business_relation_contacts as marketing_internal', 'marketing_internal.id_contact', '=', 'so.pic_marketing_internal')
            ->leftJoin('business_relation_contacts as marketing_eksternal', 'marketing_eksternal.id_contact', '=', 'so.pic_marketing_eksternal')
            ->select(
                'so.*',
                'pelanggan.nama as nama_pelanggan',
                'site_pelanggan.nama_lokasi as nama_site_pelanggan',
                'o.id_office',
                'o.name as name_office',
                'brc.nama_pic as pic_pelanggan',
                'del.nama as pelanggan_delivery',
                'site_del.nama_lokasi as pelanggan_site_delivery',
                'brc_del.nama_pic as pic_pelanggan_del',
                'pay.nama as pelanggan_pay',
                'site_pay.nama_lokasi as pelanggan_site_pay',
                'brc_pay.id_contact as id_pic_pelanggan_payment',
                'brc_pay.nama_pic as pic_pelanggan_pay',
                'pic_i.nama_pic as pic_input',
                'pic_o.nama_pic as pic_ordername',
                'marketing_internal.nama_pic as marketing_internal_name',
                'marketing_internal.id_contact as marketing_internal_id',
                'marketing_eksternal.nama_pic as marketing_eksternal_name',
                'marketing_eksternal.id_contact as marketing_eksternal_id',
            )
            ->where('so.id_so', $id)
            ->first();


        if (!$so) {
            return response()->json([
                'message' => 'Sales Order tidak ditemukan'
            ], 404);
        }

        return response()->json($so);
    }

    public function detail($id)
    {

        $so = DB::table('sales_orders as so')
            ->leftJoin('business_relations as pelanggan', 'so.id_pelanggan', '=', 'pelanggan.id_br')
            ->leftJoin('business_relation_sites as site_pelanggan', 'so.id_site_pelanggan', '=', 'site_pelanggan.id_site')
            ->select('so.*', 'pelanggan.nama as nama_pelanggan', 'site_pelanggan.nama_lokasi as nama_site_pelanggan')
            ->where('so.id_so', $id)
            ->first();


        if (!$so) {
            return response()->json([
                'message' => 'Sales Order tidak ditemukan'
            ], 404);
        }

        return response()->json($so);
    }

    public function update(Request $request, $id)
    {

        // dd($request->all(), $id);
        // =========================
        // VALIDATION
        // =========================
        $validated = $request->validate([
            'tanggal_so' => 'required|date',
            'judul_order' => 'nullable|string|max:255',
            'tidak_ada_po' => 'required|boolean',
            'tanggal_po' => 'nullable|date',
            'no_po' => 'nullable|string|max:100',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'id_office' => 'nullable|integer',

            'id_pelanggan' => 'required|integer',
            'id_site_pelanggan' => 'nullable|integer',
            'id_pic_pelanggan' => 'nullable|integer',

            'id_pelanggan_delivery' => 'required|integer',
            'id_site_pelanggan_delivery' => 'nullable|integer',
            'id_pic_pelanggan_delivery' => 'nullable|integer',

            'id_pelanggan_payment' => 'required|integer',
            'id_site_pelanggan_payment' => 'nullable|integer',
            'id_pic_pelanggan_payment' => 'nullable|integer',

            'pic_input' => 'nullable|string|max:100',
            'pic_order' => 'nullable|string|max:100',
            'pic_marketing_internal' => 'nullable|string|max:100',
            'pic_marketing_eksternal' => 'nullable|string|max:100',

            'status' => 'required|string|max:50',
            'keterangan_status' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ]);

        try {

            // =========================
            // UPDATE
            // =========================
            $before = DB::table('sales_orders')->where('id_so', $id)->get()->toJson();

            DB::table('sales_orders')
                ->where('id_so', $id)
                ->update([
                    'tanggal_so' => $validated['tanggal_so'],
                    'judul_order' => $validated['judul_order'],
                    'tidak_ada_po' => $validated['tidak_ada_po'],
                    'tanggal_po' => $validated['tanggal_po'],
                    'no_po' => $validated['no_po'],
                    'tanggal_mulai' => $validated['tanggal_mulai'],
                    'tanggal_selesai' => $validated['tanggal_selesai'],
                    'id_office' => $validated['id_office'],

                    'id_pelanggan' => $validated['id_pelanggan'],
                    'id_site_pelanggan' => $validated['id_site_pelanggan'],
                    'id_pic_pelanggan' => $validated['id_pic_pelanggan'],

                    'id_pelanggan_delivery' => $validated['id_pelanggan_delivery'],
                    'id_site_pelanggan_delivery' => $validated['id_site_pelanggan_delivery'],
                    'id_pic_pelanggan_delivery' => $validated['id_pic_pelanggan_delivery'],

                    'id_pelanggan_payment' => $validated['id_pelanggan_payment'],
                    'id_site_pelanggan_payment' => $validated['id_site_pelanggan_payment'],
                    'id_pic_pelanggan_payment' => $validated['id_pic_pelanggan_payment'],

                    'pic_input' => $validated['pic_input'],
                    'pic_order' => $validated['pic_order'],
                    'pic_marketing_internal' => $validated['pic_marketing_internal'],
                    'pic_marketing_eksternal' => $validated['pic_marketing_eksternal'],

                    'status' => $validated['status'],
                    'keterangan_status' => $validated['keterangan_status'],
                    'keterangan' => $validated['keterangan'],

                    'updated_at' => now(),
                ]);

            $after = DB::table('sales_orders')->where('id_so', $id)->get()->toJson();
            saveAudit('sales_orders', $id, 'update', $before, $after);

            return response()->json([
                'success' => true,
                'message' => 'Sales Order berhasil diperbarui'
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('sales_orders')
            ->where('no_so', 'like', "%{$search}%")
            ->limit(10)
            ->get();

        return response()->json(
            $data->map(function ($item) {
                return [
                    'id'    => $item->id_so,         // HARUS 'id'
                    'text'  => $item->no_so . " - " . $item->judul_order,         // HARUS 'text' agar muncul di dropdown
                    'judul' => $item->judul_order,   // Data tambahan (opsional)
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
        $query = DB::table('sales_orders as s')
            ->leftJoin('business_relations as br', 's.id_pelanggan', '=', 'br.id_br')
            ->leftJoin('business_relation_sites as brs', 's.id_site_pelanggan', '=', 'brs.id_site')
            ->select([
                's.id_so',
                's.no_so',
                'br.nama as Pelanggan',
                'brs.nama_lokasi as Site Customer',
                's.judul_order',
                's.id_pelanggan',
                's.id_site_pelanggan',
                's.status',
                's.created_at',
            ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->make(true);
    }
}
