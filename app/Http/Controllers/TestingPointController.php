<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TestingPointController extends Controller
{
    public function index()
    {
        return view('testing-points.index');
    }

    public function data()
    {
        $query = DB::table('testing_points as tp')
            ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
            ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
            ->leftJoin('testing_kelompok_matriks_samples as tkms', 'tkms.id_testing_kelompok_matriks_sample', '=', 'tms.id_testing_kelompok_matriks_sample')
            ->select([
                'tp.id_testing_point',
                'tp.nama',
                'tp.nomor_halaman as testing_poin_nomor_halaman',
                'tp.deskripsi as testing_poin_deskripsi',
                'tp.keterangan as testing_poin_keterangan',
                'ts.nomor as matrik_standard_nomor',
                'ts.judul as matrik_standard_judul',
                'tkms.judul_indonesia as kelompok_matriks_sample_judul_indonesia',
                'tms.kode as matriks_sample_kode',
                'tms.judul_indonesia as matriks_sample_judul_indonesia',
                'tp.is_aktif',
                'tp.created_at'
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
        return view('testing-points.create');
    }

    public function store(Request $request)
    {
        $table = 'testing_points';
        $validated = $request->validate([
            'id_testing_standard' => 'required|integer',
            'id_testing_matriks_sample' => 'required|integer',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'nomor_halaman' => 'nullable|string|max:50',
            'keterangan' => 'nullable|string',
            'is_aktif' => 'required|boolean',
        ]);

        $upload = uploadAttachment($request->file('attachments'), $table);
        $files = $upload['files'];


        $validated['attachment'] = json_encode($files);

        $id = DB::table('testing_points')->insertGetId([
            ...$validated,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'id' => $id
        ]);
    }

    public function detail($id)
    {
        $data = DB::table('testing_points as a')
            ->leftJoin('testing_standards as b', 'a.id_testing_standard', '=', 'b.id_testing_standard')
            ->leftJoin('testing_matriks_samples as c', 'c.id_testing_matriks_sample', '=', 'a.id_testing_matriks_sample')
            ->where('a.id_testing_point', $id)
            ->select(
                'a.*',
                'b.nomor as standard_nomor',
                'b.judul as standard_judul',
                'c.kode as matrik_sample_kode',
                'c.judul_indonesia as matrik_sample_judul_indonesia',
            )
            ->first();

        if (!$data) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'id_testing_standard' => 'required|integer',
            'id_testing_matriks_sample' => 'required|integer',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'nomor_halaman' => 'nullable|string|max:50',
            'attachment' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string',
            'is_aktif' => 'required|boolean',
        ]);

        // ambil data lama
        $data = DB::table('testing_points')
            ->where('id_testing_point', $id)
            ->get();

        $before = $data->toJson();


        $existing = $request->existing_attachments ?? [];
        $newFiles = [];
        if ($request->hasFile('attachments')) {

            $upload = uploadAttachment(
                $request->file('attachments'),
                'testing_points'
            );

            $newFiles = $upload['files'];
        }

        $attachments = array_merge($existing, $newFiles);
        $validated['attachment'] = json_encode($attachments);


        DB::table('testing_points')
            ->where('id_testing_point', $id)
            ->update([
                ...$validated,
                'updated_at' => now(),
            ]);

        $after = DB::table('testing_points')
            ->where('id_testing_point', $id)
            ->get()->toJson();

        saveAudit(
            'testing_points',
            $id,
            'update',
            $before,
            $after
        );

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        DB::table('testing_points')
            ->where('id_testing_point', $id)
            ->delete();

        return response()->json(['success' => true]);
    }
}
