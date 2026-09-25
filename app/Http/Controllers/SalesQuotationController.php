<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;
use App\Traits\HasOrderPartyFields;

/**
 * Sales Quotation (SQ) — Fase 1: header CRUD saja (No SQ, Data
 * Pemesan/Pengiriman/Pembayaran, PIC Internal, status). Rincian
 * WO/BOQ/FWO/Budget menyusul di Fase 2-3. Lihat Obsidian
 * Modules/Sales Quotation.md untuk rencana lengkap & keputusan desain.
 */
class SalesQuotationController extends Controller
{
    use HasAuditHistory, HasOrderPartyFields;

    protected function auditTable(): string
    {
        return 'sales_quotations';
    }

    protected function auditExcludeFields(): array
    {
        return ['updated_at', 'created_at', 'id_sq'];
    }

    public function index()
    {
        return view('sales-quotation.index', [
            'title' => 'Sales Quotations',
        ]);
    }

    public function create()
    {
        return view('sales-quotation.create', [
            'title' => 'Create Sales Quotation',
        ]);
    }

    private function sqRequiredRules(): array
    {
        return array_merge(self::orderPartyRules(), [
            'tanggal_sq' => 'required|date',
            'judul_order' => 'required|string|max:255',
        ]);
    }

    private function sqRequiredMessages(): array
    {
        return array_merge(self::orderPartyMessages(), [
            'tanggal_sq.required' => 'Tanggal SQ wajib diisi.',
            'judul_order.required' => 'Judul Order wajib diisi.',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->sqRequiredRules(), $this->sqRequiredMessages());

        $noSq = $this->generateNoSq();

        $id = DB::table('sales_quotations')->insertGetId(array_merge(
            self::orderPartyColumns($request),
            [
                'no_sq' => $noSq,
                'revisi' => 0,
                'is_latest' => true,
                'tanggal_sq' => $request->tanggal_sq,
                'berlaku_sampai' => $request->berlaku_sampai,
                'judul_order' => $request->judul_order,
                'rencana_mulai' => $request->rencana_mulai,
                'cara_pembayaran' => $request->cara_pembayaran,
                'keterangan' => $request->keterangan,
                'status' => 'draft',
                'attachment' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ));

        $after = DB::table('sales_quotations')->where('id_sq', $id)->get()->toJson();
        saveAudit('sales_quotations', $id, 'Create', '', $after);

        return response()->json([
            'success' => true,
            'message' => 'Sales Quotation berhasil dibuat',
            'id_sq' => $id,
        ]);
    }

    private function generateNoSq(): string
    {
        $year = now()->format('y');
        $prefix = "SQ-{$year}-";

        $last = DB::table('sales_quotations')
            ->where('no_sq', 'like', $prefix . '%')
            ->where('revisi', 0)
            ->orderByDesc('id_sq')
            ->first();

        $newNumber = $last ? ((int) substr($last->no_sq, -4)) + 1 : 1;

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function show($id)
    {
        $sq = DB::table('sales_quotations as sq')
            ->leftJoin('business_relations as pelanggan', 'sq.id_pelanggan', '=', 'pelanggan.id_br')
            ->leftJoin('business_relation_sites as site_pelanggan', 'sq.id_site_pelanggan', '=', 'site_pelanggan.id_site')
            ->leftJoin('business_relation_contacts as brc', 'brc.id_contact', '=', 'sq.id_pic_pelanggan')
            ->leftJoin('business_relations as del', 'sq.id_pelanggan_delivery', '=', 'del.id_br')
            ->leftJoin('business_relation_sites as site_del', 'sq.id_site_pelanggan_delivery', '=', 'site_del.id_site')
            ->leftJoin('business_relation_contacts as brc_del', 'brc_del.id_contact', '=', 'sq.id_pic_pelanggan_delivery')
            ->leftJoin('business_relations as pay', 'sq.id_pelanggan_payment', '=', 'pay.id_br')
            ->leftJoin('business_relation_sites as site_pay', 'sq.id_site_pelanggan_payment', '=', 'site_pay.id_site')
            ->leftJoin('business_relation_contacts as brc_pay', 'brc_pay.id_contact', '=', 'sq.id_pic_pelanggan_payment')
            ->leftJoin('users as pic_i', 'pic_i.id', '=', 'sq.pic_input')
            ->leftJoin('users as marketing_internal', 'marketing_internal.id', '=', 'sq.pic_marketing_internal')
            ->leftJoin('users as marketing_eksternal', 'marketing_eksternal.id', '=', 'sq.pic_marketing_eksternal')
            ->leftJoin('office as o', 'o.id_office', '=', 'sq.id_office')
            ->leftJoin('entitas as ent_pelanggan', 'ent_pelanggan.id_entitas', '=', 'pelanggan.id_entitas')
            ->leftJoin('entitas as ent_del', 'ent_del.id_entitas', '=', 'del.id_entitas')
            ->leftJoin('entitas as ent_pay', 'ent_pay.id_entitas', '=', 'pay.id_entitas')
            ->select(
                'sq.*',
                'pelanggan.nama as nama_pelanggan',
                'ent_pelanggan.nama as entitas_pelanggan',
                'ent_del.nama as entitas_delivery',
                'ent_pay.nama as entitas_pay',
                'site_pelanggan.nama_lokasi as nama_site_pelanggan',
                'o.name as name_office',
                'brc.nama_pic as pic_pelanggan',
                'del.nama as pelanggan_delivery',
                'site_del.nama_lokasi as pelanggan_site_delivery',
                'brc_del.nama_pic as pic_pelanggan_del',
                'pay.nama as pelanggan_pay',
                'site_pay.nama_lokasi as pelanggan_site_pay',
                'brc_pay.nama_pic as pic_pelanggan_pay',
                'pic_i.name as pic_input_name',
                'marketing_internal.name as marketing_internal_name',
                'marketing_internal.id as marketing_internal_id',
                'marketing_eksternal.name as marketing_eksternal_name',
                'marketing_eksternal.id as marketing_eksternal_id',
            )
            ->where('sq.id_sq', $id)
            ->first();

        if (!$sq) {
            return response()->json(['message' => 'Sales Quotation tidak ditemukan'], 404);
        }

        $sq->nama_pelanggan_display = brDisplayName($sq->entitas_pelanggan, $sq->nama_pelanggan);
        $sq->pelanggan_delivery_display = brDisplayName($sq->entitas_delivery, $sq->pelanggan_delivery);
        $sq->pelanggan_pay_display = brDisplayName($sq->entitas_pay, $sq->pelanggan_pay);
        $sq->attachment = $sq->attachment ? json_decode($sq->attachment) : [];

        return response()->json($sq);
    }

    public function update(Request $request, $id)
    {
        $sq = DB::table('sales_quotations')->where('id_sq', $id)->first();
        if (!$sq) {
            return response()->json(['message' => 'Sales Quotation tidak ditemukan'], 404);
        }

        $validated = $request->validate(array_merge($this->sqRequiredRules(), [
            'berlaku_sampai' => 'nullable|date|after_or_equal:tanggal_sq',
            'rencana_mulai' => 'nullable|date',
            'cara_pembayaran' => 'nullable|string',
            'keterangan' => 'nullable|string',
            'keterangan_status' => 'nullable|string',
        ]), $this->sqRequiredMessages());

        $before = DB::table('sales_quotations')->where('id_sq', $id)->get()->toJson();

        DB::table('sales_quotations')->where('id_sq', $id)->update(array_merge(
            self::orderPartyColumns($request),
            [
                'tanggal_sq' => $validated['tanggal_sq'],
                'berlaku_sampai' => $validated['berlaku_sampai'] ?? null,
                'judul_order' => $validated['judul_order'],
                'rencana_mulai' => $validated['rencana_mulai'] ?? null,
                'cara_pembayaran' => $validated['cara_pembayaran'] ?? null,
                'keterangan' => $validated['keterangan'] ?? null,
                'keterangan_status' => $validated['keterangan_status'] ?? null,
                'updated_at' => now(),
            ]
        ));

        $after = DB::table('sales_quotations')->where('id_sq', $id)->get()->toJson();
        saveAudit('sales_quotations', $id, 'update', $before, $after);

        return response()->json(['success' => true, 'message' => 'Sales Quotation berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $before = DB::table('sales_quotations')->where('id_sq', $id)->get()->toJson();
        DB::table('sales_quotations')->where('id_sq', $id)->update(['deleted_at' => now()]);
        $after = DB::table('sales_quotations')->where('id_sq', $id)->get()->toJson();
        saveAudit('sales_quotations', $id, 'delete', $before, $after);

        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('sales_quotations')
            ->whereNull('deleted_at')
            ->where('is_latest', true)
            ->where('no_sq', 'like', "%{$search}%")
            ->limit(10)
            ->get();

        return response()->json($data->map(fn($item) => [
            'id' => $item->id_sq,
            'text' => $item->no_sq . ' - ' . $item->judul_order,
        ]));
    }

    public function data(Request $request)
    {
        $filters = $request->input('filters', []);
        $isSearching = !empty($filters);

        $query = DB::table('sales_quotations as s')
            ->leftJoin('business_relations as br', 's.id_pelanggan', '=', 'br.id_br')
            ->when(!$isSearching, fn($q) => $q->whereNull('s.deleted_at')->where('s.is_latest', true))
            ->when($isSearching, function ($q) use ($filters) {
                foreach ($filters as $f) {
                    $by = $f['by'] ?? '';
                    $term = trim($f['q'] ?? '');
                    if (!$by || $term === '') continue;
                    $like = '%' . $term . '%';
                    match ($by) {
                        'no_sq' => $q->where('s.no_sq', 'like', $like),
                        'judul' => $q->where('s.judul_order', 'like', $like),
                        'pelanggan' => $q->where('br.nama', 'like', $like),
                        'status' => match ($term) {
                            'all' => null,
                            'deleted' => $q->whereNotNull('s.deleted_at'),
                            default => $q->whereNull('s.deleted_at')->where('s.status', $term),
                        },
                        default => null,
                    };
                }
            })
            ->select([
                's.id_sq',
                DB::raw("CASE WHEN s.deleted_at IS NOT NULL THEN 'deleted' ELSE s.status END as status"),
                's.no_sq',
                's.revisi',
                'br.nama as Pelanggan',
                's.judul_order',
                's.tanggal_sq',
                's.berlaku_sampai',
                's.id_pelanggan',
                's.created_at',
            ]);

        return DataTables::of($query)->addIndexColumn()->make(true);
    }
}
