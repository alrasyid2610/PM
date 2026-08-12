<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FieldworkBoqController extends Controller
{
    public function byFwo(int $id_fwo)
    {
        $rows = DB::table('fieldwork_boq as fb')
            ->leftJoin('boq as b', 'fb.id_boq', '=', 'b.id_boq')
            ->leftJoin('testing_points as tp', 'fb.id_testing_point', '=', 'tp.id_testing_point')
            ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
            ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
            ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 'b.id_satuan')
            ->where('fb.id_fwo', $id_fwo)
            ->whereNull('fb.deleted_at')
            ->select([
                'fb.id_fwo_boq',
                'fb.id_boq',
                'fb.id_testing_point',
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

        // Hitung qty yang sudah dipakai FWO lain yang belum dihapus untuk setiap BOQ
        $boqIdsList  = $rows->pluck('id_boq');
        $usedByOthers = DB::table('fieldwork_boq as fb')
            ->join('fieldworks as fw', 'fw.id_fwo', '=', 'fb.id_fwo')
            ->whereIn('fb.id_boq', $boqIdsList)
            ->where('fb.id_fwo', '!=', $id_fwo)
            ->whereNull('fw.deleted_at')
            ->whereNull('fb.deleted_at')
            ->selectRaw('fb.id_boq, SUM(COALESCE(fb.qty, 0)) as used_qty')
            ->groupBy('fb.id_boq')
            ->pluck('used_qty', 'id_boq');

        $fboqIds = $rows->pluck('id_fwo_boq');

        $itemsGrouped = DB::table('fieldwork_boq_items as fbi')
            ->leftJoin('testing_items as ti', 'fbi.id_testing_item', '=', 'ti.id_testing_item')
            ->leftJoin('testing_units as tu', 'ti.id_testing_unit', '=', 'tu.id_testing_unit')
            ->whereIn('fbi.id_fwo_boq', $fboqIds)
            ->select([
                'fbi.id_fwo_boq',
                'fbi.id_testing_item',
                'ti.judul_indonesia',
                'ti.judul_inggris',
                'ti.nilai',
                'tu.kode as kode_unit',
            ])
            ->get()
            ->groupBy('id_fwo_boq');

        $result = $rows->map(function ($row) use ($itemsGrouped, $usedByOthers) {
            $items = ($itemsGrouped->get($row->id_fwo_boq) ?? collect())
                ->map(fn($item) => [
                    'id_testing_item' => $item->id_testing_item,
                    'judul_indonesia' => $item->judul_indonesia,
                    'judul_inggris'   => $item->judul_inggris,
                    'nilai'           => $item->nilai,
                    'kode_unit'       => $item->kode_unit,
                ])
                ->values()
                ->toArray();

            $remaining    = max(0, (int)($row->boq_qty ?? 0) - (int)($usedByOthers[$row->id_boq] ?? 0));
            $unallocated  = max(0, $remaining - (int)($row->qty ?? 0));

            return [
                'id_fwo_boq'       => $row->id_fwo_boq,
                'id_boq'           => $row->id_boq,
                'id_testing_point' => $row->id_testing_point,
                'point_name'       => $row->point_name,
                'qty'              => $row->qty,
                'boq_qty'          => $row->boq_qty,
                'remaining_qty'    => $remaining,
                'unallocated_qty'  => $unallocated,
                'satuan'           => $row->satuan,
                'harga'            => $row->harga,
                'keterangan'       => $row->keterangan,
                'items'            => $items,
            ];
        })->values()->toArray();

        return response()->json($result);
    }

    public function forCopy(int $id_fwo)
    {
        $fwo = DB::table('fieldworks')->where('id_fwo', $id_fwo)->first();
        if (!$fwo) {
            return response()->json(['message' => 'FWO tidak ditemukan'], 404);
        }

        // Semua BOQ item milik WO ini
        $boqRows = DB::table('boq as b')
            ->leftJoin('testing_points as tp', 'b.id_testing_point', '=', 'tp.id_testing_point')
            ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
            ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
            ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 'b.id_satuan')
            ->where('b.id_wo', $fwo->id_wo)
            ->select([
                'b.id_boq',
                'b.id_testing_point',
                'b.qty as boq_qty',
                'sat.nama as satuan',
                DB::raw("TRIM(CONCAT_WS(' ', NULLIF(tms.judul_indonesia,''), NULLIF(ts.nomor,''), NULLIF(tp.nama,''))) as point_name"),
            ])
            ->get();

        if ($boqRows->isEmpty()) {
            return response()->json([]);
        }

        $boqIds = $boqRows->pluck('id_boq');

        // Qty yang sudah dipakai semua FWO yang belum dihapus (termasuk source FWO)
        $usedByAll = DB::table('fieldwork_boq as fb')
            ->join('fieldworks as fw', 'fw.id_fwo', '=', 'fb.id_fwo')
            ->whereIn('fb.id_boq', $boqIds)
            ->whereNull('fw.deleted_at')
            ->whereNull('fb.deleted_at')
            ->selectRaw('fb.id_boq, SUM(COALESCE(fb.qty, 0)) as used_qty')
            ->groupBy('fb.id_boq')
            ->pluck('used_qty', 'id_boq');

        // Qty dari source FWO per BOQ (untuk pre-fill)
        $sourceQtys = DB::table('fieldwork_boq')
            ->where('id_fwo', $id_fwo)
            ->whereIn('id_boq', $boqIds)
            ->pluck('qty', 'id_boq');

        // Items per BOQ (dari boq_items, bukan fieldwork_boq_items)
        $boqItems = DB::table('boq_items as bi')
            ->leftJoin('testing_items as ti', 'bi.id_testing_item', '=', 'ti.id_testing_item')
            ->leftJoin('testing_units as tu', 'ti.id_testing_unit', '=', 'tu.id_testing_unit')
            ->whereIn('bi.id_boq', $boqIds)
            ->select([
                'bi.id_boq',
                'bi.id_testing_item',
                'ti.judul_indonesia',
                'ti.judul_inggris',
                'ti.nilai',
                'tu.kode as kode_unit',
            ])
            ->get()
            ->groupBy('id_boq');

        $result = $boqRows->map(function ($row) use ($usedByAll, $sourceQtys, $boqItems) {
            $used        = (int)($usedByAll[$row->id_boq] ?? 0);
            $unallocated = max(0, (int)($row->boq_qty ?? 0) - $used);
            $sourceQty   = $sourceQtys[$row->id_boq] ?? null;

            $items = ($boqItems->get($row->id_boq) ?? collect())
                ->map(fn($item) => [
                    'id_testing_item' => $item->id_testing_item,
                    'judul_indonesia' => $item->judul_indonesia,
                    'judul_inggris'   => $item->judul_inggris,
                    'nilai'           => $item->nilai,
                    'kode_unit'       => $item->kode_unit,
                ])
                ->values()
                ->toArray();

            return [
                'id_boq'           => $row->id_boq,
                'id_testing_point' => $row->id_testing_point,
                'point_name'       => $row->point_name,
                'qty'              => $sourceQty,
                'boq_qty'          => $row->boq_qty,
                'unallocated_qty'  => $unallocated,
                'satuan'           => $row->satuan,
                'items'            => $items,
            ];
        })->values()->toArray();

        return response()->json($result);
    }

    public function update(Request $request, int $id_fwo)
    {
        $fwo = DB::table('fieldworks')->where('id_fwo', $id_fwo)->first();
        if ($fwo && $fwo->status === 'completed') {
            return response()->json(['message' => 'FWO sudah selesai, data tidak dapat diubah.'], 403);
        }

        $validated = $request->validate([
            'sections'                => 'present|array',
            'sections.*.id_boq'       => 'required|integer',
            'sections.*.qty'          => 'nullable|integer|min:1',
            'sections.*.keterangan'   => 'nullable|string',
        ]);

        foreach ($validated['sections'] as $sec) {
            $boq = DB::table('boq')->where('id_boq', $sec['id_boq'])->first();
            if (!$boq) {
                return response()->json(['message' => "BOQ #{$sec['id_boq']} tidak ditemukan"], 422);
            }
            if (!empty($sec['qty'])) {
                // Cek apakah qty baru lebih kecil dari jumlah sample yang sudah ada
                $existingFwoBoq = DB::table('fieldwork_boq')
                    ->where('id_fwo', $id_fwo)
                    ->where('id_boq', $sec['id_boq'])
                    ->whereNull('deleted_at')
                    ->first();

                if ($existingFwoBoq) {
                    $sampleCount = DB::table('lab_samples')
                        ->where('id_fwo_boq', $existingFwoBoq->id_fwo_boq)
                        ->count();

                    if ($sampleCount > (int)$sec['qty']) {
                        $ptName = DB::table('testing_points')
                            ->where('id_testing_point', $boq->id_testing_point)
                            ->value('nama') ?? "BOQ #{$sec['id_boq']}";
                        return response()->json([
                            'message' => "Qty \"{$ptName}\" tidak bisa dikurangi menjadi {$sec['qty']} karena sudah ada {$sampleCount} sample. Hapus sample yang berlebih terlebih dahulu.",
                        ], 422);
                    }
                }

                $usedByOthers = (int) DB::table('fieldwork_boq as fb')
                    ->join('fieldworks as fw', 'fw.id_fwo', '=', 'fb.id_fwo')
                    ->where('fb.id_boq', $sec['id_boq'])
                    ->where('fb.id_fwo', '!=', $id_fwo)
                    ->whereNull('fw.deleted_at')
                    ->whereNull('fb.deleted_at')
                    ->sum('fb.qty');
                $remaining = (int)($boq->qty ?? 0) - $usedByOthers;
                if ($sec['qty'] > $remaining) {
                    $ptName = DB::table('testing_points')
                        ->where('id_testing_point', $boq->id_testing_point)
                        ->value('nama') ?? "BOQ #{$sec['id_boq']}";
                    return response()->json([
                        'message' => "Qty untuk \"{$ptName}\" melebihi batas (maks BOQ: {$boq->qty})"
                    ], 422);
                }
            }
        }

        // Mapping id_boq → row lama (aktif)
        $existing = DB::table('fieldwork_boq')
            ->where('id_fwo', $id_fwo)
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('id_boq'); // [id_boq => row]

        $incomingBoqIds = collect($validated['sections'])->pluck('id_boq');

        // Soft-delete baris yang tidak ada di list baru
        $toDelete = $existing->keys()->diff($incomingBoqIds);
        if ($toDelete->isNotEmpty()) {
            // Kumpulkan id_fwo_boq yang akan dihapus
            $fwoBoqIdsToDelete = $existing
                ->whereIn('id_boq', $toDelete->values()->toArray())
                ->pluck('id_fwo_boq');

            // Blokir jika ada lab_samples dengan status diambil atau dikirim
            $blockedSamples = DB::table('lab_samples')
                ->whereIn('id_fwo_boq', $fwoBoqIdsToDelete)
                ->whereIn('status', ['diambil', 'dikirim'])
                ->count();

            if ($blockedSamples > 0) {
                $ptNames = $existing
                    ->whereIn('id_boq', $toDelete->values()->toArray())
                    ->map(function ($row) {
                        return DB::table('testing_points')
                            ->where('id_testing_point', $row->id_testing_point)
                            ->value('nama') ?? "BOQ #{$row->id_boq}";
                    })
                    ->values()
                    ->join(', ');

                return response()->json([
                    'message' => "BOQ [{$ptNames}] tidak bisa dihapus karena memiliki sample yang sudah diambil atau dikirim ke lab.",
                ], 422);
            }

            // Hard delete lab_samples yang masih belum_diambil
            DB::table('lab_samples')
                ->whereIn('id_fwo_boq', $fwoBoqIdsToDelete)
                ->where('status', 'belum_diambil')
                ->delete();

            // Hard delete fieldwork_boq_items
            DB::table('fieldwork_boq_items')
                ->whereIn('id_fwo_boq', $fwoBoqIdsToDelete)
                ->delete();

            // Soft-delete fieldwork_boq
            DB::table('fieldwork_boq')
                ->where('id_fwo', $id_fwo)
                ->whereIn('id_boq', $toDelete)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now()]);
        }

        foreach ($validated['sections'] as $sec) {
            $boq = DB::table('boq')->where('id_boq', $sec['id_boq'])->first();

            if (isset($existing[$sec['id_boq']])) {
                // UPDATE — pakai id_fwo_boq yang sudah ada
                $fwoBoqId = $existing[$sec['id_boq']]->id_fwo_boq;
                DB::table('fieldwork_boq')->where('id_fwo_boq', $fwoBoqId)->update([
                    'qty'        => $sec['qty'] ?? null,
                    'keterangan' => $sec['keterangan'] ?? null,
                    'updated_at' => now(),
                ]);
            } else {
                // INSERT — BOQ baru yang belum ada di FWO ini
                $fwoBoqId = DB::table('fieldwork_boq')->insertGetId([
                    'id_fwo'           => $id_fwo,
                    'id_boq'           => $sec['id_boq'],
                    'id_testing_point' => $boq->id_testing_point,
                    'qty'              => $sec['qty'] ?? null,
                    'keterangan'       => $sec['keterangan'] ?? null,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }

            // Sync fieldwork_boq_items (selalu replace dari boq_items master)
            DB::table('fieldwork_boq_items')->where('id_fwo_boq', $fwoBoqId)->delete();
            $boqItems = DB::table('boq_items')->where('id_boq', $sec['id_boq'])->get();
            if ($boqItems->isNotEmpty()) {
                DB::table('fieldwork_boq_items')->insert(
                    $boqItems->map(fn($item) => [
                        'id_fwo_boq'      => $fwoBoqId,
                        'id_testing_item' => $item->id_testing_item,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ])->toArray()
                );
            }
        }

        return response()->json(['success' => true, 'message' => 'Fieldwork BOQ berhasil diperbarui']);
    }
}
