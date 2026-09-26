<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;

/**
 * SQ Work Order — modul mandiri (menu, halaman, DataTable sendiri) untuk WO
 * estimasi milik Sales Quotation, dibangun mengikuti pola Work Order asli
 * di bawah Sales Order persis (menu sendiri, halaman sendiri dg URL sendiri,
 * bukan modal) — keputusan Fase 0 "tanpa halaman sendiri" DIBATALKAN
 * 2026-09-26 atas instruksi user, supaya UX konsisten dgn SO/WO/FWO.
 * Lihat Obsidian Modules/Sales Quotation.md.
 */
class SqWorkOrderController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'sq_work_orders'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_sq_wo']; }

    public function index()
    {
        return view('sq-work-order.index', ['title' => 'SQ Work Orders']);
    }

    public function create(Request $request)
    {
        if (!$request->filled('id_sq')) {
            return redirect()->route('sq-work-orders.index');
        }

        return view('sq-work-order.create', ['title' => 'Create SQ Work Order']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_sq' => 'required|integer|exists:sales_quotations,id_sq',
            'judul_pekerjaan' => 'nullable|string|max:255',
            'id_pelanggan_pekerjaan' => 'nullable|integer',
            'id_site_pelanggan_pekerjaan' => 'nullable|integer',
            'id_pic_pelanggan_pekerjaan' => 'nullable|integer',
            'hari_mulai' => 'required|integer|min:1',
            'durasi_hari' => 'nullable|integer|min:1',
            'keterangan' => 'nullable|string',
        ]);

        $urutan = 1 + (int) DB::table('sq_work_orders')->where('id_sq', $validated['id_sq'])->max('urutan');

        $id = DB::table('sq_work_orders')->insertGetId(array_merge($validated, [
            'no_sq_wo' => $this->generateNoSqWo(),
            'urutan' => $urutan,
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $after = DB::table('sq_work_orders')->where('id_sq_wo', $id)->get()->toJson();
        saveAudit('sq_work_orders', $id, 'Create', '', $after);

        return response()->json(['success' => true, 'id' => $id]);
    }

    private function generateNoSqWo(): string
    {
        $prefix = 'SQWO-' . now()->format('y') . '-';

        // ORDER BY id_sq_wo (bukan created_at) — id auto-increment selalu unik &
        // monoton, menghindari ambiguitas kalau ada 2 WO dibuat di detik yang
        // sama (pola sama seperti bug nomor WO duplikat yang pernah ditemukan
        // di generateNoWo(), lihat Sales Order.md Fase 2).
        $latest = DB::table('sq_work_orders')
            ->where('no_sq_wo', 'like', $prefix . '%')
            ->orderByDesc('id_sq_wo')
            ->first();

        $newNumber = $latest ? ((int) substr($latest->no_sq_wo, -4)) + 1 : 1;

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function show($id)
    {
        $wo = DB::table('sq_work_orders as w')
            ->leftJoin('sales_quotations as sq', 'sq.id_sq', '=', 'w.id_sq')
            ->leftJoin('business_relation_sites as brs', 'brs.id_site', '=', 'w.id_site_pelanggan_pekerjaan')
            // SQ WO hanya menyimpan Site (form tidak punya field Perusahaan) —
            // Perusahaan diturunkan dari Site untuk badge action bar.
            ->leftJoin('business_relations as br', 'br.id_br', '=', DB::raw('COALESCE(w.id_pelanggan_pekerjaan, brs.id_br)'))
            ->leftJoin('entitas as ent', 'ent.id_entitas', '=', 'br.id_entitas')
            ->leftJoin('business_relation_contacts as brc', 'brc.id_contact', '=', 'w.id_pic_pelanggan_pekerjaan')
            ->where('w.id_sq_wo', $id)
            ->select([
                'w.*',
                'sq.no_sq', 'sq.revisi',
                'br.id_br as id_br_pekerjaan',
                'br.nama as nama_pelanggan_pekerjaan',
                'ent.nama as entitas_pelanggan_pekerjaan',
                'brs.nama_lokasi as nama_site_pelanggan_pekerjaan',
                'brc.nama_pic as nama_pic',
            ])
            ->first();

        if (!$wo) {
            return response()->json(['message' => 'SQ Work Order tidak ditemukan'], 404);
        }

        $wo->nama_pelanggan_display = brDisplayName($wo->entitas_pelanggan_pekerjaan, $wo->nama_pelanggan_pekerjaan);

        return response()->json($wo);
    }

    public function update(Request $request, $id)
    {
        $row = DB::table('sq_work_orders')->where('id_sq_wo', $id)->first();
        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);

        $validated = $request->validate([
            'judul_pekerjaan' => 'nullable|string|max:255',
            'id_pelanggan_pekerjaan' => 'nullable|integer',
            'id_site_pelanggan_pekerjaan' => 'nullable|integer',
            'id_pic_pelanggan_pekerjaan' => 'nullable|integer',
            'hari_mulai' => 'required|integer|min:1',
            'durasi_hari' => 'nullable|integer|min:1',
            'keterangan' => 'nullable|string',
        ]);

        $before = DB::table('sq_work_orders')->where('id_sq_wo', $id)->get()->toJson();

        DB::table('sq_work_orders')->where('id_sq_wo', $id)->update(array_merge($validated, [
            'updated_at' => now(),
        ]));

        $after = DB::table('sq_work_orders')->where('id_sq_wo', $id)->get()->toJson();
        saveAudit('sq_work_orders', $id, 'update', $before, $after);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $row = DB::table('sq_work_orders')->where('id_sq_wo', $id)->first();
        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);

        $before = DB::table('sq_work_orders')->where('id_sq_wo', $id)->get()->toJson();

        // Hard delete — FK cascade membereskan sq_boq/sq_boq_items/sq_boq_tambahan/
        // sq_wo_budgets/sq_fieldworks/dst. milik WO ini otomatis.
        DB::table('sq_work_orders')->where('id_sq_wo', $id)->delete();

        saveAudit('sq_work_orders', $id, 'delete', $before, '');

        return response()->json(['success' => true]);
    }

    public function data(Request $request)
    {
        $filters = collect($request->input('filters', []))->filter(fn($f) => !empty($f['by']) && !empty($f['q']));
        $isSearching = $filters->isNotEmpty();

        $query = DB::table('sq_work_orders as w')
            ->leftJoin('sales_quotations as sq', 'sq.id_sq', '=', 'w.id_sq')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'w.id_pelanggan_pekerjaan')
            ->when($isSearching, function ($q) use ($filters) {
                foreach ($filters as $f) {
                    $like = '%' . $f['q'] . '%';
                    match ($f['by']) {
                        'no_sq_wo' => $q->where('w.no_sq_wo', 'like', $like),
                        'no_sq' => $q->where('sq.no_sq', 'like', $like),
                        'judul' => $q->where('w.judul_pekerjaan', 'like', $like),
                        'pelanggan' => $q->where('br.nama', 'like', $like),
                        default => null,
                    };
                }
            })
            ->select([
                'w.id_sq_wo',
                'w.no_sq_wo',
                'sq.id_sq',
                'sq.no_sq',
                'w.judul_pekerjaan',
                'br.nama as Pelanggan',
                'w.hari_mulai',
                'w.durasi_hari',
                'w.created_at',
            ]);

        return DataTables::of($query)->addIndexColumn()->make(true);
    }

    /**
     * Daftar ringkas SQ WO milik 1 SQ (dipakai tab "Work Order" di halaman
     * Sales Quotation) — pola sama dengan WorkOrderController::bySo().
     */
    public function bySq($id_sq)
    {
        $wos = DB::table('sq_work_orders as w')
            ->where('w.id_sq', $id_sq)
            ->orderBy('w.urutan')
            ->orderBy('w.id_sq_wo')
            ->select(['w.id_sq_wo', 'w.no_sq_wo', 'w.judul_pekerjaan', 'w.hari_mulai', 'w.durasi_hari'])
            ->get();

        if ($wos->isEmpty()) {
            return response()->json([]);
        }

        $woIds = $wos->pluck('id_sq_wo');

        $boqTotals = DB::table('sq_boq')
            ->whereIn('id_sq_wo', $woIds)
            ->selectRaw('id_sq_wo, COUNT(*) as boq_count, SUM(GREATEST(0, qty * harga - discount)) as total_boq')
            ->groupBy('id_sq_wo')
            ->get()
            ->keyBy('id_sq_wo');

        return response()->json($wos->map(function ($wo) use ($boqTotals) {
            $t = $boqTotals->get($wo->id_sq_wo);
            $wo->boq_count = (int) ($t->boq_count ?? 0);
            $wo->total_boq = (int) ($t->total_boq ?? 0);
            return $wo;
        })->values());
    }
}
