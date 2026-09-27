<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        $id = DB::table('sq_fieldworks')->insertGetId(array_merge($validated, [
            'no_sq_fwo' => $this->generateNoSqFwo(),
            'urutan' => $urutan,
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $after = DB::table('sq_fieldworks')->where('id_sq_fwo', $id)->get()->toJson();
        saveAudit('sq_fieldworks', $id, 'Create', '', $after);

        return response()->json(['success' => true, 'id' => $id]);
    }

    private function generateNoSqFwo(): string
    {
        $prefix = 'SQFWO-' . now()->format('y') . '-';

        // ORDER BY id_sq_fwo (bukan created_at) — pola sama seperti
        // generateNoSqWo(), menghindari ambiguitas kalau 2 FWO dibuat di
        // detik yang sama.
        $latest = DB::table('sq_fieldworks')
            ->where('no_sq_fwo', 'like', $prefix . '%')
            ->orderByDesc('id_sq_fwo')
            ->first();

        $newNumber = $latest ? ((int) substr($latest->no_sq_fwo, -4)) + 1 : 1;

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
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
        saveAudit('sq_fieldworks', $id, 'update', $before, $after);

        return response()->json(['success' => true]);
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

        saveAudit('sq_fieldworks', $id, 'delete', $before, '');

        return response()->json(['success' => true]);
    }

    public function data(Request $request)
    {
        $filters = collect($request->input('filters', []))->filter(fn($f) => !empty($f['by']) && !empty($f['q']));
        $isSearching = $filters->isNotEmpty();

        $query = DB::table('sq_fieldworks as f')
            ->leftJoin('sq_work_orders as w', 'w.id_sq_wo', '=', 'f.id_sq_wo')
            ->leftJoin('sales_quotations as sq', 'sq.id_sq', '=', 'w.id_sq')
            ->when($isSearching, function ($q) use ($filters) {
                foreach ($filters as $f) {
                    $like = '%' . $f['q'] . '%';
                    match ($f['by']) {
                        'no_sq_fwo' => $q->where('f.no_sq_fwo', 'like', $like),
                        'no_sq_wo' => $q->where('w.no_sq_wo', 'like', $like),
                        'no_sq' => $q->where('sq.no_sq', 'like', $like),
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
                'f.judul_pekerjaan',
                'f.hari_ke',
                'f.durasi_hari',
                'f.created_at',
            ]);

        return DataTables::of($query)->addIndexColumn()->make(true);
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
