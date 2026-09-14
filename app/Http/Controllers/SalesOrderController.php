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


    /**
     * Field wajib diisi untuk SO — disepakati bersama user (2026-09-14):
     * sebelumnya cuma tanggal_so & id_pelanggan yang wajib di backend,
     * sekarang ditambah Tanggal Mulai/Selesai + ketiga kategori Data
     * Pelanggan (Pemesan/Pengiriman/Pembayaran: Perusahaan+Site+PIC) + PIC
     * Input/Order + Marketing Internal. Sengaja TIDAK dibuat NOT NULL di
     * level database — sudah dicek ada data SO lama yang kolom-kolom ini
     * masih NULL (SO-26-0002, 0005, 0009, dst), ALTER TABLE akan gagal
     * (dicoba langsung, error 1265 "Data truncated"). Wajib berlaku untuk
     * SO baru & update ke depan saja, data lama tidak disentuh/dipaksa.
     */
    private function soRequiredRules(): array
    {
        return [
            'tanggal_so' => 'required|date',
            'judul_order' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date',
            'id_pelanggan' => 'required|integer',
            'id_site_pelanggan' => 'required|integer',
            'id_pic_pelanggan' => 'required|integer',
            'id_pelanggan_delivery' => 'required|integer',
            'id_site_pelanggan_delivery' => 'required|integer',
            'id_pic_pelanggan_delivery' => 'required|integer',
            'id_pelanggan_payment' => 'required|integer',
            'id_site_pelanggan_payment' => 'required|integer',
            'id_pic_pelanggan_payment' => 'required|integer',
            'pic_input' => 'required|integer',
            'pic_order' => 'required|integer',
            'pic_marketing_internal' => 'required|integer',
        ];
    }

    private function soRequiredMessages(): array
    {
        return [
            'tanggal_so.required' => 'Tanggal SO wajib diisi.',
            'judul_order.required' => 'Judul Order wajib diisi.',
            'tanggal_mulai.required' => 'Tanggal Mulai wajib diisi.',
            'tanggal_selesai.required' => 'Tanggal Selesai wajib diisi.',
            'id_pelanggan.required' => 'Perusahaan pada Data Pemesan wajib dipilih.',
            'id_site_pelanggan.required' => 'Site pada Data Pemesan wajib dipilih.',
            'id_pic_pelanggan.required' => 'PIC pada Data Pemesan wajib dipilih.',
            'id_pelanggan_delivery.required' => 'Perusahaan pada Data Pengiriman wajib dipilih.',
            'id_site_pelanggan_delivery.required' => 'Site pada Data Pengiriman wajib dipilih.',
            'id_pic_pelanggan_delivery.required' => 'PIC pada Data Pengiriman wajib dipilih.',
            'id_pelanggan_payment.required' => 'Perusahaan pada Data Pembayaran wajib dipilih.',
            'id_site_pelanggan_payment.required' => 'Site pada Data Pembayaran wajib dipilih.',
            'id_pic_pelanggan_payment.required' => 'PIC pada Data Pembayaran wajib dipilih.',
            'pic_input.required' => 'PIC Input wajib dipilih.',
            'pic_order.required' => 'PIC Order wajib dipilih.',
            'pic_marketing_internal.required' => 'Marketing Internal wajib dipilih.',
        ];
    }

    public function store(Request $request)
    {
        // ==========================
        // VALIDATION
        // ==========================
        $request->validate(array_merge($this->soRequiredRules(), [
            'attachments'   => 'nullable|array',
            'attachments.*' => 'nullable|file|max:153600',
        ]), $this->soRequiredMessages());

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
        $validated = $request->validate(array_merge($this->soRequiredRules(), [
            // tanggal_selesai wajib DAN tidak boleh sebelum tanggal_mulai.
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'tidak_ada_po' => 'nullable|boolean',
            'tanggal_po' => 'nullable|date',
            'no_po' => 'nullable|string|max:100',
            'id_office' => 'nullable|integer',
            'pic_marketing_eksternal' => 'nullable|integer',
            'id_sc' => 'nullable|integer',
            'keterangan_status' => 'nullable|string',
            'cara_pembayaran'   => 'nullable|string',
            'keterangan'        => 'nullable|string',

            'attachments'            => 'nullable|array',
            'attachments.*'          => 'nullable|file|max:153600',
            'existing_attachments'   => 'nullable|array',
            'existing_attachments.*' => 'nullable|string',
        ]), $this->soRequiredMessages());

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

        // ── Fase 2: FWO + Fieldwork BOQ + FWO BOQ Other/Sampling + Personel ──
        $fwos = $woIds->isNotEmpty()
            ? DB::table('fieldworks as fw')
                ->leftJoin('business_relation_sites as brs', 'brs.id_site', '=', 'fw.id_site_pelanggan_pekerjaan')
                ->leftJoin('business_relation_contacts as brc', 'brc.id_contact', '=', 'fw.id_pic_pelanggan_pekerjaan')
                ->whereIn('fw.id_wo', $woIds)
                ->whereNull('fw.deleted_at')
                ->orderBy('fw.id_fwo')
                ->select([
                    'fw.id_fwo', 'fw.id_wo', 'fw.no_fwo', 'fw.judul_pekerjaan',
                    'fw.id_site_pelanggan_pekerjaan', 'brs.nama_lokasi as nama_site',
                    'fw.id_pic_pelanggan_pekerjaan', 'brc.nama_pic as nama_pic',
                    'fw.tanggal_mulai', 'fw.tanggal_selesai', 'fw.waktu_kedatangan', 'fw.keterangan',
                ])
                ->get()
                ->groupBy('id_wo')
            : collect();

        $fwoIds = $woIds->isNotEmpty()
            ? DB::table('fieldworks')->whereIn('id_wo', $woIds)->whereNull('deleted_at')->pluck('id_fwo')
            : collect();

        $fieldworkBoqRows = $fwoIds->isNotEmpty()
            ? DB::table('fieldwork_boq as fb')
                ->leftJoin('testing_points as tp', 'fb.id_testing_point', '=', 'tp.id_testing_point')
                ->whereIn('fb.id_fwo', $fwoIds)
                ->whereNull('fb.deleted_at')
                ->orderBy('fb.id_fwo_boq')
                ->select([
                    'fb.id_fwo_boq as source_id_fwo_boq', 'fb.id_fwo', 'fb.id_testing_point',
                    'tp.nama as nama_testing_point', 'fb.qty', 'fb.keterangan',
                ])
                ->get()
                ->groupBy('id_fwo')
            : collect();

        $fwoBtBase = $fwoIds->isNotEmpty()
            ? DB::table('fwo_boq_tambahan as bt')
                ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 'bt.id_satuan')
                ->whereIn('bt.id_fwo', $fwoIds)
                ->whereNull('bt.deleted_at')
                ->orderBy('bt.id_fwo_boq_tambahan')
                ->select(['bt.id_fwo_boq_tambahan as source_id_fwo_boq_tambahan', 'bt.id_fwo', 'bt.jenis', 'bt.nama_item', 'bt.qty', 'bt.id_satuan', 'sat.nama as satuan', 'bt.harga', 'bt.keterangan'])
                ->get()
            : collect();
        $fwoBoqOtherByFwo    = $fwoBtBase->where('jenis', 'lainnya')->groupBy('id_fwo');
        $fwoBoqSamplingByFwo = $fwoBtBase->where('jenis', 'sampling')->groupBy('id_fwo');

        $fwoPersonelRows = $fwoIds->isNotEmpty()
            ? DB::table('fieldwork_personels as fp')
                ->leftJoin('personnel as p', 'p.id_personnel', '=', 'fp.id_personnel')
                ->whereIn('fp.id_fwo', $fwoIds)
                ->orderBy('fp.id_fwo_personel')
                ->select(['fp.id_fwo_personel as source_id_fwo_personel', 'fp.id_fwo', 'fp.id_personnel', 'p.nama as nama_personnel', 'fp.role'])
                ->get()
                ->groupBy('id_fwo')
            : collect();

        $wos = $wos->map(function ($wo) use (
            $boqRows, $boqOtherByWo, $boqSamplingByWo,
            $fwos, $fieldworkBoqRows, $fwoBoqOtherByFwo, $fwoBoqSamplingByFwo, $fwoPersonelRows
        ) {
            $wo->boq          = array_values($boqRows->get($wo->id_wo, collect())->toArray());
            $wo->boq_other    = array_values($boqOtherByWo->get($wo->id_wo, collect())->toArray());
            $wo->boq_sampling = array_values($boqSamplingByWo->get($wo->id_wo, collect())->toArray());

            $wo->fwos = $fwos->get($wo->id_wo, collect())->map(function ($fwo) use (
                $fieldworkBoqRows, $fwoBoqOtherByFwo, $fwoBoqSamplingByFwo, $fwoPersonelRows
            ) {
                $fwo->fieldwork_boq    = array_values($fieldworkBoqRows->get($fwo->id_fwo, collect())->toArray());
                $fwo->fwo_boq_other    = array_values($fwoBoqOtherByFwo->get($fwo->id_fwo, collect())->toArray());
                $fwo->fwo_boq_sampling = array_values($fwoBoqSamplingByFwo->get($fwo->id_fwo, collect())->toArray());
                $fwo->personel         = array_values($fwoPersonelRows->get($fwo->id_fwo, collect())->toArray());
                return $fwo;
            })->values()->toArray();

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
            'so.tanggal_selesai'              => 'nullable|date|after_or_equal:so.tanggal_mulai',
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
            'wos.*.tanggal_selesai'           => 'nullable|date|after_or_equal:wos.*.tanggal_mulai',
            'wos.*.keterangan'                => 'nullable|string',
            // source_id_boq(_tambahan) nullable — baris BARU (bukan hasil
            // clone, ditambah user lewat "+ Tambah BOQ"/"+ Tambah Item")
            // tidak punya source sama sekali. Baris baru BOQ WAJIB kirim
            // id_testing_point + minimal 1 id_testing_items (divalidasi
            // manual di bawah, bukan lewat rule di sini, karena wajib/tidaknya
            // bergantung ada-tidaknya source_id_boq per baris).
            'wos.*.boq'                       => 'nullable|array',
            'wos.*.boq.*.source_id_boq'       => 'nullable|integer',
            'wos.*.boq.*.include'             => 'required|boolean',
            'wos.*.boq.*.qty'                 => 'nullable|integer',
            'wos.*.boq.*.id_satuan'           => 'nullable|integer',
            'wos.*.boq.*.harga'               => 'nullable|integer',
            'wos.*.boq.*.keterangan'          => 'nullable|string',
            'wos.*.boq.*.id_testing_point'    => 'nullable|integer',
            'wos.*.boq.*.id_testing_items'    => 'nullable|array',
            'wos.*.boq.*.id_testing_items.*'  => 'integer',
            'wos.*.boq_other'                 => 'nullable|array',
            'wos.*.boq_other.*.source_id_boq_tambahan' => 'nullable|integer',
            'wos.*.boq_other.*.include'       => 'required|boolean',
            'wos.*.boq_other.*.nama_item'     => 'required|string|max:255',
            'wos.*.boq_other.*.qty'           => 'nullable|integer',
            'wos.*.boq_other.*.id_satuan'     => 'nullable|integer',
            'wos.*.boq_other.*.harga'         => 'nullable|integer',
            'wos.*.boq_other.*.keterangan'    => 'nullable|string',
            'wos.*.boq_sampling'              => 'nullable|array',
            'wos.*.boq_sampling.*.source_id_boq_tambahan' => 'nullable|integer',
            'wos.*.boq_sampling.*.include'    => 'required|boolean',
            'wos.*.boq_sampling.*.nama_item'  => 'required|string|max:255',
            'wos.*.boq_sampling.*.qty'        => 'nullable|integer',
            'wos.*.boq_sampling.*.id_satuan'  => 'nullable|integer',
            'wos.*.boq_sampling.*.harga'      => 'nullable|integer',
            'wos.*.boq_sampling.*.keterangan' => 'nullable|string',

            // ── Fase 2: FWO ── (source_id_fwo_* nullable = baris baru,
            // sama pola seperti source_id_boq di BOQ — lihat catatan di sana)
            'wos.*.fwos'                             => 'nullable|array',
            'wos.*.fwos.*.source_id_fwo'             => 'required|integer',
            'wos.*.fwos.*.include'                   => 'required|boolean',
            'wos.*.fwos.*.judul_pekerjaan'            => 'required|string|max:500',
            'wos.*.fwos.*.id_site_pelanggan_pekerjaan' => 'nullable|integer',
            'wos.*.fwos.*.id_pic_pelanggan_pekerjaan' => 'required|integer',
            'wos.*.fwos.*.tanggal_mulai'              => 'nullable|date',
            'wos.*.fwos.*.tanggal_selesai'            => 'nullable|date|after_or_equal:wos.*.fwos.*.tanggal_mulai',
            'wos.*.fwos.*.waktu_kedatangan'           => 'nullable|date',
            'wos.*.fwos.*.keterangan'                 => 'nullable|string',

            'wos.*.fwos.*.fieldwork_boq'                     => 'nullable|array',
            'wos.*.fwos.*.fieldwork_boq.*.source_id_fwo_boq' => 'nullable|integer',
            'wos.*.fwos.*.fieldwork_boq.*.include'           => 'required|boolean',
            'wos.*.fwos.*.fieldwork_boq.*.id_testing_point'  => 'nullable|integer',
            'wos.*.fwos.*.fieldwork_boq.*.qty'               => 'nullable|integer',
            'wos.*.fwos.*.fieldwork_boq.*.keterangan'        => 'nullable|string',

            'wos.*.fwos.*.fwo_boq_other'                  => 'nullable|array',
            'wos.*.fwos.*.fwo_boq_other.*.source_id_fwo_boq_tambahan' => 'nullable|integer',
            'wos.*.fwos.*.fwo_boq_other.*.include'        => 'required|boolean',
            'wos.*.fwos.*.fwo_boq_other.*.nama_item'      => 'required|string|max:255',
            'wos.*.fwos.*.fwo_boq_other.*.qty'            => 'nullable|integer',
            'wos.*.fwos.*.fwo_boq_other.*.id_satuan'      => 'nullable|integer',
            'wos.*.fwos.*.fwo_boq_other.*.harga'          => 'nullable|integer',
            'wos.*.fwos.*.fwo_boq_other.*.keterangan'     => 'nullable|string',

            'wos.*.fwos.*.fwo_boq_sampling'                  => 'nullable|array',
            'wos.*.fwos.*.fwo_boq_sampling.*.source_id_fwo_boq_tambahan' => 'nullable|integer',
            'wos.*.fwos.*.fwo_boq_sampling.*.include'        => 'required|boolean',
            'wos.*.fwos.*.fwo_boq_sampling.*.nama_item'      => 'required|string|max:255',
            'wos.*.fwos.*.fwo_boq_sampling.*.qty'            => 'nullable|integer',
            'wos.*.fwos.*.fwo_boq_sampling.*.id_satuan'      => 'nullable|integer',
            'wos.*.fwos.*.fwo_boq_sampling.*.harga'          => 'nullable|integer',
            'wos.*.fwos.*.fwo_boq_sampling.*.keterangan'     => 'nullable|string',

            'wos.*.fwos.*.personel'                          => 'nullable|array',
            'wos.*.fwos.*.personel.*.source_id_fwo_personel' => 'nullable|integer',
            'wos.*.fwos.*.personel.*.include'                => 'required|boolean',
            'wos.*.fwos.*.personel.*.id_personnel'           => 'required|integer',
            'wos.*.fwos.*.personel.*.role'                   => 'nullable|string|max:500',
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

                // Dilaporkan lewat pesan sukses di akhir — bukan dianggap
                // gagal (kesepakatan Fase 2): alokasi Fieldwork BOQ yang
                // testing point-nya ternyata tidak ada di BOQ WO hasil clone
                // (BOQ WO terkait tidak dicentang "Sertakan").
                $skippedFwoBoqTotal = 0;

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

                    // Dipakai untuk validasi rentang tanggal FWO terhadap WO
                    // (Fase 2, di bawah) — pakai tanggal WO HASIL CLONE (yang
                    // mungkin sudah diedit user), bukan tanggal WO sumber.
                    $newWoMulai   = $woInput['tanggal_mulai'] ?? null;
                    $newWoSelesai = $woInput['tanggal_selesai'] ?? null;

                    // Dipakai untuk menolak 2 BOQ dengan Testing Point yang
                    // sama dalam 1 WO (baik hasil clone maupun baris baru) —
                    // aturan sama seperti BoqController::store(). Sudah
                    // dicek juga di frontend, tapi wajib dicek ulang di sini
                    // karena frontend tidak pernah dipercaya untuk hal yang
                    // menentukan integritas data.
                    $usedTestingPoints = [];
                    // id_testing_point => id_boq baru — dipakai untuk remap
                    // Fieldwork BOQ (Fase 2) ke BOQ WO hasil clone yang benar
                    // (fieldwork_boq.id_testing_point didenormalisasi, jadi
                    // remap-nya lewat testing point, bukan id_boq lama).
                    $woBoqIdByTestingPoint = [];

                    foreach (($woInput['boq'] ?? []) as $boqInput) {
                        if (empty($boqInput['include'])) continue;

                        if (!empty($boqInput['source_id_boq'])) {
                            // Clone dari BOQ sumber — identitas (id_testing_point,
                            // item_produk_alternate) diambil ulang dari DB.
                            $sourceBoq = DB::table('boq')
                                ->where('id_boq', $boqInput['source_id_boq'])
                                ->where('id_wo', $sourceWo->id_wo)
                                ->whereNull('deleted_at')
                                ->first();
                            if (!$sourceBoq) continue;

                            if (in_array($sourceBoq->id_testing_point, $usedTestingPoints)) {
                                throw new \RuntimeException("Testing Point untuk BOQ pada WO \"{$sourceWo->no_wo}\" terdeteksi duplikat.");
                            }
                            $usedTestingPoints[] = $sourceBoq->id_testing_point;

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
                            $woBoqIdByTestingPoint[$sourceBoq->id_testing_point] = $newBoqId;

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
                        } else {
                            // Baris BOQ BARU (ditambah user lewat "+ Tambah
                            // BOQ", bukan hasil clone) — tidak ada source
                            // untuk dijadikan acuan, jadi id_testing_point &
                            // id_testing_items divalidasi manual di sini,
                            // pola sama seperti BoqController::store().
                            $newTestingPointId = $boqInput['id_testing_point'] ?? null;
                            if (!$newTestingPointId) {
                                throw new \RuntimeException("Ada baris BOQ baru pada WO \"{$sourceWo->no_wo}\" yang belum memilih Testing Point.");
                            }

                            $testingPointExists = DB::table('testing_points')
                                ->where('id_testing_point', $newTestingPointId)
                                ->whereNull('deleted_at')
                                ->exists();
                            if (!$testingPointExists) {
                                throw new \RuntimeException("Testing Point yang dipilih untuk BOQ baru pada WO \"{$sourceWo->no_wo}\" tidak valid.");
                            }

                            if (in_array($newTestingPointId, $usedTestingPoints)) {
                                throw new \RuntimeException("Testing Point yang dipilih sudah dipakai di BOQ lain pada WO \"{$sourceWo->no_wo}\".");
                            }
                            $usedTestingPoints[] = $newTestingPointId;

                            $validItemIds = DB::table('testing_items')
                                ->where('id_testing_point', $newTestingPointId)
                                ->whereIn('id_testing_item', $boqInput['id_testing_items'] ?? [])
                                ->pluck('id_testing_item');
                            if ($validItemIds->isEmpty()) {
                                throw new \RuntimeException("Pilih minimal 1 Testing Item untuk BOQ baru pada WO \"{$sourceWo->no_wo}\".");
                            }

                            // Kolom qty & harga di DB NOT NULL (default 0) —
                            // qty=0 tidak masuk akal untuk item BOQ target,
                            // jadi ditolak eksplisit di sini (bukan cuma
                            // dibiarkan default ke 0 secara diam-diam).
                            if (empty($boqInput['qty']) || $boqInput['qty'] < 1) {
                                throw new \RuntimeException("Isi Qty (minimal 1) untuk BOQ baru pada WO \"{$sourceWo->no_wo}\".");
                            }

                            $newBoqId = DB::table('boq')->insertGetId([
                                'id_wo'                 => $newWoId,
                                'id_testing_point'      => $newTestingPointId,
                                'item_produk_alternate' => null,
                                'qty'                   => $boqInput['qty'] ?? 0,
                                'id_satuan'             => $boqInput['id_satuan'] ?? null,
                                'harga'                 => $boqInput['harga'] ?? 0,
                                'keterangan'            => $boqInput['keterangan'] ?? null,
                                'created_at'            => now(),
                                'updated_at'            => now(),
                            ]);
                            $woBoqIdByTestingPoint[$newTestingPointId] = $newBoqId;

                            DB::table('boq_items')->insert($validItemIds->map(fn ($itemId) => [
                                'id_boq'          => $newBoqId,
                                'id_testing_item' => $itemId,
                                'created_at'      => now(),
                                'updated_at'      => now(),
                            ])->toArray());
                        }
                    }

                    foreach (['boq_other' => 'lainnya', 'boq_sampling' => 'sampling'] as $inputKey => $jenis) {
                        foreach (($woInput[$inputKey] ?? []) as $btInput) {
                            if (empty($btInput['include'])) continue;

                            if (!empty($btInput['source_id_boq_tambahan'])) {
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
                            } else {
                                // Baris baru — bukan identitas/referensi ke
                                // record lain, aman langsung dari input
                                // (nama_item sudah divalidasi wajib di rule).
                                // qty NOT NULL di DB — ditolak eksplisit
                                // kalau kosong/0, sama seperti BOQ baru.
                                if (empty($btInput['qty']) || $btInput['qty'] < 1) {
                                    $label = $jenis === 'sampling' ? 'BOQ Sampling' : 'BOQ Other';
                                    throw new \RuntimeException("Isi Qty (minimal 1) untuk item {$label} \"{$btInput['nama_item']}\" pada WO \"{$sourceWo->no_wo}\".");
                                }

                                DB::table('boq_tambahan')->insert([
                                    'id_wo'      => $newWoId,
                                    'jenis'      => $jenis,
                                    'nama_item'  => $btInput['nama_item'],
                                    'qty'        => $btInput['qty'] ?? 0,
                                    'id_satuan'  => $btInput['id_satuan'] ?? null,
                                    'harga'      => $btInput['harga'] ?? 0,
                                    'keterangan' => $btInput['keterangan'] ?? null,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    }

                    // ── Fase 2: FWO + Fieldwork BOQ + FWO BOQ Other/Sampling + Personel ──
                    foreach (($woInput['fwos'] ?? []) as $fwoInput) {
                        if (empty($fwoInput['include'])) continue;

                        // Ambil ulang FWO sumber dari DB — pastikan benar-benar
                        // milik WO sumber ini, bukan WO/SO lain.
                        $sourceFwo = DB::table('fieldworks')
                            ->where('id_fwo', $fwoInput['source_id_fwo'])
                            ->where('id_wo', $sourceWo->id_wo)
                            ->whereNull('deleted_at')
                            ->first();
                        if (!$sourceFwo) continue;

                        $judulPekerjaanFwo = $fwoInput['judul_pekerjaan'] ?? $sourceFwo->judul_pekerjaan;
                        if (!$judulPekerjaanFwo) {
                            throw new \RuntimeException("Judul Pekerjaan FWO \"{$sourceFwo->no_fwo}\" pada WO \"{$sourceWo->no_wo}\" wajib diisi.");
                        }

                        // Site FWO harus tetap milik Perusahaan yang sama
                        // dengan WO-nya — guard sama seperti Site WO di atas.
                        $fwoSiteId = $fwoInput['id_site_pelanggan_pekerjaan'] ?? $sourceFwo->id_site_pelanggan_pekerjaan;
                        if ($fwoSiteId) {
                            $fwoSiteOk = DB::table('business_relation_sites')
                                ->where('id_site', $fwoSiteId)
                                ->where('id_br', $sourceWo->id_pelanggan_pekerjaan)
                                ->exists();
                            if (!$fwoSiteOk) {
                                throw new \RuntimeException("Site untuk FWO \"{$sourceFwo->no_fwo}\" bukan milik Perusahaan yang sama dengan WO \"{$sourceWo->no_wo}\".");
                            }
                        }

                        $fwoPicId = $fwoInput['id_pic_pelanggan_pekerjaan'] ?? $sourceFwo->id_pic_pelanggan_pekerjaan;
                        if (!$fwoPicId) {
                            throw new \RuntimeException("PIC Pelanggan untuk FWO \"{$sourceFwo->no_fwo}\" wajib dipilih.");
                        }

                        $fwoMulaiVal   = $fwoInput['tanggal_mulai']   ?? $sourceFwo->tanggal_mulai;
                        $fwoSelesaiVal = $fwoInput['tanggal_selesai'] ?? $sourceFwo->tanggal_selesai;
                        $waktuKedatanganVal = $fwoInput['waktu_kedatangan'] ?? $sourceFwo->waktu_kedatangan;

                        // Rentang tanggal FWO wajib berada di dalam rentang
                        // tanggal WO-nya sendiri — aturan & pesan sama persis
                        // dengan FieldworkController::store() supaya konsisten
                        // dengan halaman "Tambah FWO" biasa.
                        $woMulaiCmp   = $newWoMulai   ? substr($newWoMulai, 0, 10)   : null;
                        $woSelesaiCmp = $newWoSelesai ? substr($newWoSelesai, 0, 10) : null;
                        $fwoMulaiCmp   = $fwoMulaiVal   ? substr($fwoMulaiVal, 0, 10)   : null;
                        $fwoSelesaiCmp = $fwoSelesaiVal ? substr($fwoSelesaiVal, 0, 10) : null;

                        if ($fwoMulaiCmp && $woMulaiCmp && $fwoMulaiCmp < $woMulaiCmp) {
                            throw new \RuntimeException("Tanggal mulai FWO \"{$sourceFwo->no_fwo}\" tidak boleh sebelum tanggal mulai WO \"{$sourceWo->no_wo}\" ({$woMulaiCmp}).");
                        }
                        if ($fwoMulaiCmp && $woSelesaiCmp && $fwoMulaiCmp > $woSelesaiCmp) {
                            throw new \RuntimeException("Tanggal mulai FWO \"{$sourceFwo->no_fwo}\" tidak boleh setelah tanggal selesai WO \"{$sourceWo->no_wo}\" ({$woSelesaiCmp}).");
                        }
                        if ($fwoSelesaiCmp && $woMulaiCmp && $fwoSelesaiCmp < $woMulaiCmp) {
                            throw new \RuntimeException("Tanggal selesai FWO \"{$sourceFwo->no_fwo}\" tidak boleh sebelum tanggal mulai WO \"{$sourceWo->no_wo}\" ({$woMulaiCmp}).");
                        }
                        if ($fwoSelesaiCmp && $woSelesaiCmp && $fwoSelesaiCmp > $woSelesaiCmp) {
                            throw new \RuntimeException("Tanggal selesai FWO \"{$sourceFwo->no_fwo}\" tidak boleh setelah tanggal selesai WO \"{$sourceWo->no_wo}\" ({$woSelesaiCmp}).");
                        }

                        $waktuKedatanganCmp = $waktuKedatanganVal ? substr($waktuKedatanganVal, 0, 10) : null;
                        if ($fwoMulaiCmp && $waktuKedatanganCmp && $waktuKedatanganCmp < $fwoMulaiCmp) {
                            throw new \RuntimeException("Waktu kedatangan FWO \"{$sourceFwo->no_fwo}\" tidak boleh lebih kecil dari tanggal mulai FWO ({$fwoMulaiCmp}).");
                        }
                        if ($fwoSelesaiCmp && $waktuKedatanganCmp && $waktuKedatanganCmp > $fwoSelesaiCmp) {
                            throw new \RuntimeException("Waktu kedatangan FWO \"{$sourceFwo->no_fwo}\" tidak boleh lebih besar dari tanggal selesai FWO ({$fwoSelesaiCmp}).");
                        }

                        $newFwoId = DB::table('fieldworks')->insertGetId([
                            'id_wo'                       => $newWoId,
                            'no_fwo'                      => $this->generateNoFwo(),
                            'judul_pekerjaan'             => $judulPekerjaanFwo,
                            'id_site_pelanggan_pekerjaan' => $fwoSiteId,
                            'id_pic_pelanggan_pekerjaan'  => $fwoPicId,
                            'tanggal_mulai'               => $fwoMulaiVal,
                            'tanggal_selesai'             => $fwoSelesaiVal,
                            'waktu_kedatangan'            => $waktuKedatanganVal,
                            'status'                      => 'planned',
                            'keterangan'                  => $fwoInput['keterangan'] ?? $sourceFwo->keterangan,
                            'attachments'                 => null,
                            'created_at'                  => now(),
                            'updated_at'                  => now(),
                        ]);

                        foreach (($fwoInput['fieldwork_boq'] ?? []) as $fbInput) {
                            if (empty($fbInput['include'])) continue;

                            if (!empty($fbInput['source_id_fwo_boq'])) {
                                $sourceFb = DB::table('fieldwork_boq')
                                    ->where('id_fwo_boq', $fbInput['source_id_fwo_boq'])
                                    ->where('id_fwo', $sourceFwo->id_fwo)
                                    ->whereNull('deleted_at')
                                    ->first();
                                if (!$sourceFb) continue;

                                $fbTestingPointId = $sourceFb->id_testing_point;
                                $fbQty = $fbInput['qty'] ?? $sourceFb->qty;
                                $fbKeterangan = $fbInput['keterangan'] ?? $sourceFb->keterangan;
                            } else {
                                // Alokasi Fieldwork BOQ baru — Testing Point
                                // WAJIB salah satu yang sudah ada di BOQ WO
                                // ini sendiri (dicek via $woBoqIdByTestingPoint
                                // di bawah, bukan bebas seperti "Tambah BOQ"
                                // level WO), sesuai aturan bisnis FWO cuma
                                // bisa kerjakan BOQ yang memang ada di WO-nya.
                                $fbTestingPointId = $fbInput['id_testing_point'] ?? null;
                                if (!$fbTestingPointId) {
                                    throw new \RuntimeException("Ada alokasi Fieldwork BOQ baru pada FWO \"{$sourceFwo->no_fwo}\" yang belum memilih Testing Point.");
                                }
                                if (empty($fbInput['qty']) || $fbInput['qty'] < 1) {
                                    throw new \RuntimeException("Isi Qty (minimal 1) untuk alokasi Fieldwork BOQ baru pada FWO \"{$sourceFwo->no_fwo}\".");
                                }
                                $fbQty = $fbInput['qty'];
                                $fbKeterangan = $fbInput['keterangan'] ?? null;
                            }

                            // Remap ke BOQ WO hasil clone via id_testing_point
                            // (fieldwork_boq.id_testing_point didenormalisasi,
                            // sengaja tidak dipakai id_boq lama) — kalau BOQ WO
                            // terkait tidak disertakan saat clone, lewati baris
                            // ini (bukan gagal total), sesuai kesepakatan user.
                            $fbNewBoqId = $woBoqIdByTestingPoint[$fbTestingPointId] ?? null;
                            if (!$fbNewBoqId) {
                                $skippedFwoBoqTotal++;
                                continue;
                            }

                            $newFwoBoqId = DB::table('fieldwork_boq')->insertGetId([
                                'id_fwo'           => $newFwoId,
                                'id_boq'           => $fbNewBoqId,
                                'id_testing_point' => $fbTestingPointId,
                                'qty'              => $fbQty,
                                'keterangan'       => $fbKeterangan,
                                'created_at'       => now(),
                                'updated_at'       => now(),
                            ]);

                            // fieldwork_boq_items disalin dari isi BOQ WO
                            // TUJUAN (bukan dari fieldwork_boq_items sumber) —
                            // supaya tetap konsisten kalau user sempat
                            // uncheck sebagian Testing Item saat "Tambah BOQ"
                            // baru di level WO.
                            $fbNewItemIds = DB::table('boq_items')
                                ->where('id_boq', $fbNewBoqId)
                                ->whereNull('deleted_at')
                                ->pluck('id_testing_item');
                            foreach ($fbNewItemIds as $fbItemId) {
                                DB::table('fieldwork_boq_items')->insert([
                                    'id_fwo_boq'      => $newFwoBoqId,
                                    'id_testing_item' => $fbItemId,
                                    'created_at'      => now(),
                                    'updated_at'      => now(),
                                ]);
                            }
                        }

                        foreach (['fwo_boq_other' => 'lainnya', 'fwo_boq_sampling' => 'sampling'] as $fwoBtKey => $fwoBtJenis) {
                            foreach (($fwoInput[$fwoBtKey] ?? []) as $fwoBtInput) {
                                if (empty($fwoBtInput['include'])) continue;

                                if (!empty($fwoBtInput['source_id_fwo_boq_tambahan'])) {
                                    $sourceFwoBt = DB::table('fwo_boq_tambahan')
                                        ->where('id_fwo_boq_tambahan', $fwoBtInput['source_id_fwo_boq_tambahan'])
                                        ->where('id_fwo', $sourceFwo->id_fwo)
                                        ->where('jenis', $fwoBtJenis)
                                        ->whereNull('deleted_at')
                                        ->first();
                                    if (!$sourceFwoBt) continue;

                                    DB::table('fwo_boq_tambahan')->insert([
                                        'id_fwo'     => $newFwoId,
                                        'jenis'      => $fwoBtJenis,
                                        'nama_item'  => $fwoBtInput['nama_item'] ?? $sourceFwoBt->nama_item,
                                        'qty'        => $fwoBtInput['qty'] ?? $sourceFwoBt->qty,
                                        'id_satuan'  => $fwoBtInput['id_satuan'] ?? $sourceFwoBt->id_satuan,
                                        'harga'      => $fwoBtInput['harga'] ?? $sourceFwoBt->harga,
                                        'keterangan' => $fwoBtInput['keterangan'] ?? $sourceFwoBt->keterangan,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                } else {
                                    if (empty($fwoBtInput['qty']) || $fwoBtInput['qty'] < 1) {
                                        $fwoBtLabel = $fwoBtJenis === 'sampling' ? 'FWO BOQ Sampling' : 'FWO BOQ Other';
                                        throw new \RuntimeException("Isi Qty (minimal 1) untuk item {$fwoBtLabel} \"{$fwoBtInput['nama_item']}\" pada FWO \"{$sourceFwo->no_fwo}\".");
                                    }

                                    DB::table('fwo_boq_tambahan')->insert([
                                        'id_fwo'     => $newFwoId,
                                        'jenis'      => $fwoBtJenis,
                                        'nama_item'  => $fwoBtInput['nama_item'],
                                        'qty'        => $fwoBtInput['qty'] ?? 0,
                                        'id_satuan'  => $fwoBtInput['id_satuan'] ?? null,
                                        'harga'      => $fwoBtInput['harga'] ?? 0,
                                        'keterangan' => $fwoBtInput['keterangan'] ?? null,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                }
                            }
                        }

                        foreach (($fwoInput['personel'] ?? []) as $personelInput) {
                            if (empty($personelInput['include'])) continue;

                            $personnelId = $personelInput['id_personnel'] ?? null;
                            if (!$personnelId) {
                                throw new \RuntimeException("Ada baris Personel pada FWO \"{$sourceFwo->no_fwo}\" yang belum memilih Personnel.");
                            }

                            $personnelExists = DB::table('personnel')
                                ->where('id_personnel', $personnelId)
                                ->whereNull('deleted_at')
                                ->exists();
                            if (!$personnelExists) {
                                throw new \RuntimeException("Personnel yang dipilih pada FWO \"{$sourceFwo->no_fwo}\" tidak valid.");
                            }

                            DB::table('fieldwork_personels')->insert([
                                'id_fwo'        => $newFwoId,
                                'id_personnel'  => $personnelId,
                                'role'          => $personelInput['role'] ?? null,
                                'created_at'    => now(),
                                'updated_at'    => now(),
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

                return ['id_so' => $newSoId, 'skipped_fwo_boq' => $skippedFwoBoqTotal];
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => $e instanceof \RuntimeException ? $e->getMessage() : 'Gagal membuat salinan Sales Order. Tidak ada data yang tersimpan.',
            ], 422);
        }

        $message = 'Sales Order berhasil disalin';
        if ($newSoId['skipped_fwo_boq'] > 0) {
            $message .= " ({$newSoId['skipped_fwo_boq']} alokasi Fieldwork BOQ dilewati karena BOQ WO terkait tidak disertakan)";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'id_so'   => $newSoId['id_so'],
        ]);
    }

    private function sourceNoSoLabel($id): string
    {
        return DB::table('sales_orders')->where('id_so', $id)->value('no_so') ?? "#{$id}";
    }

    /**
     * Nomor FWO baru untuk hasil clone — pola sama seperti
     * FieldworkController::generateNoFwo() (private di controller itu,
     * jadi disalin ke sini, bukan dipanggil lintas controller — sama
     * seperti generateNoWo()).
     */
    private function generateNoFwo(): string
    {
        $year = now()->format('y');

        $latest = DB::table('fieldworks')
            ->where('no_fwo', 'like', "FWO.{$year}.%")
            ->orderBy('no_fwo', 'desc')
            ->value('no_fwo');

        if (!$latest) {
            return "FWO.{$year}.A.0001";
        }

        $parts  = explode('.', $latest);
        $letter = $parts[2] ?? 'A';
        $number = intval($parts[3] ?? 0);

        if ($number < 9999) {
            return sprintf('FWO.%s.%s.%04d', $year, $letter, $number + 1);
        }

        return sprintf('FWO.%s.%s.0001', $year, chr(ord($letter) + 1));
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
