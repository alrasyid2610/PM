<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasAuditHistory;

/**
 * Base CRUD untuk "BOQ Others" & "BOQ Sampling" level WO (tabel
 * boq_tambahan, dibedakan kolom `jenis`) — versi WO dari
 * FwoBoqTambahanController, lihat Project Reference bagian "PRD — Struktur
 * Modul Lab Testing". Item bebas (tidak terikat testing_points/testing_items),
 * independen dari `fwo_boq_tambahan` (tidak saling alokasi qty).
 *
 * Dua controller konkret (BoqOtherController, BoqSamplingController) extends
 * class ini dan cuma override jenis() — supaya masing-masing bisa punya
 * permission slug sendiri (wo-boq-other / wo-boq-sampling) sesuai pola
 * master data, bukan digabung ke permission work-orders.
 */
abstract class BoqTambahanController extends Controller
{
    use HasAuditHistory;

    abstract protected function jenis(): string;

    protected function auditTable(): string { return 'boq_tambahan'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_boq_tambahan']; }

    private function woIsCompleted($id_wo): bool
    {
        $wo = DB::table('work_orders')->where('id_wo', $id_wo)->first(['status']);
        return $wo && $wo->status === 'completed';
    }

    public function listByWo($id_wo)
    {
        $rows = DB::table('boq_tambahan as t')
            ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 't.id_satuan')
            ->where('t.id_wo', $id_wo)
            ->where('t.jenis', $this->jenis())
            ->whereNull('t.deleted_at')
            ->orderBy('t.created_at')
            ->select([
                't.id_boq_tambahan', 't.nama_item', 't.qty', 't.id_satuan',
                'sat.nama as satuan', 't.harga', 't.keterangan', 't.created_at',
            ])
            ->get();

        $wo = DB::table('work_orders')->where('id_wo', $id_wo)->first(['status']);

        return response()->json([
            'data'      => $rows,
            'total'     => $rows->sum(fn($r) => (int) $r->qty * (int) $r->harga),
            'wo_status' => $wo->status ?? null,
        ]);
    }

    public function store(Request $request)
    {
        if ($this->woIsCompleted($request->id_wo)) {
            return response()->json(['success' => false, 'message' => 'WO sudah selesai, tidak dapat menambah item.'], 403);
        }

        $validated = $request->validate([
            'id_wo'      => 'required|integer',
            'nama_item'  => 'required|string|max:255',
            'qty'        => 'required|integer|min:1',
            'id_satuan'  => 'nullable|integer|exists:satuan,id_satuan',
            'harga'      => 'required|integer|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $id = DB::table('boq_tambahan')->insertGetId([
            'id_wo'      => $validated['id_wo'],
            'jenis'      => $this->jenis(),
            'nama_item'  => $validated['nama_item'],
            'qty'        => $validated['qty'],
            'id_satuan'  => $validated['id_satuan'] ?? null,
            'harga'      => $validated['harga'],
            'keterangan' => $validated['keterangan'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $after = DB::table('boq_tambahan')->where('id_boq_tambahan', $id)->get()->toJson();
        saveAudit('boq_tambahan', $id, 'Create', '', $after);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function show($id)
    {
        $row = DB::table('boq_tambahan')
            ->where('id_boq_tambahan', $id)
            ->where('jenis', $this->jenis())
            ->whereNull('deleted_at')
            ->first();

        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);
        return response()->json($row);
    }

    public function update(Request $request, $id)
    {
        $row = DB::table('boq_tambahan')->where('id_boq_tambahan', $id)->first();
        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);

        if ($this->woIsCompleted($row->id_wo)) {
            return response()->json(['success' => false, 'message' => 'WO sudah selesai, tidak dapat mengubah item.'], 403);
        }

        $validated = $request->validate([
            'nama_item'  => 'required|string|max:255',
            'qty'        => 'required|integer|min:1',
            'id_satuan'  => 'nullable|integer|exists:satuan,id_satuan',
            'harga'      => 'required|integer|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $before = DB::table('boq_tambahan')->where('id_boq_tambahan', $id)->get()->toJson();

        DB::table('boq_tambahan')->where('id_boq_tambahan', $id)->update([
            'nama_item'  => $validated['nama_item'],
            'qty'        => $validated['qty'],
            'id_satuan'  => $validated['id_satuan'] ?? null,
            'harga'      => $validated['harga'],
            'keterangan' => $validated['keterangan'] ?? null,
            'updated_at' => now(),
        ]);

        $after = DB::table('boq_tambahan')->where('id_boq_tambahan', $id)->get()->toJson();
        saveAudit('boq_tambahan', $id, 'Update', $before, $after);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $row = DB::table('boq_tambahan')->where('id_boq_tambahan', $id)->first();
        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);

        if ($this->woIsCompleted($row->id_wo)) {
            return response()->json(['success' => false, 'message' => 'WO sudah selesai, tidak dapat menghapus item.'], 403);
        }

        $before = DB::table('boq_tambahan')->where('id_boq_tambahan', $id)->get()->toJson();
        DB::table('boq_tambahan')->where('id_boq_tambahan', $id)->update(['deleted_at' => now()]);
        saveAudit('boq_tambahan', $id, 'Delete', $before, '');

        return response()->json(['success' => true]);
    }

    public function history($id)
    {
        return $this->auditHistory($id);
    }
}
