<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class BoqController extends Controller
{
    public function index()
    {
        return view('boq.index', [
            'title' => 'BOQ'
        ]);
    }

    public function data()
    {
        $data = DB::table('boq as b')
            ->leftJoin('work_orders as wo', 'b.id_wo', '=', 'wo.id_wo')
            ->leftJoin('boq_items as bi', 'b.id_boq', '=', 'bi.id_boq')
            ->select([
                'b.id_wo',
                'wo.no_wo',
                'wo.judul_pekerjaan',
                DB::raw('COUNT(DISTINCT b.id_boq) as total_section'),
                DB::raw('COUNT(bi.id_boq_items) as total_item'),
                DB::raw('MIN(b.created_at) as created_at'),
            ])
            ->groupBy('b.id_wo', 'wo.no_wo', 'wo.judul_pekerjaan')
            ->orderBy('wo.no_wo')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function show(Request $request, $id)
    {
        $wo = DB::table('work_orders')->where('id_wo', $id)->first();

        if (!$wo) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $boqRows = DB::table('boq as b')
            ->leftJoin('testing_points as tp', 'b.id_testing_point', '=', 'tp.id_testing_point')
            ->where('b.id_wo', $id)
            ->select([
                'b.id_boq',
                'b.id_testing_point',
                'b.item_produk_alternate',
                'b.qty',
                'b.satuan',
                'b.harga',
                'b.keterangan',
                'tp.nama as point_name',
            ])
            ->get();

        $boqIds = $boqRows->pluck('id_boq');

        $boqItemsGrouped = DB::table('boq_items as bi')
            ->leftJoin('testing_items as ti', 'bi.id_testing_item', '=', 'ti.id_testing_item')
            ->leftJoin('testing_units as tu', 'ti.id_testing_unit', '=', 'tu.id_testing_unit')
            ->whereIn('bi.id_boq', $boqIds)
            ->select([
                'bi.id_boq',
                'bi.id_boq_items',
                'bi.id_testing_item',
                'ti.judul_indonesia',
                'ti.judul_inggris',
                'ti.nilai',
                'tu.kode as kode_unit',
            ])
            ->get()
            ->groupBy('id_boq');

        $sections = $boqRows->map(function ($boq) use ($boqItemsGrouped) {
            $items = ($boqItemsGrouped->get($boq->id_boq) ?? collect())->map(function ($item) {
                return [
                    'id_boq_items'    => $item->id_boq_items,
                    'id_testing_item' => $item->id_testing_item,
                    'judul_indonesia' => $item->judul_indonesia,
                    'judul_inggris'   => $item->judul_inggris,
                    'nilai'           => $item->nilai,
                    'kode_unit'       => $item->kode_unit,
                ];
            })->values()->toArray();

            return [
                'id_boq'                => $boq->id_boq,
                'id_testing_point'      => $boq->id_testing_point,
                'point_name'            => $boq->point_name,
                'item_produk_alternate' => $boq->item_produk_alternate,
                'qty'                   => $boq->qty,
                'satuan'                => $boq->satuan,
                'harga'                 => $boq->harga,
                'keterangan'            => $boq->keterangan,
                'items'                 => $items,
            ];
        })->values()->toArray();

        return response()->json([
            'id_wo'           => $wo->id_wo,
            'no_wo'           => $wo->no_wo,
            'judul_pekerjaan' => $wo->judul_pekerjaan,
            'sections'        => $sections,
        ]);
    }

    public function byWo($id_wo)
    {
        $data = DB::table('boq as b')
            ->leftJoin('testing_points as tp', 'b.id_testing_point', '=', 'tp.id_testing_point')
            ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
            ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
            ->leftJoin('boq_items as bi', 'b.id_boq', '=', 'bi.id_boq')
            ->where('b.id_wo', $id_wo)
            ->select([
                'b.id_wo',
                'b.id_testing_point',
                DB::raw("CONCAT(COALESCE(tms.kode,''), '-', COALESCE(ts.nomor,''), '-', COALESCE(tp.nama,'')) as kode"),
                DB::raw('COUNT(bi.id_boq_items) as total_item'),
            ])
            ->groupBy('b.id_wo', 'b.id_testing_point', 'tms.kode', 'ts.nomor', 'tp.nama')
            ->orderBy('tms.kode')
            ->orderBy('ts.nomor')
            ->get();

        return response()->json($data);
    }

    public function create()
    {
        return view('boq.create', [
            'title' => 'Create BOQ',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_wo'                            => 'required|integer',
            'sections'                         => 'required|array|min:1',
            'sections.*.id_testing_point'      => 'required|integer',
            'sections.*.item_produk_alternate' => 'nullable|string',
            'sections.*.qty'                   => 'nullable|integer',
            'sections.*.satuan'                => 'nullable|string|max:255',
            'sections.*.harga'                 => 'nullable|numeric',
            'sections.*.keterangan'            => 'nullable|string',
            'sections.*.items'                 => 'required|array|min:1',
            'sections.*.items.*'               => 'required|integer',
        ]);

        foreach ($validated['sections'] as $section) {
            $boqId = DB::table('boq')->insertGetId([
                'id_wo'                 => $validated['id_wo'],
                'id_testing_point'      => $section['id_testing_point'],
                'item_produk_alternate' => $section['item_produk_alternate'] ?? null,
                'qty'                   => $section['qty'] ?? null,
                'satuan'                => $section['satuan'] ?? null,
                'harga'                 => $section['harga'] ?? null,
                'keterangan'            => $section['keterangan'] ?? null,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);

            $boqItems = array_map(fn($itemId) => [
                'id_boq'          => $boqId,
                'id_testing_item' => $itemId,
                'created_at'      => now(),
                'updated_at'      => now(),
            ], $section['items']);

            DB::table('boq_items')->insert($boqItems);
        }

        saveAudit('boq', $validated['id_wo'], 'create', null, json_encode($validated['sections']));

        return response()->json([
            'success' => true,
            'message' => 'BOQ berhasil disimpan',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'sections'                         => 'required|array|min:1',
            'sections.*.id_testing_point'      => 'required|integer',
            'sections.*.item_produk_alternate' => 'nullable|string',
            'sections.*.qty'                   => 'nullable|integer',
            'sections.*.satuan'                => 'nullable|string|max:255',
            'sections.*.harga'                 => 'nullable|numeric',
            'sections.*.keterangan'            => 'nullable|string',
            'sections.*.items'                 => 'required|array|min:1',
            'sections.*.items.*'               => 'required|integer',
        ]);

        $oldBoqIds = DB::table('boq')->where('id_wo', $id)->pluck('id_boq');
        $oldData   = DB::table('boq')->where('id_wo', $id)->get();

        DB::table('boq_items')->whereIn('id_boq', $oldBoqIds)->delete();
        DB::table('boq')->where('id_wo', $id)->delete();

        foreach ($validated['sections'] as $section) {
            $boqId = DB::table('boq')->insertGetId([
                'id_wo'                 => $id,
                'id_testing_point'      => $section['id_testing_point'],
                'item_produk_alternate' => $section['item_produk_alternate'] ?? null,
                'qty'                   => $section['qty'] ?? null,
                'satuan'                => $section['satuan'] ?? null,
                'harga'                 => $section['harga'] ?? null,
                'keterangan'            => $section['keterangan'] ?? null,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);

            $boqItems = array_map(fn($itemId) => [
                'id_boq'          => $boqId,
                'id_testing_item' => $itemId,
                'created_at'      => now(),
                'updated_at'      => now(),
            ], $section['items']);

            DB::table('boq_items')->insert($boqItems);
        }

        saveAudit('boq', $id, 'update', json_encode($oldData), json_encode($validated['sections']));

        return response()->json(['success' => true, 'message' => 'BOQ berhasil diperbarui']);
    }

    public function history($id)
    {
        $logs = DB::table('audit_logs as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.created_by')
            ->select(['a.*', 'u.name as created_by_name'])
            ->where('a.nama_table', 'boq')
            ->where('a.row_id', $id)
            ->orderByDesc('a.created_at')
            ->get();

        $pointNames = DB::table('testing_points')
            ->pluck('nama', 'id_testing_point')
            ->toArray();

        return response()->json($logs->map(function ($log) use ($pointNames) {
            $oldSections = $log->old_value ? (json_decode($log->old_value, true) ?? []) : [];
            $newSections = $log->new_value ? (json_decode($log->new_value, true) ?? []) : [];

            $oldByPt = [];
            foreach ($oldSections as $sec) {
                $pt = $sec['id_testing_point'] ?? null;
                if ($pt !== null) $oldByPt[$pt] = $sec;
            }

            $newByPt = [];
            foreach ($newSections as $sec) {
                $pt = $sec['id_testing_point'] ?? null;
                if ($pt !== null) $newByPt[$pt] = $sec;
            }

            $changes = [];

            foreach ($newByPt as $ptId => $newSec) {
                if (!array_key_exists($ptId, $oldByPt)) {
                    $name = $pointNames[$ptId] ?? "ID $ptId";
                    $changes[] = [
                        'field'     => "Section: $name",
                        'old_value' => null,
                        'new_value' => 'Ditambahkan (' . count($newSec['items'] ?? []) . ' item)',
                    ];
                }
            }

            foreach ($oldByPt as $ptId => $oldSec) {
                if (!array_key_exists($ptId, $newByPt)) {
                    $name = $pointNames[$ptId] ?? "ID $ptId";
                    $changes[] = [
                        'field'     => "Section: $name",
                        'old_value' => 'Ada (' . count($oldSec['items'] ?? []) . ' item)',
                        'new_value' => null,
                    ];
                }
            }

            foreach ($newByPt as $ptId => $newSec) {
                if (!array_key_exists($ptId, $oldByPt)) continue;
                $oldSec = $oldByPt[$ptId];
                $name   = $pointNames[$ptId] ?? "ID $ptId";

                $oldItemCount = count($oldSec['items'] ?? []);
                $newItemCount = count($newSec['items'] ?? []);
                if ($oldItemCount !== $newItemCount) {
                    $changes[] = [
                        'field'     => "$name — Jumlah item",
                        'old_value' => "$oldItemCount item",
                        'new_value' => "$newItemCount item",
                    ];
                }

                foreach (['item_produk_alternate', 'qty', 'satuan', 'harga', 'keterangan'] as $field) {
                    $oldVal = $oldSec[$field] ?? null;
                    $newVal = $newSec[$field] ?? null;
                    if ((string)$oldVal !== (string)$newVal) {
                        $changes[] = [
                            'field'     => "$name — $field",
                            'old_value' => $oldVal,
                            'new_value' => $newVal,
                        ];
                    }
                }
            }

            return [
                'id'              => $log->id,
                'action'          => strtolower($log->action),
                'changes'         => $changes,
                'total_changes'   => count($changes),
                'created_by_name' => $log->created_by_name ?? 'System',
                'created_at'      => $log->created_at,
            ];
        }));
    }

    public function select2ByWo(Request $request, $id_wo)
    {
        $search = $request->q;
        $id_fwo = $request->query('id_fwo');

        $data = DB::table('boq as b')
            ->leftJoin('testing_points as tp', 'b.id_testing_point', '=', 'tp.id_testing_point')
            ->where('b.id_wo', $id_wo)
            ->when($search, fn($q) => $q->where('tp.nama', 'like', "%$search%"))
            ->select(['b.id_boq', 'b.id_testing_point', 'b.qty', 'b.satuan', 'tp.nama as point_name'])
            ->get();

        $boqIds = $data->pluck('id_boq');
        $usedByOthers = DB::table('fieldwork_boq')
            ->whereIn('id_boq', $boqIds)
            ->when($id_fwo, fn($q) => $q->where('id_fwo', '!=', $id_fwo))
            ->selectRaw('id_boq, SUM(COALESCE(qty, 0)) as used_qty')
            ->groupBy('id_boq')
            ->pluck('used_qty', 'id_boq');

        return response()->json(
            $data->map(fn($item) => [
                'id'               => $item->id_boq,
                'text'             => $item->point_name ?? "BOQ #{$item->id_boq}",
                'id_testing_point' => $item->id_testing_point,
                'qty_boq'          => $item->qty,
                'remaining_qty'    => max(0, (int)($item->qty ?? 0) - (int)($usedByOthers[$item->id_boq] ?? 0)),
                'satuan'           => $item->satuan,
            ])
        );
    }

    public function sectionItems(Request $request, $id_boq)
    {
        $id_fwo = $request->query('id_fwo');

        $boq = DB::table('boq as b')
            ->leftJoin('testing_points as tp', 'b.id_testing_point', '=', 'tp.id_testing_point')
            ->where('b.id_boq', $id_boq)
            ->select(['b.id_boq', 'b.id_testing_point', 'b.qty', 'b.satuan', 'tp.nama as point_name'])
            ->first();

        if (!$boq) {
            return response()->json(['message' => 'Tidak ditemukan'], 404);
        }

        $usedByOthers = (int) DB::table('fieldwork_boq')
            ->where('id_boq', $id_boq)
            ->when($id_fwo, fn($q) => $q->where('id_fwo', '!=', $id_fwo))
            ->sum('qty');

        $remaining = max(0, (int)($boq->qty ?? 0) - $usedByOthers);

        $items = DB::table('boq_items as bi')
            ->leftJoin('testing_items as ti', 'bi.id_testing_item', '=', 'ti.id_testing_item')
            ->leftJoin('testing_units as tu', 'ti.id_testing_unit', '=', 'tu.id_testing_unit')
            ->where('bi.id_boq', $id_boq)
            ->select(['bi.id_testing_item', 'ti.judul_indonesia', 'ti.judul_inggris', 'ti.nilai', 'tu.kode as kode_unit'])
            ->get();

        return response()->json([
            'id_boq'           => $boq->id_boq,
            'id_testing_point' => $boq->id_testing_point,
            'point_name'       => $boq->point_name,
            'qty_boq'          => $boq->qty,
            'remaining_qty'    => $remaining,
            'satuan'           => $boq->satuan,
            'items'            => $items,
        ]);
    }

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('work_orders')
            ->where('no_wo', 'like', "%{$search}%")
            ->limit(10)
            ->get();

        return response()->json(
            $data->map(function ($item) {
                return [
                    'id'    => $item->id_wo,
                    'text'  => $item->no_wo . " - " . $item->judul_pekerjaan,
                    'judul' => $item->judul_pekerjaan,
                ];
            })
        );
    }
}
