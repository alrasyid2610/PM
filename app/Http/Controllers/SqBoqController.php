<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Support\SqLock;
use Illuminate\Support\Facades\DB;

/**
 * Sales Quotation — Fase 2: BOQ estimasi (sq_boq + sq_boq_items). Pola
 * "whole-replace" per WO, sama seperti BoqController::update() versi SO
 * (satu kali submit = daftar section lengkap, diselisihkan dengan yang
 * sudah ada di DB) — tapi TANPA guard "masih dipakai FWO aktif" karena di
 * Fase 2 belum ada sq_fieldworks; begitu Fase 3 menambahkan
 * sq_fieldwork_boq (FK cascade ke sq_boq), penghapusan section di sini akan
 * otomatis ikut menghapus Fieldwork BOQ yang mengacu ke situ — wajar untuk
 * dokumen estimasi yang masih bebas diubah, beda dari SO yang sudah final.
 */
class SqBoqController extends Controller
{
    public function show($id_sq_wo)
    {
        $rows = DB::table('sq_boq as b')
            ->leftJoin('testing_points as tp', 'b.id_testing_point', '=', 'tp.id_testing_point')
            ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
            ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
            ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 'b.id_satuan')
            ->where('b.id_sq_wo', $id_sq_wo)
            ->select([
                'b.id_sq_boq', 'b.id_testing_point', 'b.item_produk_alternate',
                'b.qty', 'b.id_satuan', 'sat.nama as satuan', 'b.harga', 'b.discount', 'b.keterangan',
                DB::raw("TRIM(CONCAT_WS(' ', NULLIF(tms.judul_indonesia,''), NULLIF(ts.nomor,''), NULLIF(tp.nama,''))) as point_name"),
            ])
            ->get();

        $boqIds = $rows->pluck('id_sq_boq');
        $items = DB::table('sq_boq_items as bi')
            ->leftJoin('testing_items as ti', 'bi.id_testing_item', '=', 'ti.id_testing_item')
            ->leftJoin('testing_units as tu', 'ti.id_testing_unit', '=', 'tu.id_testing_unit')
            ->whereIn('bi.id_sq_boq', $boqIds)
            ->select(['bi.id_sq_boq', 'bi.id_testing_item', 'ti.judul_indonesia', 'ti.judul_inggris', 'ti.nilai', 'tu.kode as kode_unit'])
            ->get()
            ->groupBy('id_sq_boq');

        return response()->json($rows->map(function ($r) use ($items) {
            $r->items = $items->get($r->id_sq_boq, collect())->values()->toArray();
            return $r;
        })->values());
    }

    public function save(Request $request, $id_sq_wo)
    {
        if ($lock = SqLock::byWo($id_sq_wo)) return $lock;
        $wo = DB::table('sq_work_orders')->where('id_sq_wo', $id_sq_wo)->first();
        if (!$wo) return response()->json(['message' => 'SQ Work Order tidak ditemukan'], 404);

        $validated = $request->validate([
            'sections' => 'nullable|array',
            'sections.*.id_testing_point' => 'required|integer',
            'sections.*.item_produk_alternate' => 'nullable|string',
            'sections.*.qty' => 'nullable|integer',
            'sections.*.id_satuan' => 'nullable|integer|exists:satuan,id_satuan',
            'sections.*.harga' => 'nullable|numeric',
            'sections.*.discount' => 'nullable|integer|min:0',
            'sections.*.keterangan' => 'nullable|string',
            'sections.*.items' => 'nullable|array',
            'sections.*.items.*' => 'required|integer',
        ]);
        $sections = $validated['sections'] ?? [];

        $incomingPtIds = collect($sections)->pluck('id_testing_point');
        if ($incomingPtIds->count() !== $incomingPtIds->unique()->count()) {
            return response()->json(['success' => false, 'message' => 'Terdapat duplikasi Testing Point dalam data yang dikirim.'], 422);
        }

        $existing = DB::table('sq_boq')->where('id_sq_wo', $id_sq_wo)->get()->keyBy('id_testing_point');
        $removedPtIds = $existing->keys()->diff($incomingPtIds);

        foreach ($removedPtIds as $ptId) {
            $boqId = $existing[$ptId]->id_sq_boq;
            DB::table('sq_boq_items')->where('id_sq_boq', $boqId)->delete();
            DB::table('sq_boq')->where('id_sq_boq', $boqId)->delete();
        }

        foreach ($sections as $section) {
            $ptId = $section['id_testing_point'];
            $newItemIds = $section['items'] ?? [];

            if ($existing->has($ptId)) {
                $boqId = $existing[$ptId]->id_sq_boq;

                DB::table('sq_boq')->where('id_sq_boq', $boqId)->update([
                    'item_produk_alternate' => $section['item_produk_alternate'] ?? null,
                    'qty' => $section['qty'] ?? 0,
                    'id_satuan' => $section['id_satuan'] ?? null,
                    'harga' => $section['harga'] ?? 0,
                    'discount' => (int) ($section['discount'] ?? 0),
                    'keterangan' => $section['keterangan'] ?? null,
                    'updated_at' => now(),
                ]);

                $existingItemIds = DB::table('sq_boq_items')->where('id_sq_boq', $boqId)->pluck('id_testing_item');
                $toInsert = array_diff($newItemIds, $existingItemIds->toArray());
                $toDelete = $existingItemIds->diff($newItemIds);

                if (!empty($toInsert)) {
                    DB::table('sq_boq_items')->insert(array_map(fn($itemId) => [
                        'id_sq_boq' => $boqId, 'id_testing_item' => $itemId,
                        'created_at' => now(), 'updated_at' => now(),
                    ], array_values($toInsert)));
                }
                if ($toDelete->isNotEmpty()) {
                    DB::table('sq_boq_items')->where('id_sq_boq', $boqId)->whereIn('id_testing_item', $toDelete->toArray())->delete();
                }
            } else {
                $boqId = DB::table('sq_boq')->insertGetId([
                    'id_sq_wo' => $id_sq_wo,
                    'id_testing_point' => $ptId,
                    'item_produk_alternate' => $section['item_produk_alternate'] ?? null,
                    'qty' => $section['qty'] ?? 0,
                    'id_satuan' => $section['id_satuan'] ?? null,
                    'harga' => $section['harga'] ?? 0,
                    'discount' => (int) ($section['discount'] ?? 0),
                    'keterangan' => $section['keterangan'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (!empty($newItemIds)) {
                    DB::table('sq_boq_items')->insert(array_map(fn($itemId) => [
                        'id_sq_boq' => $boqId, 'id_testing_item' => $itemId,
                        'created_at' => now(), 'updated_at' => now(),
                    ], $newItemIds));
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'BOQ berhasil disimpan']);
    }
}
