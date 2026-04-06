<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TestingMatriksSampleController extends Controller
{
    public function index()
    {
        return view('testing-matriks-samples.index', [
            'title' => 'Testing Matriks Samples'
        ]);
    }

    public function data()
    {
        $query = DB::table('testing_matriks_samples as tms')
            ->leftJoin(
                'testing_kelompok_matriks_samples as tkms',
                'tms.id_testing_kelompok_matriks_sample',
                '=',
                'tkms.id_testing_kelompok_matriks_sample'
            )
            ->select([
                'tms.id_testing_matriks_sample',
                'tms.kode',
                'tms.judul_indonesia',
                'tms.judul_inggris',
                'tkms.kode as kelompok_kode',
                'tkms.judul_indonesia as kelompok_matrik_judul_indonesia',
                'tms.created_at'
            ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->make(true);
    }

    public function create()
    {
        return view('testing-matriks-samples.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_testing_kelompok_matriks_sample' => 'required|integer',
            'kode' => 'required|string|max:50',
            'judul_indonesia' => 'required|string|max:255',
            'judul_inggris' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        $id = DB::table('testing_matriks_samples')->insertGetId([
            'id_testing_kelompok_matriks_sample' => $validated['id_testing_kelompok_matriks_sample'],
            'kode' => $validated['kode'],
            'judul_indonesia' => $validated['judul_indonesia'],
            'judul_inggris' => $validated['judul_inggris'],
            'keterangan' => $validated['keterangan'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'id' => $id
        ]);
    }

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('testing_matriks_samples')
            ->where('judul_indonesia', 'like', "%{$search}%")
            ->limit(10)
            ->get();

        return response()->json(
            $data->map(function ($item) {
                return [
                    'id' => $item->id_testing_matriks_sample,
                    'text' => $item->kode . ' - ' . $item->judul_indonesia,
                ];
            })
        );
    }

    public function detail($id)
    {
        $data = DB::table('testing_matriks_samples as a')
            ->leftJoin('testing_kelompok_matriks_samples as b', 'b.id_testing_kelompok_matriks_sample', '=', 'a.id_testing_kelompok_matriks_sample')
            ->select(
                'a.*',
                'b.judul_indonesia as kelompok_matriks_judul_indonesia'
            )
            ->where('a.id_testing_matriks_sample', $id)
            ->first();

        if (!$data) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'id_testing_kelompok_matriks_sample' => 'required|integer',
            'kode' => 'required|string|max:50',
            'judul_indonesia' => 'required|string|max:255',
            'judul_inggris' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ]);


        $before = DB::table('testing_matriks_samples')
            ->where('id_testing_matriks_sample', $id)
            ->get()
            ->toJson();

        DB::table('testing_matriks_samples')
            ->where('id_testing_matriks_sample', $id)
            ->update([
                'id_testing_kelompok_matriks_sample' => $validated['id_testing_kelompok_matriks_sample'],
                'kode' => $validated['kode'],
                'judul_indonesia' => $validated['judul_indonesia'],
                'judul_inggris' => $validated['judul_inggris'],
                'keterangan' => $validated['keterangan'] ?? null,
                'updated_at' => now(),
            ]);

        $after = DB::table('testing_matriks_samples')
            ->where('id_testing_matriks_sample', $id)
            ->get()
            ->toJson();

        saveAudit(
            'testing_matriks_samples',
            $id,
            'update',
            $before,
            $after
        );



        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        DB::table('testing_matriks_samples')
            ->where('id_testing_matriks_sample', $id)
            ->delete();

        return response()->json(['success' => true]);
    }
}
