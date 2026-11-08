<?php

namespace App\Http\Controllers;

use App\Support\DataScope;
use Illuminate\Http\Request;
use App\Support\DocumentNumber;
use App\Support\SqLock;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;

/**
 * SQ Fieldwork (Fase 3) — modul mandiri (menu, halaman, DataTable sendiri)
 * untuk FWO estimasi milik SQ Work Order, dibangun persis mengikuti pola
 * SqWorkOrderController/Fieldwork asli. Scope Fase 3 SENGAJA dibatasi ke
 * SQ FWO + SQ FWO BOQ + SQ FWO Budget saja (kesepakatan user 2026-09-27) —
 * TIDAK ada Personel/BOQ Other/BOQ Sampling/Attachment/Sample seperti FWO
 * asli, karena tabel `sq_*` untuk itu memang tidak dibuat. Lihat Obsidian
 * Modules/Sales Quotation.md.
 */
class SqFieldworkController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'sq_fieldworks'; }

    // History SQ FWO juga menampilkan tab BOQ dan Budget
    protected function auditParentTable(): ?string { return 'sq_fieldworks'; }

    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_sq_fwo']; }

    public function index()
    {
        return view('sq-fieldwork.index', ['title' => 'SQ Fieldworks']);
    }

    public function create(Request $request)
    {
        if (!$request->filled('id_sq_wo')) {
            return redirect()->route('sq-fieldworks.index');
        }

        return view('sq-fieldwork.create', ['title' => 'Create SQ Fieldwork']);
    }

    public function store(Request $request)
    {
        if ($lock = SqLock::byWo($request->input('id_sq_wo'))) return $lock;

        $validated = $request->validate([
            'id_sq_wo' => 'required|integer|exists:sq_work_orders,id_sq_wo',
            'judul_pekerjaan' => 'nullable|string|max:500',
            'id_site_pelanggan_pekerjaan' => 'required|integer',
            'id_pic_pelanggan_pekerjaan' => 'required|integer',
            'hari_ke' => 'required|integer|min:1',
            'durasi_hari' => 'nullable|integer|min:1',
            'keterangan' => 'nullable|string',
        ], [
            'id_site_pelanggan_pekerjaan.required' => 'Site Pekerjaan wajib diisi (dibutuhkan saat Terbitkan SO nanti).',
            'id_pic_pelanggan_pekerjaan.required' => 'PIC Pekerjaan wajib diisi (dibutuhkan saat Terbitkan SO nanti).',
        ]);

        $wo = DB::table('sq_work_orders')->where('id_sq_wo', $validated['id_sq_wo'])->first();
        if ($wo) {
            $woMulai = (int) $wo->hari_mulai;
            $woSelesai = $woMulai + max(0, (int) ($wo->durasi_hari ?: 1) - 1);
            $fwoMulai = (int) $validated['hari_ke'];
            $fwoSelesai = $fwoMulai + max(0, (int) ($validated['durasi_hari'] ?? 1) - 1);

            if ($fwoMulai < $woMulai) {
                return response()->json(['message' => "Hari Ke- FWO tidak boleh sebelum Hari Mulai WO (ke-{$woMulai})"], 422);
            }
            if ($fwoSelesai > $woSelesai) {
                return response()->json(['message' => "Jadwal FWO tidak boleh melewati jadwal WO (sampai hari ke-{$woSelesai})"], 422);
            }
        }

        $urutan = 1 + (int) DB::table('sq_fieldworks')->where('id_sq_wo', $validated['id_sq_wo'])->max('urutan');

        $id = DocumentNumber::insertGetId('sq_fieldworks', 'no_sq_fwo', 'SQFWO', array_merge($validated, [
            'urutan' => $urutan,
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $after = DB::table('sq_fieldworks')->where('id_sq_fwo', $id)->get()->toJson();
        saveAudit('sq_fieldworks', $id, 'Create', '', $after, ['sq_work_orders', $validated['id_sq_wo']]);

        $this->notifyCreated($id);

        return response()->json(['success' => true, 'id' => $id]);
    }

    /**
     * Picu notifikasi `sq_fwo.created` (tab SQ di Pengaturan Notifikasi). Peran penerima diambil dari SQ induknya
     * (PIC Input/Order, Marketing, Pengaju) + pelaku aksi. Tidak pernah menggagalkan penyimpanan.
     */
    private function notifyCreated($idSqFwo): void
    {
        $fwo = DB::table('sq_fieldworks as f')
            ->join('sq_work_orders as w', 'w.id_sq_wo', '=', 'f.id_sq_wo')
            ->leftJoin('sales_quotations as sq', 'sq.id_sq', '=', 'w.id_sq')
            ->where('f.id_sq_fwo', $idSqFwo)
            ->first(['f.no_sq_fwo', 'f.judul_pekerjaan', 'w.id_sq', 'w.no_sq_wo', 'w.judul_pekerjaan as judul_wo', 'sq.no_sq']);
        if (!$fwo) return;

        \App\Services\NotificationDispatcher::fire('sq_fwo.created', [
            'title' => 'SQ FWO baru: {{no_sq_fwo}}',
            'body'  => '{{judul_pekerjaan}}',
            'url'   => url('sq-fieldworks?open=' . $idSqFwo),
        ], [
            'no_sq_fwo'       => $fwo->no_sq_fwo ?? '',
            'judul_pekerjaan' => $fwo->judul_pekerjaan ?: ($fwo->judul_wo ?? ''),
            'no_sq_wo'        => $fwo->no_sq_wo ?? '',
            'no_sq'           => $fwo->no_sq ?? '',
            'nama_user'       => auth()->user()?->name,
        ], \App\Support\NotificationRoles::sq((int) $fwo->id_sq));
    }

    public function show($id)
    {
        $fwo = DB::table('sq_fieldworks as f')
            ->leftJoin('sq_work_orders as w', 'w.id_sq_wo', '=', 'f.id_sq_wo')
            ->leftJoin('sales_quotations as sq', 'sq.id_sq', '=', 'w.id_sq')
            // sq_fieldworks tidak punya kolom id_pelanggan_pekerjaan sendiri
            // (beda dari sq_work_orders) — Perusahaan selalu diturunkan dari Site.
            ->leftJoin('business_relation_sites as brs', 'brs.id_site', '=', 'f.id_site_pelanggan_pekerjaan')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'brs.id_br')
            ->leftJoin('entitas as ent', 'ent.id_entitas', '=', 'br.id_entitas')
            ->leftJoin('business_relation_contacts as brc', 'brc.id_contact', '=', 'f.id_pic_pelanggan_pekerjaan')
            ->where('f.id_sq_fwo', $id)
            ->select([
                'f.*',
                'w.no_sq_wo', 'w.judul_pekerjaan as wo_judul_pekerjaan',
                'w.hari_mulai as wo_hari_mulai', 'w.durasi_hari as wo_durasi_hari',
                'sq.id_sq', 'sq.no_sq', 'sq.revisi', 'sq.status as sq_status',
                'br.id_br as id_br_pekerjaan',
                'br.nama as nama_pelanggan_pekerjaan',
                'ent.nama as entitas_pelanggan_pekerjaan',
                'brs.nama_lokasi as nama_site_pelanggan_pekerjaan',
                'brc.nama_pic as nama_pic',
            ])
            ->first();

        if (!$fwo) {
            return response()->json(['message' => 'SQ Fieldwork tidak ditemukan'], 404);
        }

        $fwo->nama_pelanggan_display = brDisplayName($fwo->entitas_pelanggan_pekerjaan, $fwo->nama_pelanggan_pekerjaan);

        return response()->json($fwo);
    }

    public function update(Request $request, $id)
    {
        if ($lock = SqLock::byFwo($id)) return $lock;
        $row = DB::table('sq_fieldworks')->where('id_sq_fwo', $id)->first();
        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);

        $validated = $request->validate([
            'judul_pekerjaan' => 'nullable|string|max:500',
            'id_site_pelanggan_pekerjaan' => 'required|integer',
            'id_pic_pelanggan_pekerjaan' => 'required|integer',
            'hari_ke' => 'required|integer|min:1',
            'durasi_hari' => 'nullable|integer|min:1',
            'keterangan' => 'nullable|string',
        ], [
            'id_site_pelanggan_pekerjaan.required' => 'Site Pekerjaan wajib diisi (dibutuhkan saat Terbitkan SO nanti).',
            'id_pic_pelanggan_pekerjaan.required' => 'PIC Pekerjaan wajib diisi (dibutuhkan saat Terbitkan SO nanti).',
        ]);

        $wo = DB::table('sq_work_orders')->where('id_sq_wo', $row->id_sq_wo)->first();
        if ($wo) {
            $woMulai = (int) $wo->hari_mulai;
            $woSelesai = $woMulai + max(0, (int) ($wo->durasi_hari ?: 1) - 1);
            $fwoMulai = (int) $validated['hari_ke'];
            $fwoSelesai = $fwoMulai + max(0, (int) ($validated['durasi_hari'] ?? 1) - 1);

            if ($fwoMulai < $woMulai) {
                return response()->json(['message' => "Hari Ke- FWO tidak boleh sebelum Hari Mulai WO (ke-{$woMulai})"], 422);
            }
            if ($fwoSelesai > $woSelesai) {
                return response()->json(['message' => "Jadwal FWO tidak boleh melewati jadwal WO (sampai hari ke-{$woSelesai})"], 422);
            }
        }

        $before = DB::table('sq_fieldworks')->where('id_sq_fwo', $id)->get()->toJson();

        DB::table('sq_fieldworks')->where('id_sq_fwo', $id)->update(array_merge($validated, [
            'updated_at' => now(),
        ]));

        $after = DB::table('sq_fieldworks')->where('id_sq_fwo', $id)->get()->toJson();
        saveAudit('sq_fieldworks', $id, 'update', $before, $after, ['sq_work_orders', DB::table('sq_fieldworks')->where('id_sq_fwo', $id)->value('id_sq_wo')]);

        return response()->json(['success' => true]);
    }

    /**
     * Salin SQ Fieldwork (padanan FieldworkController::duplicate(), tanpa personel/penyelia/kendaraan/tanggal —
     * SQ FWO tidak punya). Full control: jadwal/site/PIC/keterangan, qty tiap BOQ (0/kosong = tidak ikut), dan Budget Plan
     * terpilih beserta nominal item bisa diubah sebelum simpan. Semua divalidasi SEBELUM insert; satu transaksi.
     * FWO baru ditaruh di urutan paling akhir pada SQ WO yang sama. Kolom "tanpa realisasi" TIDAK ikut (rencana baru).
     */
    public function duplicate(Request $request, $id)
    {
        if ($lock = SqLock::byFwo($id)) return $lock;

        $source = DB::table('sq_fieldworks')->where('id_sq_fwo', $id)->first();
        if (!$source) return response()->json(['message' => 'SQ Fieldwork tidak ditemukan'], 404);

        $validated = $request->validate([
            'judul_pekerjaan' => 'nullable|string|max:500',
            'id_site_pelanggan_pekerjaan' => 'required|integer',
            'id_pic_pelanggan_pekerjaan' => 'required|integer',
            'hari_ke' => 'required|integer|min:1',
            'durasi_hari' => 'nullable|integer|min:1',
            'keterangan' => 'nullable|string',
            'sections' => 'nullable|array',
            'sections.*.id_sq_boq' => 'required|integer',
            'sections.*.qty' => 'required|integer|min:1',
            'sections.*.keterangan' => 'nullable|string',
            'budgets' => 'nullable|array',
            'budgets.*.source_id_budget' => 'required|integer',
            'budgets.*.label' => 'required|string|max:255',
            'budgets.*.keterangan' => 'nullable|string',
            'budgets.*.items' => 'required|array|min:1',
            'budgets.*.items.*.source_id_budget_item' => 'required|integer',
            'budgets.*.items.*.nominal_budget' => 'required|integer|min:0',
            'budgets.*.items.*.keterangan' => 'nullable|string',
        ], [
            'id_site_pelanggan_pekerjaan.required' => 'Site Pekerjaan wajib diisi (dibutuhkan saat Terbitkan SO nanti).',
            'id_pic_pelanggan_pekerjaan.required' => 'PIC Pekerjaan wajib diisi (dibutuhkan saat Terbitkan SO nanti).',
        ]);

        // Jadwal FWO baru harus di dalam jadwal SQ WO
        $wo = DB::table('sq_work_orders')->where('id_sq_wo', $source->id_sq_wo)->first();
        if ($wo) {
            $woMulai = (int) $wo->hari_mulai;
            $woSelesai = $woMulai + max(0, (int) ($wo->durasi_hari ?: 1) - 1);
            $fwoMulai = (int) $validated['hari_ke'];
            $fwoSelesai = $fwoMulai + max(0, (int) ($validated['durasi_hari'] ?? 1) - 1);
            if ($fwoMulai < $woMulai) {
                return response()->json(['message' => "Hari Ke- FWO tidak boleh sebelum Hari Mulai WO (ke-{$woMulai})"], 422);
            }
            if ($fwoSelesai > $woSelesai) {
                return response()->json(['message' => "Jadwal FWO tidak boleh melewati jadwal WO (sampai hari ke-{$woSelesai})"], 422);
            }
        }

        // Qty tiap BOQ: BOQ harus milik SQ WO yang sama, tidak dobel, dan tidak melebihi sisa
        $sections = $validated['sections'] ?? [];
        $dobel = collect($sections)->pluck('id_sq_boq')->duplicates();
        if ($dobel->isNotEmpty()) {
            return response()->json(['message' => 'BOQ yang sama dipilih lebih dari sekali.'], 422);
        }
        foreach ($sections as $sec) {
            $boq = DB::table('sq_boq')->where('id_sq_boq', $sec['id_sq_boq'])->where('id_sq_wo', $source->id_sq_wo)->first();
            if (!$boq) {
                return response()->json(['message' => "BOQ #{$sec['id_sq_boq']} bukan milik SQ Work Order ini."], 422);
            }
            $used = (int) DB::table('sq_fieldwork_boq')->where('id_sq_boq', $boq->id_sq_boq)->sum('qty');
            $sisa = (int) ($boq->qty ?? 0) - $used;
            if ($sec['qty'] > $sisa) {
                $ptName = DB::table('testing_points')->where('id_testing_point', $boq->id_testing_point)->value('nama') ?? "BOQ #{$boq->id_sq_boq}";
                return response()->json(['message' => "Qty untuk \"{$ptName}\" melebihi sisa yang tersedia (sisa: {$sisa})"], 422);
            }
        }

        // Budget Plan: harus milik FWO sumber, dan setiap item harus milik Plan itu
        $budgetsToCopy = [];
        foreach ($validated['budgets'] ?? [] as $plan) {
            $sourcePlan = DB::table('sq_fwo_budgets')
                ->where('id_sq_budget', $plan['source_id_budget'])->where('id_sq_fwo', $source->id_sq_fwo)->first();
            if (!$sourcePlan) {
                return response()->json(['message' => "Budget Plan #{$plan['source_id_budget']} tidak ditemukan di FWO ini."], 422);
            }
            $items = [];
            foreach ($plan['items'] as $it) {
                $sourceItem = DB::table('sq_fwo_budget_items')
                    ->where('id_sq_budget_item', $it['source_id_budget_item'])->where('id_sq_budget', $sourcePlan->id_sq_budget)->first();
                if (!$sourceItem) {
                    return response()->json(['message' => "Item budget #{$it['source_id_budget_item']} tidak termasuk Plan \"{$sourcePlan->label}\"."], 422);
                }
                $items[] = [
                    'source' => $sourceItem,
                    'nominal_budget' => (int) $it['nominal_budget'],
                    'keterangan' => $it['keterangan'] ?? $sourceItem->keterangan,
                ];
            }
            $budgetsToCopy[] = ['label' => $plan['label'], 'keterangan' => $plan['keterangan'] ?? null, 'items' => $items];
        }

        $noFwo = null;
        $newId = null;
        DB::transaction(function () use ($source, $validated, $sections, $budgetsToCopy, &$noFwo, &$newId) {
            $urutan = 1 + (int) DB::table('sq_fieldworks')->where('id_sq_wo', $source->id_sq_wo)->max('urutan');

            $newId = DocumentNumber::insertGetId('sq_fieldworks', 'no_sq_fwo', 'SQFWO', [
                'id_sq_wo' => $source->id_sq_wo,
                'urutan' => $urutan,
                'judul_pekerjaan' => $validated['judul_pekerjaan'] ?? null,
                'id_site_pelanggan_pekerjaan' => $validated['id_site_pelanggan_pekerjaan'],
                'id_pic_pelanggan_pekerjaan' => $validated['id_pic_pelanggan_pekerjaan'],
                'hari_ke' => $validated['hari_ke'],
                'durasi_hari' => $validated['durasi_hari'] ?? null,
                'keterangan' => $validated['keterangan'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ], $noFwo);

            foreach ($sections as $sec) {
                $fwoBoqId = DB::table('sq_fieldwork_boq')->insertGetId([
                    'id_sq_fwo' => $newId,
                    'id_sq_boq' => $sec['id_sq_boq'],
                    'qty' => $sec['qty'],
                    'keterangan' => $sec['keterangan'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                // Item uji selalu utuh dari sq_boq_items (sama seperti SqFieldworkBoqController::update)
                $boqItems = DB::table('sq_boq_items')->where('id_sq_boq', $sec['id_sq_boq'])->get();
                if ($boqItems->isNotEmpty()) {
                    DB::table('sq_fieldwork_boq_items')->insert($boqItems->map(fn ($i) => [
                        'id_sq_fwo_boq' => $fwoBoqId,
                        'id_testing_item' => $i->id_testing_item,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])->toArray());
                }
            }

            // Budget Plan terpilih: Hari Mulai/Selesai dikosongkan; kolom "tanpa realisasi" tidak ikut (rencana baru)
            foreach ($budgetsToCopy as $plan) {
                $newBudgetId = DB::table('sq_fwo_budgets')->insertGetId([
                    'id_sq_fwo' => $newId,
                    'label' => $plan['label'],
                    'keterangan' => $plan['keterangan'],
                    'hari_mulai' => null,
                    'hari_selesai' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                foreach ($plan['items'] as $item) {
                    $sama = (int) $item['nominal_budget'] === (int) $item['source']->nominal_budget;
                    DB::table('sq_fwo_budget_items')->insert([
                        'id_sq_budget' => $newBudgetId,
                        'id_account' => $item['source']->id_account,
                        'nominal_budget' => $item['nominal_budget'],
                        // nominal tidak diubah → hari/qty/harga ikut utuh; diubah → hari 1, qty 1, harga = nominal baru
                        'hari' => $sama ? $item['source']->hari : 1,
                        'qty' => $sama ? $item['source']->qty : 1,
                        'id_satuan' => $item['source']->id_satuan,
                        'harga_satuan' => $sama ? $item['source']->harga_satuan : (int) $item['nominal_budget'],
                        'keterangan' => $item['keterangan'],
                        'is_cash_advance' => $item['source']->is_cash_advance,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        $after = DB::table('sq_fieldworks')->where('id_sq_fwo', $newId)->get()->toJson();
        saveAudit('sq_fieldworks', $newId, 'Create', '', $after, ['sq_work_orders', $source->id_sq_wo]);
        $this->notifyCreated($newId);

        return response()->json(['success' => true, 'message' => 'SQ FWO berhasil disalin', 'no_sq_fwo' => $noFwo, 'id' => $newId]);
    }

    public function destroy($id)
    {
        if ($lock = SqLock::byFwo($id)) return $lock;
        $row = DB::table('sq_fieldworks')->where('id_sq_fwo', $id)->first();
        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);

        $before = DB::table('sq_fieldworks')->where('id_sq_fwo', $id)->get()->toJson();

        // Hard delete — FK cascade membereskan sq_fieldwork_boq/items,
        // sq_fwo_budgets/items milik FWO ini otomatis.
        DB::table('sq_fieldworks')->where('id_sq_fwo', $id)->delete();

        saveAudit('sq_fieldworks', $id, 'delete', $before, '', ['sq_work_orders', $row->id_sq_wo]);

        return response()->json(['success' => true]);
    }

    public function data(Request $request)
    {
        $filters = collect($request->input('filters', []))->filter(fn($f) => !empty($f['by']) && !empty($f['q']));
        $isSearching = $filters->isNotEmpty();

        $query = DB::table('sq_fieldworks as f')
            ->leftJoin('sq_work_orders as w', 'w.id_sq_wo', '=', 'f.id_sq_wo')
            ->leftJoin('sales_quotations as sq', 'sq.id_sq', '=', 'w.id_sq')
            ->leftJoin('business_relation_sites as brs', 'brs.id_site', '=', 'f.id_site_pelanggan_pekerjaan')
            // Pelanggan = Perusahaan di Data Pemesan SQ (sales_quotations.id_pelanggan)
            ->leftJoin('business_relations as pel', 'pel.id_br', '=', 'sq.id_pelanggan')
            ->leftJoin('entitas as ent_pel', 'ent_pel.id_entitas', '=', 'pel.id_entitas')
            ->tap(fn ($q) => DataScope::sq($q, 'sq'))
            ->when($isSearching, function ($q) use ($filters) {
                foreach ($filters as $f) {
                    $like = '%' . $f['q'] . '%';
                    match ($f['by']) {
                        'no_sq_fwo' => $q->where('f.no_sq_fwo', 'like', $like),
                        'no_sq_wo' => $q->where('w.no_sq_wo', 'like', $like),
                        'no_sq' => $q->where('sq.no_sq', 'like', $like),
                        'pelanggan' => $q->where('pel.nama', 'like', $like),
                        'site' => $q->where('brs.nama_lokasi', 'like', $like),
                        'judul' => $q->where('f.judul_pekerjaan', 'like', $like),
                        default => null,
                    };
                }
            })
            ->select([
                'f.id_sq_fwo',
                'f.no_sq_fwo',
                'w.id_sq_wo',
                'w.no_sq_wo',
                'sq.id_sq',
                'sq.no_sq',
                'pel.nama as pelanggan',
                'ent_pel.nama as entitas_pelanggan',
                'brs.nama_lokasi as site_pekerjaan',
                'f.judul_pekerjaan',
                'f.hari_ke',
                'f.durasi_hari',
                'f.created_at',
            ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('pelanggan', fn ($r) => brDisplayName($r->entitas_pelanggan, $r->pelanggan))
            ->removeColumn('entitas_pelanggan')
            ->make(true);
    }

    /**
     * Daftar ringkas SQ FWO milik 1 SQ WO (dipakai tab "FWO" di halaman
     * SQ Work Order) — pola sama dengan SqWorkOrderController::bySq().
     */
    public function byWo($id_sq_wo)
    {
        $fwos = DB::table('sq_fieldworks as f')
            ->where('f.id_sq_wo', $id_sq_wo)
            ->orderBy('f.urutan')
            ->orderBy('f.id_sq_fwo')
            ->select(['f.id_sq_fwo', 'f.no_sq_fwo', 'f.judul_pekerjaan', 'f.keterangan', 'f.hari_ke', 'f.durasi_hari'])
            ->get();

        if ($fwos->isEmpty()) {
            return response()->json([]);
        }

        $fwoIds = $fwos->pluck('id_sq_fwo');

        $boqTotals = DB::table('sq_fieldwork_boq as fb')
            ->join('sq_boq as b', 'b.id_sq_boq', '=', 'fb.id_sq_boq')
            ->whereIn('fb.id_sq_fwo', $fwoIds)
            ->selectRaw('fb.id_sq_fwo, COUNT(*) as boq_count, SUM(GREATEST(0, fb.qty * b.harga)) as total_boq')
            ->groupBy('fb.id_sq_fwo')
            ->get()
            ->keyBy('id_sq_fwo');

        return response()->json($fwos->map(function ($fwo) use ($boqTotals) {
            $t = $boqTotals->get($fwo->id_sq_fwo);
            $fwo->boq_count = (int) ($t->boq_count ?? 0);
            $fwo->total_boq = (int) ($t->total_boq ?? 0);
            return $fwo;
        })->values());
    }
}
