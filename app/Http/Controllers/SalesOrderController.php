<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'no_so'      => $soNumber,
            'id_sc'      => $request->filled('id_sc') ? (int)$request->id_sc : null,
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
            'cara_pembayaran'   => $request->cara_pembayaran,
            'keterangan'        => $request->keterangan,

            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $after = DB::table('sales_orders')->where('id_so', $id)->get()->toJson();
        saveAudit('sales_orders', $id, 'Create', '', $after);

        return response()->json([
            'success' => true,
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
            ->leftJoin('business_relation_contacts as pic_o', 'pic_o.id_contact', '=', 'so.pic_order')
            ->leftJoin('business_relation_contacts as marketing_internal', 'marketing_internal.id_contact', '=', 'so.pic_marketing_internal')
            ->leftJoin('business_relation_contacts as marketing_eksternal', 'marketing_eksternal.id_contact', '=', 'so.pic_marketing_eksternal')
            ->leftJoin('contracts as ct', 'ct.id_contract', '=', 'so.id_sc')
            ->select(
                'so.*',
                'ct.no_contract as contract_no',
                'ct.no_contract_client as contract_no_client',
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
                'pic_i.nama_pic as pic_input_name',
                'pic_o.nama_pic as pic_ordername',
                'marketing_internal.nama_pic as marketing_internal_name',
                'marketing_internal.id_contact as marketing_internal_id',
                'marketing_eksternal.nama_pic as marketing_eksternal_name',
                'marketing_eksternal.id_contact as marketing_eksternal_id',
            )
            ->where('so.id_so', $id)
            ->whereNull('so.deleted_at')
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
            ->leftJoin('contracts as ct', 'ct.id_contract', '=', 'so.id_sc')
            ->select(
                'so.*',
                'pelanggan.nama as nama_pelanggan',
                'site_pelanggan.nama_lokasi as nama_site_pelanggan',
                'ct.no_contract as contract_no',
                'ct.no_contract_client as contract_no_client',
            )
            ->where('so.id_so', $id)
            ->whereNull('so.deleted_at')
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

            'id_sc' => 'nullable|integer',
            'status' => 'required|string|max:50',
            'keterangan_status' => 'nullable|string',
            'cara_pembayaran'   => 'nullable|string',
            'keterangan'        => 'nullable|string',
        ]);

        try {

            // =========================
            // UPDATE
            // =========================
            $before = DB::table('sales_orders')->where('id_so', $id)->get()->toJson();

            DB::table('sales_orders')
                ->where('id_so', $id)
                ->update([
                    'id_sc'      => !empty($validated['id_sc']) ? (int)$validated['id_sc'] : null,
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
                    'cara_pembayaran'   => $validated['cara_pembayaran'],
                    'keterangan'        => $validated['keterangan'],

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

    public function woProgress(int $id_so)
    {
        $wos = DB::table('work_orders as wo')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'wo.id_pelanggan_pekerjaan')
            ->leftJoin('business_relation_sites as brs', 'brs.id_site', '=', 'wo.id_site_pelanggan_pekerjaan')
            ->where('wo.id_so', $id_so)
            ->whereNull('wo.deleted_at')
            ->select(['wo.id_wo', 'wo.no_wo', 'wo.judul_pekerjaan', 'wo.keterangan', 'wo.interval_bulan', 'wo.no_urut_period', 'wo.tanggal_mulai', 'wo.tanggal_selesai', 'br.nama as nama_pelanggan', 'brs.nama_lokasi as nama_site_pelanggan'])
            ->orderByRaw('ISNULL(wo.tanggal_mulai), wo.tanggal_mulai ASC')
            ->orderBy('wo.id_wo')
            ->get();

        if ($wos->isEmpty()) {
            return response()->json([]);
        }

        $woIds = $wos->pluck('id_wo');

        $boqSections = DB::table('boq as b')
            ->leftJoin('testing_points as tp', 'b.id_testing_point', '=', 'tp.id_testing_point')
            ->whereIn('b.id_wo', $woIds)
            ->select(['b.id_boq', 'b.id_wo', 'tp.nama as point_name', 'b.qty as boq_qty', 'b.satuan', 'b.harga'])
            ->get();

        $boqIds = $boqSections->pluck('id_boq');

        $fwoQtyByBoq = DB::table('fieldwork_boq')
            ->whereIn('id_boq', $boqIds)
            ->selectRaw('id_boq, SUM(COALESCE(qty, 0)) as fwo_qty')
            ->groupBy('id_boq')
            ->pluck('fwo_qty', 'id_boq');

        $fwoCountByWo = DB::table('fieldworks')
            ->whereIn('id_wo', $woIds)
            ->whereNull('deleted_at')
            ->selectRaw('id_wo, COUNT(*) as fwo_count')
            ->groupBy('id_wo')
            ->pluck('fwo_count', 'id_wo');

        $sectionsByWo = $boqSections->groupBy('id_wo');

        // FWO list per WO dengan total qty yang dikerjakan
        $fwoRows = DB::table('fieldworks as fw')
            ->leftJoin('fieldwork_boq as fb', 'fw.id_fwo', '=', 'fb.id_fwo')
            ->whereIn('fw.id_wo', $woIds)
            ->whereNull('fw.deleted_at')
            ->select([
                'fw.id_fwo', 'fw.id_wo', 'fw.no_fwo',
                'fw.tanggal_mulai', 'fw.tanggal_selesai',
                DB::raw('COUNT(fb.id_fwo_boq) as boq_section_count'),
                DB::raw('SUM(COALESCE(fb.qty, 0)) as total_qty'),
            ])
            ->groupBy('fw.id_fwo', 'fw.id_wo', 'fw.no_fwo', 'fw.tanggal_mulai', 'fw.tanggal_selesai')
            ->orderBy('fw.id_fwo')
            ->get()
            ->groupBy('id_wo');

        return response()->json($wos->map(function ($wo) use ($sectionsByWo, $fwoQtyByBoq, $fwoCountByWo, $fwoRows) {
            $sections         = $sectionsByWo->get($wo->id_wo) ?? collect();
            $totalBoqQty      = (int) $sections->sum('boq_qty');
            $totalFwoQty      = (int) $sections->sum(fn($s) => (int)($fwoQtyByBoq[$s->id_boq] ?? 0));
            $pct              = $totalBoqQty > 0 ? round($totalFwoQty / $totalBoqQty * 100) : 0;
            $totalBoqAmount   = (int) $sections->sum(fn($s) => (int)($s->boq_qty ?? 0) * (int)($s->harga ?? 0));

            $fwos = ($fwoRows->get($wo->id_wo) ?? collect())->map(fn($f) => [
                'id_fwo'             => $f->id_fwo,
                'no_fwo'             => $f->no_fwo,
                'tanggal_mulai'      => $f->tanggal_mulai,
                'tanggal_selesai'    => $f->tanggal_selesai,
                'boq_section_count'  => (int)$f->boq_section_count,
                'total_qty'          => (int)$f->total_qty,
            ])->values()->toArray();

            return [
                'id_wo'               => $wo->id_wo,
                'no_wo'               => $wo->no_wo,
                'judul_pekerjaan'     => $wo->judul_pekerjaan,
                'keterangan'          => $wo->keterangan,
                'interval_bulan'      => $wo->interval_bulan,
                'no_urut_period'      => $wo->no_urut_period,
                'nama_pelanggan'      => $wo->nama_pelanggan,
                'nama_site_pelanggan' => $wo->nama_site_pelanggan,
                'tanggal_mulai'       => $wo->tanggal_mulai,
                'tanggal_selesai'     => $wo->tanggal_selesai,
                'fwo_count'           => (int)($fwoCountByWo[$wo->id_wo] ?? 0),
                'total_boq_qty'   => $totalBoqQty,
                'total_fwo_qty'   => $totalFwoQty,
                'progress_pct'    => $pct,
                'total_boq_amount' => $totalBoqAmount,
                'sections'        => $sections->map(fn($s) => [
                    'id_boq'        => $s->id_boq,
                    'point_name'    => $s->point_name ?? '—',
                    'boq_qty'       => (int)($s->boq_qty ?? 0),
                    'satuan'        => $s->satuan,
                    'harga'         => (int)($s->harga ?? 0),
                    'total_amount'  => (int)($s->boq_qty ?? 0) * (int)($s->harga ?? 0),
                    'fwo_qty'       => (int)($fwoQtyByBoq[$s->id_boq] ?? 0),
                    'progress_pct'  => ($s->boq_qty ?? 0) > 0
                        ? round((int)($fwoQtyByBoq[$s->id_boq] ?? 0) / $s->boq_qty * 100)
                        : 0,
                ])->values()->toArray(),
                'fwos'            => $fwos,
            ];
        })->values());
    }

    public function destroy($id)
    {
        $before = DB::table('sales_orders')->where('id_so', $id)->get()->toJson();
        DB::table('sales_orders')->where('id_so', $id)->update(['deleted_at' => now()]);
        $after = DB::table('sales_orders')->where('id_so', $id)->get()->toJson();
        saveAudit('sales_orders', $id, 'delete', $before, $after);
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('sales_orders')
            ->whereNull('deleted_at')
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
            ->whereNull('s.deleted_at')
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
