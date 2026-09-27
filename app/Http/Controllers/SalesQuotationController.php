<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Support\SqLock;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;
use App\Traits\HasOrderPartyFields;
use Spatie\LaravelPdf\Facades\Pdf;

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

    /**
     * Nomor SQ Work Order baru untuk hasil revisi — pola sama persis
     * SqWorkOrderController::generateNoSqWo() (private di sana, disalin ke
     * sini seperti pola SqConvertController::generateNoWo()). ORDER BY
     * id_sq_wo (bukan created_at) supaya aman kalau banyak WO dibuat dalam
     * 1 transaksi yang timestamp-nya bisa sama persis.
     */
    private function generateNoSqWo(): string
    {
        $prefix = 'SQWO-' . now()->format('y') . '-';
        $latest = DB::table('sq_work_orders')->where('no_sq_wo', 'like', $prefix . '%')->orderByDesc('id_sq_wo')->first();
        $newNumber = $latest ? ((int) substr($latest->no_sq_wo, -4)) + 1 : 1;
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
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
                DB::raw('(SELECT so_t.id_so FROM sales_orders so_t WHERE so_t.id_sq = sq.id_sq AND so_t.deleted_at IS NULL ORDER BY so_t.id_so LIMIT 1) as id_so_terbit'),
                DB::raw('(SELECT so_t.no_so FROM sales_orders so_t WHERE so_t.id_sq = sq.id_sq AND so_t.deleted_at IS NULL ORDER BY so_t.id_so LIMIT 1) as no_so_terbit'),
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

        $rootId = $sq->id_sq_induk ?: $sq->id_sq;
        $sq->riwayat_revisi = DB::table('sales_quotations')
            ->where(fn($q) => $q->where('id_sq', $rootId)->orWhere('id_sq_induk', $rootId))
            ->orderBy('revisi')
            ->get(['id_sq', 'no_sq', 'revisi', 'status', 'is_latest', 'created_at']);

        return response()->json($sq);
    }

    public function update(Request $request, $id)
    {
        if ($lock = SqLock::bySq($id)) return $lock;
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

    /**
     * Draft → Final. SQ Final terkunci (tidak bisa diedit) dan siap
     * diterbitkan jadi SO. Validasi minimal: punya judul & minimal 1 WO.
     */
    public function finalize($id)
    {
        $sq = DB::table('sales_quotations')->where('id_sq', $id)->whereNull('deleted_at')->first();
        if (!$sq) return response()->json(['message' => 'Sales Quotation tidak ditemukan'], 404);
        if ($sq->status !== 'draft') {
            return response()->json(['message' => 'Hanya SQ berstatus Draft yang bisa difinalkan.'], 422);
        }
        if (!DB::table('sq_work_orders')->where('id_sq', $id)->exists()) {
            return response()->json(['message' => 'Tambahkan minimal 1 Work Order sebelum memfinalkan SQ.'], 422);
        }

        $before = DB::table('sales_quotations')->where('id_sq', $id)->get()->toJson();
        DB::table('sales_quotations')->where('id_sq', $id)->update(['status' => 'final', 'terkirim_at' => null, 'updated_at' => now()]);
        saveAudit('sales_quotations', $id, 'update', $before, DB::table('sales_quotations')->where('id_sq', $id)->get()->toJson());

        return response()->json(['success' => true, 'message' => 'Sales Quotation difinalkan. SQ sekarang terkunci dan siap diterbitkan jadi SO.']);
    }

    /** Draft/Final → Cancel (alasan opsional dicatat di keterangan_status). */
    public function cancel(Request $request, $id)
    {
        $sq = DB::table('sales_quotations')->where('id_sq', $id)->whereNull('deleted_at')->first();
        if (!$sq) return response()->json(['message' => 'Sales Quotation tidak ditemukan'], 404);
        if (!in_array($sq->status, ['draft', 'final'], true)) {
            return response()->json(['message' => 'Hanya SQ berstatus Draft atau Final yang bisa dibatalkan.'], 422);
        }
        $request->validate(['keterangan_status' => 'nullable|string|max:1000']);

        $before = DB::table('sales_quotations')->where('id_sq', $id)->get()->toJson();
        DB::table('sales_quotations')->where('id_sq', $id)->update([
            'status' => 'cancel',
            'keterangan_status' => $request->input('keterangan_status') ?: $sq->keterangan_status,
            'diputuskan_at' => now(),
            'updated_at' => now(),
        ]);
        saveAudit('sales_quotations', $id, 'update', $before, DB::table('sales_quotations')->where('id_sq', $id)->get()->toJson());

        return response()->json(['success' => true, 'message' => 'Sales Quotation dibatalkan.']);
    }

    /**
     * Revisi SQ Final: bikin SQ baru (row baru, no_sq sama + revisi naik 1)
     * berstatus Draft, salinan penuh dari SQ sumber (header + WO + BOQ + BOQ
     * Other/Sampling + Budget Plan). SQ sumber ditandai is_latest=false &
     * status=cancel — dianggap sudah digantikan, tidak dianggap "batal" karena
     * alasan bisnis. Completed tidak boleh direvisi (sudah jadi SO).
     */
    public function revise($id)
    {
        $sq = DB::table('sales_quotations')->where('id_sq', $id)->whereNull('deleted_at')->first();
        if (!$sq) return response()->json(['message' => 'Sales Quotation tidak ditemukan'], 404);
        if ($sq->status !== 'final') {
            return response()->json(['message' => 'Hanya SQ berstatus Final yang bisa direvisi.'], 422);
        }

        $newId = DB::transaction(function () use ($sq) {
            $data = (array) $sq;
            unset($data['id_sq']);
            $data['revisi'] = $sq->revisi + 1;
            $data['id_sq_induk'] = $sq->id_sq_induk ?: $sq->id_sq; // selalu rujuk revisi 0 (akar)
            $data['is_latest'] = true;
            $data['status'] = 'draft';
            $data['keterangan_status'] = null;
            $data['terkirim_at'] = null;
            $data['diputuskan_at'] = null;
            $data['attachment'] = json_encode([]);
            $data['deleted_at'] = null;
            $data['created_at'] = now();
            $data['updated_at'] = now();

            $newId = DB::table('sales_quotations')->insertGetId($data);

            $wos = DB::table('sq_work_orders')->where('id_sq', $sq->id_sq)->orderBy('urutan')->orderBy('id_sq_wo')->get();
            foreach ($wos as $wo) {
                $wd = (array) $wo;
                unset($wd['id_sq_wo']);
                $wd['id_sq'] = $newId;
                // no_sq_wo TIDAK ikut disalin — nomor identitas WO harus unik
                // per baris, bukan diwariskan dari WO sumber (bug ditemukan
                // 2026-09-27: revisi bikin no_sq_wo dobel antara SQ lama & baru).
                $wd['no_sq_wo'] = $this->generateNoSqWo();
                $wd['created_at'] = now();
                $wd['updated_at'] = now();
                $newWoId = DB::table('sq_work_orders')->insertGetId($wd);

                foreach (DB::table('sq_boq')->where('id_sq_wo', $wo->id_sq_wo)->get() as $b) {
                    $bd = (array) $b;
                    unset($bd['id_sq_boq']);
                    $bd['id_sq_wo'] = $newWoId;
                    $bd['created_at'] = now();
                    $bd['updated_at'] = now();
                    $newBoqId = DB::table('sq_boq')->insertGetId($bd);

                    $items = DB::table('sq_boq_items')->where('id_sq_boq', $b->id_sq_boq)->pluck('id_testing_item');
                    if ($items->isNotEmpty()) {
                        DB::table('sq_boq_items')->insert($items->map(fn($i) => [
                            'id_sq_boq' => $newBoqId, 'id_testing_item' => $i,
                            'created_at' => now(), 'updated_at' => now(),
                        ])->all());
                    }
                }

                foreach (DB::table('sq_boq_tambahan')->where('id_sq_wo', $wo->id_sq_wo)->get() as $t) {
                    $td = (array) $t;
                    unset($td['id_sq_boq_tambahan']);
                    $td['id_sq_wo'] = $newWoId;
                    $td['created_at'] = now();
                    $td['updated_at'] = now();
                    DB::table('sq_boq_tambahan')->insert($td);
                }

                foreach (DB::table('sq_wo_budgets')->where('id_sq_wo', $wo->id_sq_wo)->get() as $bp) {
                    $bpd = (array) $bp;
                    unset($bpd['id_sq_budget']);
                    $bpd['id_sq_wo'] = $newWoId;
                    $bpd['created_at'] = now();
                    $bpd['updated_at'] = now();
                    $newBudgetId = DB::table('sq_wo_budgets')->insertGetId($bpd);

                    foreach (DB::table('sq_wo_budget_items')->where('id_sq_budget', $bp->id_sq_budget)->get() as $bi) {
                        $bid = (array) $bi;
                        unset($bid['id_sq_budget_item']);
                        $bid['id_sq_budget'] = $newBudgetId;
                        $bid['created_at'] = now();
                        $bid['updated_at'] = now();
                        DB::table('sq_wo_budget_items')->insert($bid);
                    }
                }
            }

            $before = DB::table('sales_quotations')->where('id_sq', $sq->id_sq)->get()->toJson();
            DB::table('sales_quotations')->where('id_sq', $sq->id_sq)->update([
                'is_latest' => false,
                'status' => 'cancel',
                'keterangan_status' => "Digantikan oleh revisi Rev.{$data['revisi']}",
                'diputuskan_at' => now(),
                'updated_at' => now(),
            ]);
            saveAudit('sales_quotations', $sq->id_sq, 'update', $before, DB::table('sales_quotations')->where('id_sq', $sq->id_sq)->get()->toJson());

            $after = DB::table('sales_quotations')->where('id_sq', $newId)->get()->toJson();
            saveAudit('sales_quotations', $newId, 'Create', '', $after);

            return $newId;
        });

        $newNo = DB::table('sales_quotations')->where('id_sq', $newId)->value('no_sq');
        $newRev = DB::table('sales_quotations')->where('id_sq', $newId)->value('revisi');

        return response()->json([
            'success' => true,
            'message' => "Revisi dibuat: {$newNo} Rev.{$newRev} (Draft).",
            'id_sq' => $newId,
        ]);
    }

    public function destroy($id)
    {
        if (DB::table('sales_quotations')->where('id_sq', $id)->value('status') === 'completed') {
            return response()->json(['message' => 'SQ berstatus Completed (sudah menjadi SO) tidak bisa dihapus.'], 422);
        }
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

    /**
     * Executive Summary (internal) — analisa pendapatan vs biaya operasional
     * (Budget Plan) dari data SQ yang sudah Completed (SO sudah terbit).
     * Sumber data = SQ (terkunci, jadi angkanya stabil), bukan SO yang masih
     * bisa berubah. Pendapatan dihitung bersih setelah discount.
     */
    public function executiveSummaryPdf($id)
    {
        $sq = DB::table('sales_quotations as sq')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'sq.id_pelanggan')
            ->leftJoin('entitas as ent', 'ent.id_entitas', '=', 'br.id_entitas')
            ->leftJoin('sales_orders as so', function ($j) {
                $j->on('so.id_sq', '=', 'sq.id_sq')->whereNull('so.deleted_at');
            })
            ->where('sq.id_sq', $id)
            ->whereNull('sq.deleted_at')
            ->select(['sq.*', 'br.nama as nama_pelanggan', 'ent.nama as entitas_pelanggan', 'so.no_so', 'so.tanggal_so'])
            ->first();

        if (!$sq) abort(404, 'Sales Quotation tidak ditemukan');
        if ($sq->status !== 'completed') {
            abort(422, 'Executive Summary hanya tersedia untuk SQ berstatus Completed (SO sudah terbit).');
        }
        $sq->nama_pelanggan_display = brDisplayName($sq->entitas_pelanggan, $sq->nama_pelanggan);

        $wos = DB::table('sq_work_orders')->where('id_sq', $id)->orderBy('urutan')->orderBy('id_sq_wo')->get();
        $woIds = $wos->pluck('id_sq_wo');

        $boq = DB::table('sq_boq')->whereIn('id_sq_wo', $woIds)->get()->groupBy('id_sq_wo');
        $tam = DB::table('sq_boq_tambahan')->whereIn('id_sq_wo', $woIds)->get()->groupBy('id_sq_wo');
        $budgetItems = DB::table('sq_wo_budgets as b')
            ->join('sq_wo_budget_items as i', 'i.id_sq_budget', '=', 'b.id_sq_budget')
            ->leftJoin('budget_accounts as a', 'a.id_account', '=', 'i.id_account')
            ->whereIn('b.id_sq_wo', $woIds)
            ->select(['b.id_sq_wo', 'i.nominal_budget', 'i.is_cash_advance', 'i.id_account', 'a.nama as akun_nama', 'a.kode as akun_kode'])
            ->get();

        $rows = [];
        $tot = ['boq_gross' => 0, 'boq_disc' => 0, 'other' => 0, 'sampling' => 0, 'biaya' => 0, 'cash_advance' => 0];
        foreach ($wos as $wo) {
            $b = $boq->get($wo->id_sq_wo, collect());
            $gross = (int) $b->sum(fn($r) => (int) $r->qty * (int) $r->harga);
            $disc = (int) $b->sum(fn($r) => min((int) $r->discount, (int) $r->qty * (int) $r->harga));
            $t = $tam->get($wo->id_sq_wo, collect());
            $other = (int) $t->where('jenis', 'lainnya')->sum(fn($r) => (int) $r->qty * (int) $r->harga);
            $sampling = (int) $t->where('jenis', 'sampling')->sum(fn($r) => (int) $r->qty * (int) $r->harga);
            $bi = $budgetItems->where('id_sq_wo', $wo->id_sq_wo);
            $biaya = (int) $bi->sum('nominal_budget');
            $ca = (int) $bi->where('is_cash_advance', 1)->sum('nominal_budget');
            $pendapatan = ($gross - $disc) + $other + $sampling;

            $rows[] = (object) [
                'no' => $wo->no_sq_wo, 'judul' => $wo->judul_pekerjaan,
                'pendapatan' => $pendapatan, 'biaya' => $biaya,
                'margin' => $pendapatan - $biaya,
                'margin_pct' => $pendapatan > 0 ? ($pendapatan - $biaya) / $pendapatan * 100 : null,
            ];
            $tot['boq_gross'] += $gross; $tot['boq_disc'] += $disc; $tot['other'] += $other;
            $tot['sampling'] += $sampling; $tot['biaya'] += $biaya; $tot['cash_advance'] += $ca;
        }

        $subtotal = ($tot['boq_gross'] - $tot['boq_disc']) + $tot['other'] + $tot['sampling'];
        $discountSq = (int) ($sq->discount ?? 0);
        $pendapatan = max(0, $subtotal - $discountSq);
        $margin = $pendapatan - $tot['biaya'];

        $akun = $budgetItems->groupBy(fn($i) => $i->id_account ?: 0)->map(function ($g) {
            $f = $g->first();
            return (object) [
                'nama' => $f->akun_nama ? trim(($f->akun_kode ? $f->akun_kode . ' — ' : '') . $f->akun_nama) : 'Tanpa akun',
                'nominal' => (int) $g->sum('nominal_budget'),
                'cash_advance' => (int) $g->where('is_cash_advance', 1)->sum('nominal_budget'),
            ];
        })->sortByDesc('nominal')->values();

        return Pdf::view('pdf.sales-quotation.executive-summary', [
            'sq' => $sq,
            'rows' => collect($rows),
            'tot' => $tot,
            'subtotal' => $subtotal,
            'discountSq' => $discountSq,
            'pendapatan' => $pendapatan,
            'biaya' => $tot['biaya'],
            'margin' => $margin,
            'marginPct' => $pendapatan > 0 ? $margin / $pendapatan * 100 : null,
            'akun' => $akun,
        ])
            ->format('a4')
            ->name("Executive-Summary-{$sq->no_sq}.pdf");
    }

    public function printPdf($id)
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
            ->leftJoin('office as o', 'o.id_office', '=', 'sq.id_office')
            ->leftJoin('users as pic_i', 'pic_i.id', '=', 'sq.pic_input')
            ->leftJoin('users as mkt_i', 'mkt_i.id', '=', 'sq.pic_marketing_internal')
            ->leftJoin('users as mkt_e', 'mkt_e.id', '=', 'sq.pic_marketing_eksternal')
            ->where('sq.id_sq', $id)
            ->whereNull('sq.deleted_at')
            ->select([
                'sq.*',
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
                'mkt_i.name as nama_marketing_internal',
                'mkt_e.name as nama_marketing_eksternal',
            ])
            ->first();

        if (!$sq) abort(404, 'Sales Quotation tidak ditemukan');

        $wos = DB::table('sq_work_orders as w')
            ->leftJoin('business_relation_sites as brs', 'brs.id_site', '=', 'w.id_site_pelanggan_pekerjaan')
            ->leftJoin('business_relation_contacts as brc', 'brc.id_contact', '=', 'w.id_pic_pelanggan_pekerjaan')
            ->where('w.id_sq', $id)
            ->orderBy('w.urutan')
            ->orderBy('w.id_sq_wo')
            ->select([
                'w.id_sq_wo', 'w.no_sq_wo', 'w.judul_pekerjaan', 'w.hari_mulai', 'w.durasi_hari', 'w.keterangan',
                'brs.nama_lokasi as nama_site',
                'brc.nama_pic as nama_pic',
            ])
            ->get();

        $woIds = $wos->pluck('id_sq_wo');

        $boqRows = $woIds->isNotEmpty()
            ? DB::table('sq_boq as b')
                ->leftJoin('testing_points as tp', 'b.id_testing_point', '=', 'tp.id_testing_point')
                ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
                ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
                ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 'b.id_satuan')
                ->whereIn('b.id_sq_wo', $woIds)
                ->orderBy('b.id_sq_boq')
                ->select([
                    'b.id_sq_wo',
                    'b.item_produk_alternate',
                    'tp.nama as nama_testing_point',
                    'ts.nomor as standard_nomor',
                    'ts.judul as standard_judul',
                    'tms.kode as matriks_kode',
                    'tms.judul_indonesia as matriks_judul',
                    'b.qty',
                    'sat.nama as satuan',
                    'b.harga',
                    'b.discount',
                    'b.keterangan',
                ])
                ->get()
                ->groupBy('id_sq_wo')
            : collect();

        $tambahan = $woIds->isNotEmpty()
            ? DB::table('sq_boq_tambahan as bt')
                ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 'bt.id_satuan')
                ->whereIn('bt.id_sq_wo', $woIds)
                ->orderBy('bt.id_sq_boq_tambahan')
                ->select(['bt.id_sq_wo', 'bt.jenis', 'bt.nama_item', 'bt.qty', 'sat.nama as satuan', 'bt.harga', 'bt.keterangan'])
                ->get()
            : collect();

        return Pdf::view('pdf.sales-quotation.printout', [
            'sq'              => $sq,
            'wos'             => $wos,
            'boqRows'         => $boqRows,
            'boqOtherRows'    => $tambahan->where('jenis', 'lainnya')->groupBy('id_sq_wo'),
            'boqSamplingRows' => $tambahan->where('jenis', 'sampling')->groupBy('id_sq_wo'),
        ])
            ->format('a4')
            ->landscape()
            ->name("Printout-{$sq->no_sq}.pdf");
    }
}
