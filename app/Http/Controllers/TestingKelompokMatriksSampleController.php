<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TestingKelompokMatriksSampleController extends Controller
{
    public function index()
    {
        return view('testing-kelompok-matriks-samples.index', [
            'title' => 'Testing Kelompok Matriks Samples'
        ]);
    }

    public function data()
    {
        $query = DB::table('testing_kelompok_matriks_samples')->select([
            'id_testing_kelompok_matriks_sample',
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
        return view('testing-kelompok-matriks-samples.create', [
            'title' => 'Create Testing Kelompok Matriks Sample'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:50',
            'judul_indonesia' => 'required|string|max:255',
            'judul_inggris' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        $id = DB::table('testing_kelompok_matriks_samples')->insertGetId([
            'kode' => $validated['kode'],
            'judul_indonesia' => $validated['judul_indonesia'],
            'judul_inggris' => $validated['judul_inggris'],
            'keterangan' => $validated['keterangan'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil dibuat',
            'id' => $id
        ]);
    }

    public function show($id)
    {
        $data = DB::table('testing_kelompok_matriks_samples')
            ->where('id_testing_kelompok_matriks_sample', $id)
            ->first();

        if (!$data) {
            return response()->json([
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json($data);
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

            $before = DB::table('testing_kelompok_matriks_samples')
                ->where('id_testing_kelompok_matriks_sample', $id)
                ->get()
                ->toJson();


            DB::table('testing_kelompok_matriks_samples')
                ->where('id_testing_kelompok_matriks_sample', $id)
                ->update([
                    'kode' => $validated['kode'],
                    'judul_indonesia' => $validated['judul_indonesia'],
                    'judul_inggris' => $validated['judul_inggris'],
                    'keterangan' => $validated['keterangan'] ?? null,
                    'updated_at' => now(),
                ]);

            $after = DB::table('testing_kelompok_matriks_samples')
                ->where('id_testing_kelompok_matriks_sample', $id)
                ->get()
                ->toJson();

            saveAudit(
                'testing_kelompok_matriks_samples',
                $id,
                'update',
                $before,
                $after
            );

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diperbarui'
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
        DB::table('testing_kelompok_matriks_samples')
            ->where('id_testing_kelompok_matriks_sample', $id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus'
        ]);
    }

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('testing_kelompok_matriks_samples')
            ->where('kode', 'like', "%{$search}%")
            ->orWhere('judul_indonesia', 'like', "%{$search}%")
            ->limit(10)
            ->get();

        return response()->json(
            $data->map(function ($item) {
                return [
                    'id' => $item->id_testing_kelompok_matriks_sample,
                    'text' => $item->kode . ' - ' . $item->judul_indonesia,
                ];
            })
        );
    }

    public function detail($id)
    {
        $data = DB::table('testing_kelompok_matriks_samples')
            ->where('id_testing_kelompok_matriks_sample', $id)
            ->first();

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json($data);
    }
}
