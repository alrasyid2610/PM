<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TestingItemController extends Controller
{
    public function index()
    {
        return view('testing-items.index');
    }

    public function data()
    {
        $query = DB::table('testing_items as ti')
            ->leftJoin('testing_points as tp', 'ti.id_testing_point', '=', 'tp.id_testing_point')
            ->leftJoin('testing_parameters as tpr', 'ti.id_testing_parameter', '=', 'tpr.id_testing_parameter')
            ->leftJoin('testing_units as tu', 'ti.id_testing_unit', '=', 'tu.id_testing_unit')
            ->select([
                'ti.id_testing_item',
                'ti.nomor',
                'ti.judul_indonesia',
                'tp.nama as point_nama',
                'tpr.kode as parameter_kode',
                'tu.kode as unit_kode',
                'ti.nilai',
                'ti.is_aktif',
                'ti.created_at'
            ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('is_aktif', function ($row) {
                return $row->is_aktif ? 'Aktif' : 'Tidak Aktif';
            })
            ->make(true);
    }

    public function create()
    {
        return view('testing-items.create');
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'id_testing_point' => 'required|integer',
            'judul_indonesia' => 'required|array',
            'judul_inggris' => 'required|array',
            'parameter' => 'required|array',
            'unit' => 'required|array',
            'nilai' => 'nullable|array',
            'keterangan' => 'nullable|array',
            'status' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {

            $pointId = $request->id_testing_point;

            /*
        |--------------------------------------------------------------------------
        | DELETE OLD ITEMS
        |--------------------------------------------------------------------------
        */

            DB::table('testing_items')
                ->where('id_testing_point', $pointId)
                ->delete();


            /*
        |--------------------------------------------------------------------------
        | PREPARE INSERT DATA
        |--------------------------------------------------------------------------
        */

            $rows = [];

            foreach ($request->judul_indonesia as $i => $judul) {

                $rows[] = [
                    'id_testing_point' => $pointId,
                    'nomor' => $i + 1,
                    'judul_indonesia' => $request->judul_indonesia[$i],
                    'judul_inggris' => $request->judul_inggris[$i],
                    'id_testing_parameter' => $request->parameter[$i],
                    'id_testing_unit' => $request->unit[$i],
                    'nilai' => $request->nilai[$i] ?? null,
                    'keterangan' => $request->keterangan[$i] ?? null,
                    'is_aktif' => isset($request->status[$i]) ? 1 : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }


            /*
        |--------------------------------------------------------------------------
        | BULK INSERT (SUPER CEPAT)
        |--------------------------------------------------------------------------
        */

            DB::table('testing_items')->insert($rows);

            DB::commit();


            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disimpan'
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server'
            ], 500);
        }
    }

    public function detail($id)
    {
        $data = DB::table('testing_items')
            ->where('id_testing_item', $id)
            ->first();

        if (!$data) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'id_testing_point' => 'required|integer',
            'id_testing_parameter' => 'required|integer',
            'id_testing_unit' => 'required|integer',
            'nilai' => 'nullable|numeric',
            'nomor' => 'nullable|string|max:50',
            'judul_indonesia' => 'required|string|max:255',
            'judul_inggris' => 'required|string|max:255',
            'is_aktif' => 'required|boolean',
        ]);

        DB::table('testing_items')
            ->where('id_testing_item', $id)
            ->update([
                ...$validated,
                'updated_at' => now(),
            ]);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        DB::table('testing_items')
            ->where('id_testing_item', $id)
            ->delete();

        return response()->json(['success' => true]);
    }


    public function byPoint($id)
    {

        $items = DB::table('testing_items')
            ->select([
                'testing_items.id_testing_item',
                'testing_items.judul_indonesia',
                'testing_items.judul_inggris',
                'testing_parameters.id_testing_parameter as parameter',
                'testing_parameters.kode as kode_parameter',
                'testing_parameters.judul_indonesia as judul_indonesia_parameter',
                'testing_units.id_testing_unit as unit',
                'testing_units.kode as kode_unit',
                'testing_units.judul_indonesia as judul_indonesia_unit',
                'testing_items.keterangan',
                'nilai',
                'testing_items.is_aktif as status'
            ])
            ->leftJoin('testing_parameters', 'testing_items.id_testing_parameter', '=', 'testing_parameters.id_testing_parameter')
            ->leftJoin('testing_units', 'testing_items.id_testing_unit', '=', 'testing_units.id_testing_unit')
            ->where('id_testing_point', $id)
            ->orderBy('nomor')
            ->get();

        return response()->json([
            'data' => $items
        ]);
    }
}
