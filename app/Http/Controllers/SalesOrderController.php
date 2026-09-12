<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;
use App\Traits\HasAttachment;
use Spatie\LaravelPdf\Facades\Pdf;


class SalesOrderController extends Controller
{
    use HasAuditHistory, HasAttachment;

    protected function attachmentTable(): string      { return 'sales_orders'; }
    protected function attachmentPrimaryKey(): string { return 'id_so'; }

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
            'attachments'   => 'nullable|array',
            'attachments.*' => 'nullable|file|max:153600',
        ]);

        $soNumber = $this->generateSoNumber();

        // Attachment (opsional saat create) — pakai infrastruktur generik yang
        // sama seperti update(): helper uploadAttachment() + kolom json.
        $upload = uploadAttachment($request->file('attachments'), 'sales_orders');
        $files  = $upload['files'];

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

            // STATUS — default on-progress saat pertama dibuat
            'status' => 'on-progress',
            'keterangan_status' => $request->keterangan_status,
            'cara_pembayaran'   => $request->cara_pembayaran,
            'keterangan'        => $request->keterangan,

            'attachment' => json_encode($files),

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
            ->leftJoin('users as pic_i', 'pic_i.id', '=', 'so.pic_input')
            ->leftJoin('users as pic_o', 'pic_o.id', '=', 'so.pic_order')
            ->leftJoin('users as marketing_internal', 'marketing_internal.id', '=', 'so.pic_marketing_internal')
            ->leftJoin('users as marketing_eksternal', 'marketing_eksternal.id', '=', 'so.pic_marketing_eksternal')
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
                'pic_i.name as pic_input_name',
                'pic_o.name as pic_ordername',
                'marketing_internal.name as marketing_internal_name',
                'marketing_internal.id as marketing_internal_id',
                'marketing_eksternal.name as marketing_eksternal_name',
                'marketing_eksternal.id as marketing_eksternal_id',
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
            ->leftJoin('contracts as ct', 'ct.id_contract', '=', 'so.id_sc')
            ->select(
                'so.*',
                'pelanggan.nama as nama_pelanggan',
                'site_pelanggan.nama_lokasi as nama_site_pelanggan',
                'ct.no_contract as contract_no',
                'ct.no_contract_client as contract_no_client',
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

    public function update(Request $request, $id)
    {

        // dd($request->all(), $id);
        // =========================
        // VALIDATION
        // =========================
        $validated = $request->validate([
            'tanggal_so' => 'required|date',
            'judul_order' => 'nullable|string|max:255',
            'tidak_ada_po' => 'nullable|boolean',
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

            'pic_input' => 'nullable|integer',
            'pic_order' => 'nullable|integer',
            'pic_marketing_internal' => 'nullable|integer',
            'pic_marketing_eksternal' => 'nullable|integer',

            'id_sc' => 'nullable|integer',
            'keterangan_status' => 'nullable|string',
            'cara_pembayaran'   => 'nullable|string',
            'keterangan'        => 'nullable|string',

            'attachments'            => 'nullable|array',
            'attachments.*'          => 'nullable|file|max:153600',
            'existing_attachments'   => 'nullable|array',
            'existing_attachments.*' => 'nullable|string',
        ]);

        // Gabungan attachment lama (yang tidak dihapus user) + file baru yang diupload
        $existingAtt = $request->existing_attachments ?? [];
        $newAtt      = [];
        if ($request->hasFile('attachments')) {
            $upload = uploadAttachment($request->file('attachments'), 'sales_orders');
            $newAtt = $upload['files'];
        }
        $mergedAtt = json_encode(array_values(array_merge($existingAtt, $newAtt)));

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
                    'judul_order' => $validated['judul_order'] ?? null,
                    'tidak_ada_po' => $validated['tidak_ada_po'] ?? 0,
                    'tanggal_po' => $validated['tanggal_po'] ?? null,
                    'no_po' => $validated['no_po'] ?? null,
                    'tanggal_mulai' => $validated['tanggal_mulai'] ?? null,
                    'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
                    'id_office' => $validated['id_office'] ?? null,

                    'id_pelanggan' => $validated['id_pelanggan'],
                    'id_site_pelanggan' => $validated['id_site_pelanggan'] ?? null,
                    'id_pic_pelanggan' => $validated['id_pic_pelanggan'] ?? null,

                    'id_pelanggan_delivery' => $validated['id_pelanggan_delivery'],
                    'id_site_pelanggan_delivery' => $validated['id_site_pelanggan_delivery'] ?? null,
                    'id_pic_pelanggan_delivery' => $validated['id_pic_pelanggan_delivery'] ?? null,

                    'id_pelanggan_payment' => $validated['id_pelanggan_payment'],
                    'id_site_pelanggan_payment' => $validated['id_site_pelanggan_payment'] ?? null,
                    'id_pic_pelanggan_payment' => $validated['id_pic_pelanggan_payment'] ?? null,

                    'pic_input' => $validated['pic_input'] ?? null,
                    'pic_order' => $validated['pic_order'] ?? null,
                    'pic_marketing_internal' => $validated['pic_marketing_internal'] ?? null,
                    'pic_marketing_eksternal' => $validated['pic_marketing_eksternal'] ?? null,

                    'keterangan_status' => $validated['keterangan_status'] ?? null,
                    'cara_pembayaran'   => $validated['cara_pembayaran'] ?? null,
                    'keterangan'        => $validated['keterangan'] ?? null,

                    'attachment' => $mergedAtt,

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
            ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 'b.id_satuan')
            ->whereIn('b.id_wo', $woIds)
            ->whereNull('b.deleted_at')
            ->select(['b.id_boq', 'b.id_wo', 'tp.nama as point_name', 'b.qty as boq_qty', 'sat.nama as satuan', 'b.harga'])
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
        $filters     = $request->input('filters', []);
        $isSearching = !empty($filters);

        $query = DB::table('sales_orders as s')
            ->leftJoin('business_relations as br', 's.id_pelanggan', '=', 'br.id_br')
            ->leftJoin('business_relation_sites as brs', 's.id_site_pelanggan', '=', 'brs.id_site')
            ->when(!$isSearching, fn($q) => $q->whereNull('s.deleted_at'))
            ->when(!$isSearching, fn($q) => $q->where('s.status', '!=', 'completed'))
            ->when($isSearching, function ($q) use ($filters) {
                foreach ($filters as $f) {
                    $by   = $f['by']  ?? '';
                    $term = trim($f['q'] ?? '');
                    if (!$by || $term === '') continue;
                    $like = '%' . $term . '%';
                    match ($by) {
                        'no_so'     => $q->where('s.no_so', 'like', $like),
                        'judul'     => $q->where('s.judul_order', 'like', $like),
                        'pelanggan' => $q->where('br.nama', 'like', $like),
                        'status'    => match ($term) {
                            'all'     => null,
                            'deleted' => $q->whereNotNull('s.deleted_at'),
                            default   => $q->whereNull('s.deleted_at')->where('s.status', $term),
                        },
                        default => null,
                    };
                }
            })
            ->select([
                's.id_so',
                DB::raw("CASE WHEN s.deleted_at IS NOT NULL THEN 'deleted' ELSE s.status END as status"),
                's.no_so',
                'br.nama as Pelanggan',
                'brs.nama_lokasi as Site Customer',
                's.judul_order',
                's.id_pelanggan',
                's.id_site_pelanggan',
                's.created_at',
            ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->make(true);
    }

    /**
     * Output Printout SO (Sementara) — dokumen cetak PDF internal untuk
     * mempermudah pengecekan inputan data: SO → daftar WO + detail tiap WO →
     * BOQ tiap WO (berikut Testing Point/Standard/Matriks Sample-nya).
     * Belum mencakup FWO — menyusul, menunggu instruksi lanjutan.
     */
    public function printPdf($id)
    {
        $so = DB::table('sales_orders as so')
            ->leftJoin('business_relations as pelanggan', 'so.id_pelanggan', '=', 'pelanggan.id_br')
            ->leftJoin('business_relation_sites as site_pelanggan', 'so.id_site_pelanggan', '=', 'site_pelanggan.id_site')
            ->leftJoin('business_relation_contacts as brc', 'brc.id_contact', '=', 'so.id_pic_pelanggan')
            ->leftJoin('business_relations as del', 'so.id_pelanggan_delivery', '=', 'del.id_br')
            ->leftJoin('business_relation_sites as site_del', 'so.id_site_pelanggan_delivery', '=', 'site_del.id_site')
            ->leftJoin('business_relation_contacts as brc_del', 'brc_del.id_contact', '=', 'so.id_pic_pelanggan_delivery')
            ->leftJoin('business_relations as pay', 'so.id_pelanggan_payment', '=', 'pay.id_br')
            ->leftJoin('business_relation_sites as site_pay', 'so.id_site_pelanggan_payment', '=', 'site_pay.id_site')
            ->leftJoin('business_relation_contacts as brc_pay', 'brc_pay.id_contact', '=', 'so.id_pic_pelanggan_payment')
            ->leftJoin('office as o', 'o.id_office', '=', 'so.id_office')
            ->leftJoin('users as pic_i', 'pic_i.id', '=', 'so.pic_input')
            ->leftJoin('users as pic_o', 'pic_o.id', '=', 'so.pic_order')
            ->leftJoin('users as mkt_i', 'mkt_i.id', '=', 'so.pic_marketing_internal')
            ->leftJoin('users as mkt_e', 'mkt_e.id', '=', 'so.pic_marketing_eksternal')
            ->leftJoin('contracts as ct', 'ct.id_contract', '=', 'so.id_sc')
            ->where('so.id_so', $id)
            ->select([
                'so.*',
                'ct.no_contract',
                'pelanggan.nama as nama_pelanggan',
                'site_pelanggan.nama_lokasi as nama_site_pelanggan',
                'brc.nama_pic as pic_pelanggan',
                'del.nama as nama_pelanggan_delivery',
                'site_del.nama_lokasi as nama_site_delivery',
                'brc_del.nama_pic as pic_delivery',
                'pay.nama as nama_pelanggan_payment',
                'site_pay.nama_lokasi as nama_site_payment',
                'brc_pay.nama_pic as pic_payment',
                'o.name as nama_office',
                'pic_i.name as nama_pic_input',
                'pic_o.name as nama_pic_order',
                'mkt_i.name as nama_marketing_internal',
                'mkt_e.name as nama_marketing_eksternal',
            ])
            ->first();

        if (!$so) abort(404, 'Sales Order tidak ditemukan');

        $wos = DB::table('work_orders as wo')
            ->leftJoin('business_relation_sites as brs', 'brs.id_site', '=', 'wo.id_site_pelanggan_pekerjaan')
            ->leftJoin('users as pic', 'pic.id', '=', 'wo.id_pic_pelanggan_pekerjaan')
            ->where('wo.id_so', $id)
            ->whereNull('wo.deleted_at')
            ->orderBy('wo.id_wo')
            ->select([
                'wo.id_wo',
                'wo.no_wo',
                'wo.judul_pekerjaan',
                'wo.status',
                'wo.interval_bulan',
                'wo.no_urut_period',
                'wo.tanggal_mulai',
                'wo.tanggal_selesai',
                'wo.keterangan',
                'brs.nama_lokasi as nama_site',
                'pic.name as nama_pic',
            ])
            ->get();

        $woIds = $wos->pluck('id_wo');

        $boqRows = $woIds->isNotEmpty()
            ? DB::table('boq as b')
                ->leftJoin('testing_points as tp', 'b.id_testing_point', '=', 'tp.id_testing_point')
                ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
                ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
                ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 'b.id_satuan')
                ->whereIn('b.id_wo', $woIds)
                ->whereNull('b.deleted_at')
                ->orderBy('b.id_boq')
                ->select([
                    'b.id_wo',
                    'b.item_produk_alternate',
                    'tp.nama as nama_testing_point',
                    'ts.nomor as standard_nomor',
                    'ts.judul as standard_judul',
                    'tms.kode as matriks_kode',
                    'tms.judul_indonesia as matriks_judul',
                    'b.qty',
                    'sat.nama as satuan',
                    'b.harga',
                    'b.keterangan',
                ])
                ->get()
                ->groupBy('id_wo')
            : collect();

        // BOQ Other & BOQ Sampling — 1 tabel sama (boq_tambahan), dibedakan
        // kolom jenis. Lihat WO.md#wo-boq-other--boq-sampling.
        $boqTambahanBase = $woIds->isNotEmpty()
            ? DB::table('boq_tambahan as bt')
                ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 'bt.id_satuan')
                ->whereIn('bt.id_wo', $woIds)
                ->whereNull('bt.deleted_at')
                ->orderBy('bt.id_boq_tambahan')
                ->select(['bt.id_wo', 'bt.jenis', 'bt.nama_item', 'bt.qty', 'sat.nama as satuan', 'bt.harga', 'bt.keterangan'])
                ->get()
            : collect();

        $boqOtherRows    = $boqTambahanBase->where('jenis', 'lainnya')->groupBy('id_wo');
        $boqSamplingRows = $boqTambahanBase->where('jenis', 'sampling')->groupBy('id_wo');

        $intervalLabels = [1 => 'Bulanan', 2 => 'Bimulanan', 3 => 'Triwulan', 4 => 'Caturwulan', 6 => 'Semester', 12 => 'Annual'];

        return Pdf::view('pdf.sales-order.printout', [
            'so'              => $so,
            'wos'             => $wos,
            'boqRows'         => $boqRows,
            'boqOtherRows'    => $boqOtherRows,
            'boqSamplingRows' => $boqSamplingRows,
            'intervalLabels'  => $intervalLabels,
        ])
            ->format('a4')
            ->name("Printout-{$so->no_so}.pdf");
    }

    /**
     * Clone SO — Fase 1 (SO + WO + BOQ + BOQ Other/Sampling saja).
     * FWO, Budget, Personnel menyusul di fase berikutnya (disepakati bertahap
     * dengan user 2026-09-12). Halaman penuh (bukan modal) karena skalanya
     * bisa banyak WO sekaligus — lihat Sales Order.md untuk konsep lengkap.
     */
    public function clonePage($id)
    {
        $so = DB::table('sales_orders')->where('id_so', $id)->whereNull('deleted_at')->first();
        if (!$so) abort(404, 'Sales Order tidak ditemukan');

        return view('sales-order.clone', ['id' => $id]);
    }

    /**
     * Data mentah untuk mengisi halaman wizard Clone SO (dibaca via AJAX oleh
     * sales-order/clone.blade.php) — SO + semua WO-nya + BOQ/BOQ Other/BOQ
     * Sampling tiap WO, lengkap dengan id mentah (bukan cuma label) supaya
     * bisa di-preselect di select2 dan dikirim balik sebagai `source_id_*`
     * saat submit clone.
     */
    public function cloneData($id)
    {
        // Join sama seperti printPdf()/show() — dibutuhkan label tampilan
        // (nama Perusahaan/Site/PIC/Office) untuk preselect select2 di wizard.
        $so = DB::table('sales_orders as so')
            ->leftJoin('business_relations as pelanggan', 'so.id_pelanggan', '=', 'pelanggan.id_br')
            ->leftJoin('business_relation_sites as site_pelanggan', 'so.id_site_pelanggan', '=', 'site_pelanggan.id_site')
            ->leftJoin('business_relation_contacts as brc', 'brc.id_contact', '=', 'so.id_pic_pelanggan')
            ->leftJoin('business_relations as del', 'so.id_pelanggan_delivery', '=', 'del.id_br')
            ->leftJoin('business_relation_sites as site_del', 'so.id_site_pelanggan_delivery', '=', 'site_del.id_site')
            ->leftJoin('business_relation_contacts as brc_del', 'brc_del.id_contact', '=', 'so.id_pic_pelanggan_delivery')
            ->leftJoin('business_relations as pay', 'so.id_pelanggan_payment', '=', 'pay.id_br')
            ->leftJoin('business_relation_sites as site_pay', 'so.id_site_pelanggan_payment', '=', 'site_pay.id_site')
            ->leftJoin('business_relation_contacts as brc_pay', 'brc_pay.id_contact', '=', 'so.id_pic_pelanggan_payment')
            ->leftJoin('users as pic_i', 'pic_i.id', '=', 'so.pic_input')
            ->leftJoin('users as pic_o', 'pic_o.id', '=', 'so.pic_order')
            ->leftJoin('users as mkt_i', 'mkt_i.id', '=', 'so.pic_marketing_internal')
            ->leftJoin('users as mkt_e', 'mkt_e.id', '=', 'so.pic_marketing_eksternal')
            ->where('so.id_so', $id)
            ->whereNull('so.deleted_at')
            ->select([
                'so.*',
                'pelanggan.nama as nama_pelanggan',
                'site_pelanggan.nama_lokasi as nama_site_pelanggan',
                'brc.nama_pic as pic_pelanggan',
                'del.nama as nama_pelanggan_delivery',
                'site_del.nama_lokasi as nama_site_delivery',
                'brc_del.nama_pic as pic_delivery',
                'pay.nama as nama_pelanggan_payment',
                'site_pay.nama_lokasi as nama_site_payment',
                'brc_pay.nama_pic as pic_payment',
                'pic_i.name as nama_pic_input',
                'pic_o.name as nama_pic_order',
                'mkt_i.name as nama_marketing_internal',
                'mkt_e.name as nama_marketing_eksternal',
            ])
            ->first();
        if (!$so) return response()->json(['message' => 'Sales Order tidak ditemukan'], 404);

        $wos = DB::table('work_orders as wo')
            ->leftJoin('business_relation_sites as brs', 'brs.id_site', '=', 'wo.id_site_pelanggan_pekerjaan')
            ->leftJoin('users as pic', 'pic.id', '=', 'wo.id_pic_pelanggan_pekerjaan')
            ->where('wo.id_so', $id)
            ->whereNull('wo.deleted_at')
            ->orderBy('wo.id_wo')
            ->select([
                'wo.id_wo', 'wo.no_wo', 'wo.judul_pekerjaan',
                'wo.id_pelanggan_pekerjaan', 'wo.id_site_pelanggan_pekerjaan', 'brs.nama_lokasi as nama_site',
                'wo.id_pic_pelanggan_pekerjaan', 'pic.name as nama_pic',
                'wo.interval_bulan', 'wo.no_urut_period',
                'wo.tanggal_mulai', 'wo.tanggal_selesai', 'wo.keterangan',
            ])
            ->get();

        $woIds = $wos->pluck('id_wo');

        $boqRows = $woIds->isNotEmpty()
            ? DB::table('boq as b')
                ->leftJoin('testing_points as tp', 'b.id_testing_point', '=', 'tp.id_testing_point')
                ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
                ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
                ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 'b.id_satuan')
                ->whereIn('b.id_wo', $woIds)
                ->whereNull('b.deleted_at')
                ->orderBy('b.id_boq')
                ->select([
                    'b.id_boq as source_id_boq', 'b.id_wo', 'b.id_testing_point', 'b.item_produk_alternate',
                    'tp.nama as nama_testing_point', 'ts.nomor as standard_nomor', 'ts.judul as standard_judul',
                    'tms.kode as matriks_kode', 'tms.judul_indonesia as matriks_judul',
                    'b.qty', 'b.id_satuan', 'sat.nama as satuan', 'b.harga', 'b.keterangan',
                ])
                ->get()
                ->groupBy('id_wo')
            : collect();

        $boqTambahanBase = $woIds->isNotEmpty()
            ? DB::table('boq_tambahan as bt')
                ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 'bt.id_satuan')
                ->whereIn('bt.id_wo', $woIds)
                ->whereNull('bt.deleted_at')
                ->orderBy('bt.id_boq_tambahan')
                ->select(['bt.id_boq_tambahan as source_id_boq_tambahan', 'bt.id_wo', 'bt.jenis', 'bt.nama_item', 'bt.qty', 'bt.id_satuan', 'sat.nama as satuan', 'bt.harga', 'bt.keterangan'])
                ->get()
            : collect();

        $boqOtherByWo    = $boqTambahanBase->where('jenis', 'lainnya')->groupBy('id_wo');
        $boqSamplingByWo = $boqTambahanBase->where('jenis', 'sampling')->groupBy('id_wo');

        $wos = $wos->map(function ($wo) use ($boqRows, $boqOtherByWo, $boqSamplingByWo) {
            $wo->boq          = array_values($boqRows->get($wo->id_wo, collect())->toArray());
            $wo->boq_other    = array_values($boqOtherByWo->get($wo->id_wo, collect())->toArray());
            $wo->boq_sampling = array_values($boqSamplingByWo->get($wo->id_wo, collect())->toArray());
            return $wo;
        });

        return response()->json(['so' => $so, 'wos' => $wos]);
    }

    /**
     * Eksekusi Clone SO — 1 DB::transaction() menyeluruh: gagal di titik mana
     * pun (baris tengah sekalipun) membatalkan SEMUA insert yang sudah
     * terjadi di request ini, tidak ada SO/WO/BOQ setengah jadi tersimpan.
     *
     * Prinsip keamanan data: field identitas/referensial (id_testing_point,
     * id_pelanggan_pekerjaan WO, kepemilikan Site ke Perusahaan) DIAMBIL DARI
     * DB berdasarkan `source_id_*` yang dikirim client, TIDAK dipercaya
     * langsung dari payload — supaya request yang dimanipulasi tidak bisa
     * menyisipkan referensi ke record WO/BOQ milik SO lain. Field yang murni
     * nilai (qty, harga, keterangan, tanggal, dst) baru diambil dari input
     * user sesuai hasil edit di wizard.
     */
    public function clonePost(Request $request, $id)
    {
        $sourceSo = DB::table('sales_orders')->where('id_so', $id)->whereNull('deleted_at')->first();
        if (!$sourceSo) {
            return response()->json(['message' => 'Sales Order sumber tidak ditemukan'], 404);
        }

        // ⚠️ PENTING: $request->validate() cuma mengembalikan field yang PUNYA
        // rule di sini (Laravel Validator::validated() membuang field yang
        // tidak dideklarasikan) — bug nyata yang sempat kejadian: cuma
        // tanggal_so & 3 id_pelanggan yang dulu dideklarasikan, jadi semua
        // field SO lain (judul_order, no_po, id_office, PIC, dst) senyap
        // hilang saat insert walau terkirim benar dari frontend. Setiap field
        // SO yang dipakai di insert WAJIB punya baris rule di sini juga.
        $validated = $request->validate([
            'so'                              => 'required|array',
            'so.tanggal_so'                   => 'required|date',
            'so.judul_order'                  => 'nullable|string|max:255',
            'so.tidak_ada_po'                 => 'nullable|boolean',
            'so.no_po'                        => 'nullable|string|max:50',
            'so.tanggal_po'                   => 'nullable|date',
            'so.tanggal_mulai'                => 'nullable|date',
            'so.tanggal_selesai'              => 'nullable|date',
            'so.id_office'                    => 'nullable|integer',
            'so.id_pelanggan'                 => 'required|integer',
            'so.id_site_pelanggan'            => 'nullable|integer',
            'so.id_pic_pelanggan'             => 'nullable|integer',
            'so.id_pelanggan_delivery'        => 'nullable|integer',
            'so.id_site_pelanggan_delivery'   => 'nullable|integer',
            'so.id_pic_pelanggan_delivery'    => 'nullable|integer',
            'so.id_pelanggan_payment'         => 'nullable|integer',
            'so.id_site_pelanggan_payment'    => 'nullable|integer',
            'so.id_pic_pelanggan_payment'     => 'nullable|integer',
            'so.pic_input'                    => 'nullable|integer',
            'so.pic_order'                    => 'nullable|integer',
            'so.pic_marketing_internal'       => 'nullable|integer',
            'so.pic_marketing_eksternal'      => 'nullable|integer',
            'so.keterangan_status'            => 'nullable|string',
            'so.cara_pembayaran'              => 'nullable|string',
            'so.keterangan'                   => 'nullable|string',
            'wos'                             => 'nullable|array',
            'wos.*.source_id_wo'              => 'required|integer',
            'wos.*.include'                   => 'required|boolean',
            'wos.*.judul_pekerjaan'           => 'required|string|max:255',
            'wos.*.id_site_pelanggan_pekerjaan' => 'nullable|integer',
            'wos.*.id_pic_pelanggan_pekerjaan'  => 'nullable|integer',
            'wos.*.interval_bulan'            => 'nullable|integer',
            'wos.*.no_urut_period'            => 'nullable|integer',
            'wos.*.tanggal_mulai'             => 'nullable|date',
            'wos.*.tanggal_selesai'           => 'nullable|date',
            'wos.*.keterangan'                => 'nullable|string',
            'wos.*.boq'                       => 'nullable|array',
            'wos.*.boq.*.source_id_boq'       => 'required|integer',
            'wos.*.boq.*.include'             => 'required|boolean',
            'wos.*.boq.*.qty'                 => 'nullable|integer',
            'wos.*.boq.*.id_satuan'           => 'nullable|integer',
            'wos.*.boq.*.harga'               => 'nullable|integer',
            'wos.*.boq.*.keterangan'          => 'nullable|string',
            'wos.*.boq_other'                 => 'nullable|array',
            'wos.*.boq_other.*.source_id_boq_tambahan' => 'required|integer',
            'wos.*.boq_other.*.include'       => 'required|boolean',
            'wos.*.boq_other.*.nama_item'     => 'required|string|max:255',
            'wos.*.boq_other.*.qty'           => 'nullable|integer',
            'wos.*.boq_other.*.id_satuan'     => 'nullable|integer',
            'wos.*.boq_other.*.harga'         => 'nullable|integer',
            'wos.*.boq_other.*.keterangan'    => 'nullable|string',
            'wos.*.boq_sampling'              => 'nullable|array',
            'wos.*.boq_sampling.*.source_id_boq_tambahan' => 'required|integer',
            'wos.*.boq_sampling.*.include'    => 'required|boolean',
            'wos.*.boq_sampling.*.nama_item'  => 'required|string|max:255',
            'wos.*.boq_sampling.*.qty'        => 'nullable|integer',
            'wos.*.boq_sampling.*.id_satuan'  => 'nullable|integer',
            'wos.*.boq_sampling.*.harga'      => 'nullable|integer',
            'wos.*.boq_sampling.*.keterangan' => 'nullable|string',
        ]);

        $soInput = $validated['so'];
        $wosInput = collect($validated['wos'] ?? [])->where('include', true)->values();

        try {
            $newSoId = DB::transaction(function () use ($id, $soInput, $wosInput) {
                $newNoSo = $this->generateSoNumber();

                $newSoId = DB::table('sales_orders')->insertGetId([
                    'id_sq'      => null,
                    'id_sc'      => null,
                    'no_so'      => $newNoSo,
                    'tanggal_so' => $soInput['tanggal_so'],
                    'judul_order' => $soInput['judul_order'] ?? null,
                    'tidak_ada_po' => $soInput['tidak_ada_po'] ?? 0,
                    'no_po' => $soInput['no_po'] ?? null,
                    'tanggal_po' => $soInput['tanggal_po'] ?? null,
                    'tanggal_mulai' => $soInput['tanggal_mulai'] ?? null,
                    'tanggal_selesai' => $soInput['tanggal_selesai'] ?? null,
                    'id_office' => $soInput['id_office'] ?? null,
                    'id_pelanggan' => $soInput['id_pelanggan'],
                    'id_site_pelanggan' => $soInput['id_site_pelanggan'] ?? null,
                    'id_pic_pelanggan' => $soInput['id_pic_pelanggan'] ?? null,
                    'id_pelanggan_delivery' => $soInput['id_pelanggan_delivery'] ?? null,
                    'id_site_pelanggan_delivery' => $soInput['id_site_pelanggan_delivery'] ?? null,
                    'id_pic_pelanggan_delivery' => $soInput['id_pic_pelanggan_delivery'] ?? null,
                    'id_pelanggan_payment' => $soInput['id_pelanggan_payment'] ?? null,
                    'id_site_pelanggan_payment' => $soInput['id_site_pelanggan_payment'] ?? null,
                    'id_pic_pelanggan_payment' => $soInput['id_pic_pelanggan_payment'] ?? null,
                    'pic_input' => $soInput['pic_input'] ?? null,
                    'pic_order' => $soInput['pic_order'] ?? null,
                    'pic_marketing_internal' => $soInput['pic_marketing_internal'] ?? null,
                    'pic_marketing_eksternal' => $soInput['pic_marketing_eksternal'] ?? null,
                    'status' => 'on-progress',
                    'keterangan_status' => $soInput['keterangan_status'] ?? null,
                    'cara_pembayaran' => $soInput['cara_pembayaran'] ?? null,
                    'keterangan' => $soInput['keterangan'] ?? null,
                    'attachment' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($wosInput as $woInput) {
                    // Ambil ulang WO sumber dari DB (bukan dari payload) untuk
                    // pastikan benar-benar milik SO ini & ambil Perusahaan
                    // (id_pelanggan_pekerjaan) yang dikunci ikut sumber, sama
                    // seperti pola Clone WO yang sudah ada.
                    $sourceWo = DB::table('work_orders')
                        ->where('id_wo', $woInput['source_id_wo'])
                        ->where('id_so', $id)
                        ->whereNull('deleted_at')
                        ->first();
                    if (!$sourceWo) continue;

                    $targetSiteId = $woInput['id_site_pelanggan_pekerjaan'] ?? $sourceWo->id_site_pelanggan_pekerjaan;
                    if ($targetSiteId) {
                        $siteBelongsToSameBr = DB::table('business_relation_sites')
                            ->where('id_site', $targetSiteId)
                            ->where('id_br', $sourceWo->id_pelanggan_pekerjaan)
                            ->exists();
                        if (!$siteBelongsToSameBr) {
                            throw new \RuntimeException("Site yang dipilih untuk WO \"{$sourceWo->no_wo}\" bukan milik Perusahaan yang sama dengan WO sumber");
                        }
                    }

                    $newNoWo = $this->generateNoWo();

                    $newWoId = DB::table('work_orders')->insertGetId([
                        'no_wo'                       => $newNoWo,
                        'id_so'                       => $newSoId,
                        'id_pelanggan_pekerjaan'      => $sourceWo->id_pelanggan_pekerjaan,
                        'id_site_pelanggan_pekerjaan' => $targetSiteId,
                        'id_pic_pelanggan_pekerjaan'  => $woInput['id_pic_pelanggan_pekerjaan'] ?? $sourceWo->id_pic_pelanggan_pekerjaan,
                        'judul_pekerjaan'             => $woInput['judul_pekerjaan'],
                        'interval_bulan'              => $woInput['interval_bulan'] ?? null,
                        'no_urut_period'              => $woInput['no_urut_period'] ?? null,
                        'keterangan'                  => $woInput['keterangan'] ?? null,
                        'status'                      => 'onprogress',
                        'tanggal_mulai'               => $woInput['tanggal_mulai'] ?? null,
                        'tanggal_selesai'             => $woInput['tanggal_selesai'] ?? null,
                        'created_at'                  => now(),
                        'updated_at'                  => now(),
                    ]);

                    foreach (($woInput['boq'] ?? []) as $boqInput) {
                        if (empty($boqInput['include'])) continue;

                        $sourceBoq = DB::table('boq')
                            ->where('id_boq', $boqInput['source_id_boq'])
                            ->where('id_wo', $sourceWo->id_wo)
                            ->whereNull('deleted_at')
                            ->first();
                        if (!$sourceBoq) continue;

                        $newBoqId = DB::table('boq')->insertGetId([
                            'id_wo'                 => $newWoId,
                            'id_testing_point'      => $sourceBoq->id_testing_point,
                            'item_produk_alternate' => $sourceBoq->item_produk_alternate,
                            'qty'                   => $boqInput['qty'] ?? $sourceBoq->qty,
                            'id_satuan'             => $boqInput['id_satuan'] ?? $sourceBoq->id_satuan,
                            'harga'                 => $boqInput['harga'] ?? $sourceBoq->harga,
                            'keterangan'            => $boqInput['keterangan'] ?? $sourceBoq->keterangan,
                            'created_at'            => now(),
                            'updated_at'            => now(),
                        ]);

                        $sourceBoqItems = DB::table('boq_items')
                            ->where('id_boq', $sourceBoq->id_boq)
                            ->whereNull('deleted_at')
                            ->get(['id_testing_item']);
                        foreach ($sourceBoqItems as $boqItem) {
                            DB::table('boq_items')->insert([
                                'id_boq'           => $newBoqId,
                                'id_testing_item'  => $boqItem->id_testing_item,
                                'created_at'       => now(),
                                'updated_at'       => now(),
                            ]);
                        }
                    }

                    foreach (['boq_other' => 'lainnya', 'boq_sampling' => 'sampling'] as $inputKey => $jenis) {
                        foreach (($woInput[$inputKey] ?? []) as $btInput) {
                            if (empty($btInput['include'])) continue;

                            $sourceBt = DB::table('boq_tambahan')
                                ->where('id_boq_tambahan', $btInput['source_id_boq_tambahan'])
                                ->where('id_wo', $sourceWo->id_wo)
                                ->where('jenis', $jenis)
                                ->whereNull('deleted_at')
                                ->first();
                            if (!$sourceBt) continue;

                            DB::table('boq_tambahan')->insert([
                                'id_wo'      => $newWoId,
                                'jenis'      => $jenis,
                                'nama_item'  => $btInput['nama_item'] ?? $sourceBt->nama_item,
                                'qty'        => $btInput['qty'] ?? $sourceBt->qty,
                                'id_satuan'  => $btInput['id_satuan'] ?? $sourceBt->id_satuan,
                                'harga'      => $btInput['harga'] ?? $sourceBt->harga,
                                'keterangan' => $btInput['keterangan'] ?? $sourceBt->keterangan,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }

                $after = DB::table('sales_orders')->where('id_so', $newSoId)->get()->toJson();
                // old_value/new_value punya CHECK constraint JSON valid (MariaDB) —
                // catatan "clone dari mana" harus dibungkus JSON, tidak bisa
                // string polos.
                $cloneNote = json_encode(['note' => "Clone dari SO {$this->sourceNoSoLabel($id)}"]);
                saveAudit('sales_orders', $newSoId, 'Create', $cloneNote, $after);

                return $newSoId;
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => $e instanceof \RuntimeException ? $e->getMessage() : 'Gagal membuat salinan Sales Order. Tidak ada data yang tersimpan.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sales Order berhasil disalin',
            'id_so'   => $newSoId,
        ]);
    }

    private function sourceNoSoLabel($id): string
    {
        return DB::table('sales_orders')->where('id_so', $id)->value('no_so') ?? "#{$id}";
    }

    /**
     * Nomor WO baru untuk hasil clone — pola sama seperti
     * WorkOrderController::generateNoWo() (private di controller itu,
     * jadi disalin ke sini, bukan dipanggil lintas controller).
     */
    private function generateNoWo(): string
    {
        $year = now()->format('y');
        $prefix = "WO-{$year}-";

        $latest = DB::table('work_orders')->orderByDesc('created_at')->first();
        if (!$latest) return $prefix . '0001';

        $number = (int) explode('-', $latest->no_wo)[2] + 1;
        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
