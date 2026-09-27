<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Support\SqLock;
use Illuminate\Support\Facades\DB;

/**
 * SQ Fieldwork BOQ (Fase 3) — alokasi qty dari BOQ milik SQ WO induk ke tiap
 * SQ Fieldwork (hari kerja), pola whole-replace persis
 * `FieldworkBoqController` asli, dipetakan ke tabel `sq_*`:
 *   fieldwork_boq       → sq_fieldwork_boq        (kunci: id_boq → id_sq_boq)
 *   fieldwork_boq_items → sq_fieldwork_boq_items
 * Items SELALU disinkron utuh dari `sq_boq_items` milik BOQ itu (tidak bisa
 * dipilih sebagian) — sama seperti aslinya. TIDAK ada guard lab_samples
 * (SQ tidak pernah punya sample), TIDAK ada guard status 'completed' (pakai
 * SqLock berbasis status SQ, bukan status FWO — SQ FWO tidak punya status).
 */
class SqFieldworkBoqController extends Controller
{
    public function byFwo($id_sq_fwo)
    {
        $rows = DB::table('sq_fieldwork_boq as fb')
            ->leftJoin('sq_boq as b', 'fb.id_sq_boq', '=', 'b.id_sq_boq')
            ->leftJoin('testing_points as tp', 'b.id_testing_point', '=', 'tp.id_testing_point')
            ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
            ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
            ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 'b.id_satuan')
            ->where('fb.id_sq_fwo', $id_sq_fwo)
            ->select([
                'fb.id_sq_fwo_boq',
                'fb.id_sq_boq',
                'b.id_testing_point',
                'fb.qty',
                'fb.keterangan',
                DB::raw("TRIM(CONCAT_WS(' ', NULLIF(tms.judul_indonesia,''), NULLIF(ts.nomor,''), NULLIF(tp.nama,''))) as point_name"),
                'b.qty as boq_qty',
                'sat.nama as satuan',
                'b.harga',
            ])
            ->get();

        if ($rows->isEmpty()) {
            return response()->json([]);
        }

        $boqIdsList = $rows->pluck('id_sq_boq');
        $usedByOthers = DB::table('sq_fieldwork_boq as fb')
            ->whereIn('fb.id_sq_boq', $boqIdsList)
            ->where('fb.id_sq_fwo', '!=', $id_sq_fwo)
            ->selectRaw('fb.id_sq_boq, SUM(COALESCE(fb.qty, 0)) as used_qty')
            ->groupBy('fb.id_sq_boq')
            ->pluck('used_qty', 'id_sq_boq');

        $fboqIds = $rows->pluck('id_sq_fwo_boq');
        $itemsGrouped = DB::table('sq_fieldwork_boq_items as fbi')
            ->leftJoin('testing_items as ti', 'fbi.id_testing_item', '=', 'ti.id_testing_item')
            ->leftJoin('testing_units as tu', 'ti.id_testing_unit', '=', 'tu.id_testing_unit')
            ->whereIn('fbi.id_sq_fwo_boq', $fboqIds)
            ->select(['fbi.id_sq_fwo_boq', 'fbi.id_testing_item', 'ti.judul_indonesia', 'ti.judul_inggris', 'ti.nilai', 'tu.kode as kode_unit'])
            ->get()
            ->groupBy('id_sq_fwo_boq');

        $result = $rows->map(function ($row) use ($itemsGrouped, $usedByOthers) {
            $items = ($itemsGrouped->get($row->id_sq_fwo_boq) ?? collect())
                ->map(fn($i) => [
                    'id_testing_item' => $i->id_testing_item,
                    'judul_indonesia' => $i->judul_indonesia,
                    'judul_inggris' => $i->judul_inggris,
                    'nilai' => $i->nilai,
                    'kode_unit' => $i->kode_unit,
                ])->values()->toArray();

            $remaining = max(0, (int) ($row->boq_qty ?? 0) - (int) ($usedByOthers[$row->id_sq_boq] ?? 0));
            $unallocated = max(0, $remaining - (int) ($row->qty ?? 0));

            return [
                'id_sq_fwo_boq' => $row->id_sq_fwo_boq,
                'id_sq_boq' => $row->id_sq_boq,
                'id_testing_point' => $row->id_testing_point,
                'point_name' => $row->point_name,
                'qty' => $row->qty,
                'boq_qty' => $row->boq_qty,
                'remaining_qty' => $remaining,
                'unallocated_qty' => $unallocated,
                'satuan' => $row->satuan,
                'harga' => $row->harga,
                'keterangan' => $row->keterangan,
                'items' => $items,
            ];
        })->values()->toArray();

