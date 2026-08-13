<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasAuditHistory;

/**
 * Base CRUD untuk "BOQ Others" & "BOQ Sampling" level FWO (tabel
 * fwo_boq_tambahan, dibedakan kolom `jenis`) — lihat Project Reference
 * bagian "PRD — Struktur Modul Lab Testing". Item bebas (tidak terikat
 * testing_points/testing_items), beda dengan tab "Fieldwork BOQ".
 *
 * Dua controller konkret (FwoBoqOtherController, FwoBoqSamplingController)
 * extends class ini dan cuma override jenis() — supaya masing-masing bisa
 * punya permission slug sendiri (fwo-boq-other / fwo-boq-sampling) sesuai
 * pola master data, bukan digabung ke permission fieldworks.
 */
abstract class FwoBoqTambahanController extends Controller
{
    use HasAuditHistory;

    abstract protected function jenis(): string;

    protected function auditTable(): string { return 'fwo_boq_tambahan'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_fwo_boq_tambahan']; }

    private function fwoIsCompleted($id_fwo): bool
    {
        $fwo = DB::table('fieldworks')->where('id_fwo', $id_fwo)->first(['status']);
        return $fwo && $fwo->status === 'completed';
    }

    public function listByFwo($id_fwo)
    {
        $rows = DB::table('fwo_boq_tambahan as t')
            ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 't.id_satuan')
            ->where('t.id_fwo', $id_fwo)
            ->where('t.jenis', $this->jenis())
            ->whereNull('t.deleted_at')
            ->orderBy('t.created_at')
            ->select([
                't.id_fwo_boq_tambahan', 't.nama_item', 't.qty', 't.id_satuan',
                'sat.nama as satuan', 't.harga', 't.keterangan', 't.created_at',
            ])
            ->get();

        $fwo = DB::table('fieldworks')->where('id_fwo', $id_fwo)->first(['status']);

        return response()->json([
            'data'       => $rows,
            'total'      => $rows->sum(fn($r) => (int) $r->qty * (int) $r->harga),
            'fwo_status' => $fwo->status ?? null,
        ]);
    }

    public function store(Request $request)
    {
        if ($this->fwoIsCompleted($request->id_fwo)) {
            return response()->json(['success' => false, 'message' => 'FWO sudah selesai, tidak dapat menambah item.'], 403);
        }

        $validated = $request->validate([
            'id_fwo'     => 'required|integer',
            'nama_item'  => 'required|string|max:255',
            'qty'        => 'required|integer|min:1',
            'id_satuan'  => 'nullable|integer|exists:satuan,id_satuan',
            'harga'      => 'required|integer|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $id = DB::table('fwo_boq_tambahan')->insertGetId([
            'id_fwo'     => $validated['id_fwo'],
            'jenis'      => $this->jenis(),
            'nama_item'  => $validated['nama_item'],
            'qty'        => $validated['qty'],
            'id_satuan'  => $validated['id_satuan'] ?? null,
            'harga'      => $validated['harga'],
            'keterangan' => $validated['keterangan'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $after = DB::table('fwo_boq_tambahan')->where('id_fwo_boq_tambahan', $id)->get()->toJson();
        saveAudit('fwo_boq_tambahan', $id, 'Create', '', $after);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function show($id)
    {
        $row = DB::table('fwo_boq_tambahan')
            ->where('id_fwo_boq_tambahan', $id)
            ->where('jenis', $this->jenis())
            ->whereNull('deleted_at')
            ->first();

        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);
        return response()->json($row);
    }

    public function update(Request $request, $id)
    {
        $row = DB::table('fwo_boq_tambahan')->where('id_fwo_boq_tambahan', $id)->first();
        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);

        if ($this->fwoIsCompleted($row->id_fwo)) {
            return response()->json(['success' => false, 'message' => 'FWO sudah selesai, tidak dapat mengubah item.'], 403);
        }

        $validated = $request->validate([
            'nama_item'  => 'required|string|max:255',
            'qty'        => 'required|integer|min:1',
            'id_satuan'  => 'nullable|integer|exists:satuan,id_satuan',
            'harga'      => 'required|integer|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $before = DB::table('fwo_boq_tambahan')->where('id_fwo_boq_tambahan', $id)->get()->toJson();

        DB::table('fwo_boq_tambahan')->where('id_fwo_boq_tambahan', $id)->update([
            'nama_item'  => $validated['nama_item'],
            'qty'        => $validated['qty'],
            'id_satuan'  => $validated['id_satuan'] ?? null,
            'harga'      => $validated['harga'],
            'keterangan' => $validated['keterangan'] ?? null,
            'updated_at' => now(),
        ]);

        $after = DB::table('fwo_boq_tambahan')->where('id_fwo_boq_tambahan', $id)->get()->toJson();
        saveAudit('fwo_boq_tambahan', $id, 'Update', $before, $after);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $row = DB::table('fwo_boq_tambahan')->where('id_fwo_boq_tambahan', $id)->first();
        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);

        if ($this->fwoIsCompleted($row->id_fwo)) {
            return response()->json(['success' => false, 'message' => 'FWO sudah selesai, tidak dapat menghapus item.'], 403);
        }

        $before = DB::table('fwo_boq_tambahan')->where('id_fwo_boq_tambahan', $id)->get()->toJson();
        DB::table('fwo_boq_tambahan')->where('id_fwo_boq_tambahan', $id)->update(['deleted_at' => now()]);
        saveAudit('fwo_boq_tambahan', $id, 'Delete', $before, '');

        return response()->json(['success' => true]);
    }

    public function history($id)
    {
        return $this->auditHistory($id);
    }
}
