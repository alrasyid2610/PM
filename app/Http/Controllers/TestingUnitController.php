<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TestingUnitController extends Controller
{
    public function index()
    {
        return view('testing-units.index', [
            'title' => 'Testing Units'
        ]);
    }

    public function data()
    {
        $query = DB::table('testing_units')->select([
            'id_testing_unit',
            'kode',
            'judul_indonesia',
            'judul_inggris',
            'keterangan',
            'created_at'
        ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->make(true);
    }

    public function create()
    {
        return view('testing-units.create', [
            'title' => 'Create Testing Unit'
        ]);
    }

    public function store(Request $request)
    {
        // Validasi dan simpan data baru
        $validated = $request->validate([
            'kode' => 'required|string|max:50',
            'judul_indonesia' => 'required|string|max:255',
            'judul_inggris' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ]);
        $id = DB::table('testing_units')->insertGetId([
            'kode' => $validated['kode'],
            'judul_indonesia' => $validated['judul_indonesia'],
            'judul_inggris' => $validated['judul_inggris'],
            'keterangan' => $validated['keterangan'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Testing unit berhasil dibuat',
            'id' => $id
        ]);
    }

    public function show($id)
    {
        $testingUnit = DB::table('testing_units')->where('id_testing_unit', $id)->first();
        if (!$testingUnit) {
            return response()->json(['message' => 'Testing unit tidak ditemukan'], 404);
        }

        return response()->json($testingUnit);
    }

    public function edit($id)
    {
        $testingUnit = DB::table('testing_units')->where('id_testing_unit', $id)->first();
        if (!$testingUnit) abort(404);
        return view('testing-units.edit', compact('testingUnit'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:50',
            'judul_indonesia' => 'required|string|max:255',
            'judul_inggris' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ]);
        try {
            DB::table('testing_units')->where('id_testing_unit', $id)->update([
                'kode' => $validated['kode'],
                'judul_indonesia' => $validated['judul_indonesia'],
                'judul_inggris' => $validated['judul_inggris'],
                'keterangan' => $validated['keterangan'] ?? null,
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Testing unit berhasil diperbarui'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::table('testing_units')->where('id_testing_unit', $id)->delete();
        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus'
        ]);
    }

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('testing_units')
            ->where('kode', 'like', "%{$search}%")
            ->orWhere('judul_indonesia', 'like', "%{$search}%")
            ->limit(10)
            ->get();

        return response()->json(
            $data->map(function ($item) {
                return [
                    'id' => $item->id_testing_unit,
                    'text' => $item->kode . ' - ' . $item->judul_indonesia,
                ];
            })
        );
    }

    public function select2byid(Request $request)
    {
        $search = $request->q;

        $data = DB::table('testing_units')
            ->where('id_testing_unit', $search)
            ->limit(10)
            ->get();

        return response()->json(
            $data->map(function ($item) {
                return [
                    'id' => $item->id_testing_unit,
                    'text' => $item->kode . ' - ' . $item->judul_indonesia,
                ];
            })
        );
    }

    public function detail($id)
    {
        $data = DB::table('testing_units')
            ->where('id_testing_unit', $id)
            ->first();

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Testing Unit tidak ditemukan'
            ], 404);
        }

        return response()->json($data);
    }
}
