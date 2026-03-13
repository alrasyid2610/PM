<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TestingStandardController extends Controller
{
    public function index()
    {
        return view('testing-standards.index', [
            'title' => 'Testing Standards'
        ]);
    }

    public function data()
    {
        $query = DB::table('testing_standards')->select([
            'id_testing_standard',
            'nomor',
            'judul',
            'is_aktif',
            'attachment',
            'created_at'
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
        return view('testing-standards.create', [
            'title' => 'Create Testing Standard'
        ]);
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'nomor' => 'required|string|max:100',
            'judul' => 'required|string|max:255',
            'is_aktif' => 'required|boolean',
            'attachments.*' => 'nullable|file|max:5120'
        ]);

        $attachments = [];

        if ($request->hasFile('attachments')) {

            foreach ($request->file('attachments') as $file) {

                $path = $file->store('testing-standards', 'public');

                $attachments[] = $path;
            }
        }

        $id = DB::table('testing_standards')->insertGetId([

            'nomor' => $request->nomor,
            'judul' => $request->judul,
            'is_aktif' => $request->is_aktif,
            'attachment' => json_encode($attachments),
            'created_at' => now(),
            'updated_at' => now()

        ]);

        return response()->json([
            'status' => 'success',
            'id' => $id
        ]);
    }
    public function detail($id)
    {
        $data = DB::table('testing_standards')
            ->where('id_testing_standard', $id)
            ->first();

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nomor' => 'required|string|max:50',
            'judul' => 'required|string|max:255',
            'is_aktif' => 'required|boolean',
            'attachment' => 'nullable|string|max:255',
        ]);

        DB::table('testing_standards')
            ->where('id_testing_standard', $id)
            ->update([
                'nomor' => $validated['nomor'],
                'judul' => $validated['judul'],
                'is_aktif' => $validated['is_aktif'],
                'attachment' => $validated['attachment'] ?? null,
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diperbarui'
        ]);
    }

    public function destroy($id)
    {
        DB::table('testing_standards')
            ->where('id_testing_standard', $id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus'
        ]);
    }
}