        return response()->json($result);
    }

    /**
     * Daftar BOQ milik SQ WO ini (untuk modal "Kelola BOQ") lengkap dengan
     * sisa qty yang belum dialokasikan ke SQ FWO lain — pola sama
     * BoqController::select2ByWo().
     */
    public function selectByWo(Request $request, $id_sq_wo)
    {
        $search = $request->q;
        $idSqFwo = $request->query('id_sq_fwo');

        $data = DB::table('sq_boq as b')
            ->leftJoin('testing_points as tp', 'b.id_testing_point', '=', 'tp.id_testing_point')
            ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
            ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
            ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 'b.id_satuan')
            ->where('b.id_sq_wo', $id_sq_wo)
            ->when($search, fn($q) => $q
                ->where('tp.nama', 'like', "%$search%")
                ->orWhere('tms.judul_indonesia', 'like', "%$search%")
                ->orWhere('ts.nomor', 'like', "%$search%"))
            ->select([
                'b.id_sq_boq',
                'b.id_testing_point',
                'b.qty',
                'sat.nama as satuan',
                DB::raw("TRIM(CONCAT_WS(' ', NULLIF(tms.judul_indonesia,''), NULLIF(ts.nomor,''), NULLIF(tp.nama,''))) as point_name"),
            ])
            ->get();

        $boqIds = $data->pluck('id_sq_boq');
        $usedByOthers = DB::table('sq_fieldwork_boq')
            ->whereIn('id_sq_boq', $boqIds)
            ->when($idSqFwo, fn($q) => $q->where('id_sq_fwo', '!=', $idSqFwo))
            ->selectRaw('id_sq_boq, SUM(COALESCE(qty, 0)) as used_qty')
            ->groupBy('id_sq_boq')
            ->pluck('used_qty', 'id_sq_boq');

        return response()->json($data->map(fn($item) => [
            'id' => $item->id_sq_boq,
            'text' => $item->point_name ?? "BOQ #{$item->id_sq_boq}",
            'id_testing_point' => $item->id_testing_point,
            'qty_boq' => $item->qty,
            'remaining_qty' => max(0, (int) ($item->qty ?? 0) - (int) ($usedByOthers[$item->id_sq_boq] ?? 0)),
            'satuan' => $item->satuan,
        ]));
    }

    /** Detail 1 BOQ + item-itemnya, dipakai tombol "lihat detail" di modal — pola BoqController::sectionItems(). */
    public function sectionItems(Request $request, $id_sq_boq)
    {
        $idSqFwo = $request->query('id_sq_fwo');

        $boq = DB::table('sq_boq as b')
            ->leftJoin('testing_points as tp', 'b.id_testing_point', '=', 'tp.id_testing_point')
            ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
            ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
            ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 'b.id_satuan')
            ->where('b.id_sq_boq', $id_sq_boq)
            ->select(['b.id_sq_boq', 'b.id_testing_point', 'b.qty', 'sat.nama as satuan',
                DB::raw("TRIM(CONCAT_WS(' ', NULLIF(tms.judul_indonesia,''), NULLIF(ts.nomor,''), NULLIF(tp.nama,''))) as point_name")])
            ->first();

        if (!$boq) return response()->json(['message' => 'Tidak ditemukan'], 404);

        $usedByOthers = (int) DB::table('sq_fieldwork_boq')
            ->where('id_sq_boq', $id_sq_boq)
            ->when($idSqFwo, fn($q) => $q->where('id_sq_fwo', '!=', $idSqFwo))
            ->sum('qty');
        $remaining = max(0, (int) ($boq->qty ?? 0) - $usedByOthers);

        $items = DB::table('sq_boq_items as bi')
            ->leftJoin('testing_items as ti', 'bi.id_testing_item', '=', 'ti.id_testing_item')
            ->leftJoin('testing_units as tu', 'ti.id_testing_unit', '=', 'tu.id_testing_unit')
            ->where('bi.id_sq_boq', $id_sq_boq)
            ->select(['bi.id_testing_item', 'ti.judul_indonesia', 'ti.judul_inggris', 'ti.nilai', 'tu.kode as kode_unit'])
            ->get();

        return response()->json([
            'id_sq_boq' => $boq->id_sq_boq,
            'id_testing_point' => $boq->id_testing_point,
            'point_name' => $boq->point_name,
            'qty_boq' => $boq->qty,
            'remaining_qty' => $remaining,
            'satuan' => $boq->satuan,
            'items' => $items,
        ]);
    }

    public function update(Request $request, $id_sq_fwo)
    {
        if ($lock = SqLock::byFwo($id_sq_fwo)) return $lock;

        $fwo = DB::table('sq_fieldworks')->where('id_sq_fwo', $id_sq_fwo)->first();
        if (!$fwo) return response()->json(['message' => 'SQ Fieldwork tidak ditemukan'], 404);

        $validated = $request->validate([
            'sections' => 'present|array',
            'sections.*.id_sq_boq' => 'required|integer',
            'sections.*.qty' => 'nullable|integer|min:1',
            'sections.*.keterangan' => 'nullable|string',
        ]);

        foreach ($validated['sections'] as $sec) {
            $boq = DB::table('sq_boq')->where('id_sq_boq', $sec['id_sq_boq'])->first();
            if (!$boq) {
                return response()->json(['message' => "BOQ #{$sec['id_sq_boq']} tidak ditemukan"], 422);
            }
            if (!empty($sec['qty'])) {
                $usedByOthers = (int) DB::table('sq_fieldwork_boq')
                    ->where('id_sq_boq', $sec['id_sq_boq'])
                    ->where('id_sq_fwo', '!=', $id_sq_fwo)
                    ->sum('qty');
                $remaining = (int) ($boq->qty ?? 0) - $usedByOthers;
                if ($sec['qty'] > $remaining) {
                    $ptName = DB::table('testing_points')->where('id_testing_point', $boq->id_testing_point)->value('nama') ?? "BOQ #{$sec['id_sq_boq']}";
                    return response()->json(['message' => "Qty untuk \"{$ptName}\" melebihi batas (maks BOQ: {$boq->qty})"], 422);
                }
            }
        }

        $existing = DB::table('sq_fieldwork_boq')->where('id_sq_fwo', $id_sq_fwo)->get()->keyBy('id_sq_boq');
        $incomingBoqIds = collect($validated['sections'])->pluck('id_sq_boq');

        $toDelete = $existing->keys()->diff($incomingBoqIds);
        if ($toDelete->isNotEmpty()) {
            $fwoBoqIdsToDelete = $existing->whereIn('id_sq_boq', $toDelete->values()->toArray())->pluck('id_sq_fwo_boq');
            DB::table('sq_fieldwork_boq_items')->whereIn('id_sq_fwo_boq', $fwoBoqIdsToDelete)->delete();
            DB::table('sq_fieldwork_boq')->where('id_sq_fwo', $id_sq_fwo)->whereIn('id_sq_boq', $toDelete)->delete();
        }

        foreach ($validated['sections'] as $sec) {
            if (isset($existing[$sec['id_sq_boq']])) {
                $fwoBoqId = $existing[$sec['id_sq_boq']]->id_sq_fwo_boq;
                DB::table('sq_fieldwork_boq')->where('id_sq_fwo_boq', $fwoBoqId)->update([
                    'qty' => $sec['qty'] ?? null,
                    'keterangan' => $sec['keterangan'] ?? null,
                    'updated_at' => now(),
                ]);
            } else {
                $fwoBoqId = DB::table('sq_fieldwork_boq')->insertGetId([
                    'id_sq_fwo' => $id_sq_fwo,
                    'id_sq_boq' => $sec['id_sq_boq'],
                    'qty' => $sec['qty'] ?? null,
                    'keterangan' => $sec['keterangan'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Items SELALU disinkron utuh dari sq_boq_items (bukan pilihan
            // manual) — pola sama persis FieldworkBoqController::update().
            DB::table('sq_fieldwork_boq_items')->where('id_sq_fwo_boq', $fwoBoqId)->delete();
            $boqItems = DB::table('sq_boq_items')->where('id_sq_boq', $sec['id_sq_boq'])->get();
            if ($boqItems->isNotEmpty()) {
                DB::table('sq_fieldwork_boq_items')->insert($boqItems->map(fn($item) => [
                    'id_sq_fwo_boq' => $fwoBoqId,
                    'id_testing_item' => $item->id_testing_item,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->toArray());
            }
        }

        return response()->json(['success' => true, 'message' => 'Fieldwork BOQ berhasil diperbarui']);
    }
}
