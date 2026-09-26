<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Sales Quotation — Fase 2: BOQ Other/Sampling estimasi (sq_boq_tambahan).
 * CRUD 1 baris, sama pola dengan BoqTambahanController versi SO, tapi
 * `jenis` dikirim langsung dari request (bukan 2 subclass) karena SQ tidak
 * punya slug permission terpisah untuk Other vs Sampling.
 */
class SqBoqTambahanController extends Controller
{
    public function listByWo($id_sq_wo)
    {
        $rows = DB::table('sq_boq_tambahan as t')
            ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 't.id_satuan')
            ->where('t.id_sq_wo', $id_sq_wo)
            ->orderBy('t.created_at')
            ->select(['t.*', 'sat.nama as satuan'])
            ->get();

        return response()->json([
            'lainnya' => $rows->where('jenis', 'lainnya')->values(),
            'sampling' => $rows->where('jenis', 'sampling')->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_sq_wo' => 'required|integer|exists:sq_work_orders,id_sq_wo',
            'jenis' => 'required|in:lainnya,sampling',
            'nama_item' => 'required|string|max:255',
            'qty' => 'required|integer|min:1',
            'id_satuan' => 'nullable|integer|exists:satuan,id_satuan',
            'harga' => 'required|integer|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $id = DB::table('sq_boq_tambahan')->insertGetId(array_merge($validated, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function update(Request $request, $id)
    {
        $row = DB::table('sq_boq_tambahan')->where('id_sq_boq_tambahan', $id)->first();
        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);

        $validated = $request->validate([
            'nama_item' => 'required|string|max:255',
            'qty' => 'required|integer|min:1',
            'id_satuan' => 'nullable|integer|exists:satuan,id_satuan',
            'harga' => 'required|integer|min:0',
            'keterangan' => 'nullable|string',
        ]);

        DB::table('sq_boq_tambahan')->where('id_sq_boq_tambahan', $id)->update(array_merge($validated, [
            'updated_at' => now(),
        ]));

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $row = DB::table('sq_boq_tambahan')->where('id_sq_boq_tambahan', $id)->first();
        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);

        DB::table('sq_boq_tambahan')->where('id_sq_boq_tambahan', $id)->delete();

        return response()->json(['success' => true]);
    }
}
