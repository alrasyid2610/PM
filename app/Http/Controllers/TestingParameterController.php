<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasAuditHistory;



class TestingParameterController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string
    {
        return 'testing_parameters'; // ← nama table di audit_logs
    }

    public function index()
    {
        return view('testing-parameters.index', [
            'title' => 'Testing Parameters'
        ]);
    }

    public function data()
    {
        $query = DB::table('testing_parameters')->select([
            'id_testing_parameter',
            'kelompok',
            'kode',
            'judul_indonesia',
            'judul_inggris',
            'rumus_empiris',
            'judul_iupac',
            'referensi',
            'keterangan',
            'attachment',
            'created_at'
        ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->make(true);
    }

    public function create()
    {
        return view('testing-parameters.create', [
            'title' => 'Create Testing Parameter'
        ]);
    }

    public function store(Request $request)
    {
        $table = 'testing_parameters';

        $validated = $request->validate([
            'kelompok' => 'nullable|string|max:255',
            'kode' => 'required|string|max:100',
            'judul_indonesia' => 'required|string|max:255',
            'judul_inggris' => 'nullable|string|max:255',
            'rumus_empiris' => 'nullable|string',
            'judul_iupac' => 'nullable|string|max:255',
            'referensi' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        $upload = uploadAttachment($request->file('attachments'), $table);
        $files = $upload['files'];


        $id = DB::table('testing_parameters')->insertGetId([
            'kelompok' => $validated['kelompok'] ?? null,
            'kode' => $validated['kode'],
            'judul_indonesia' => $validated['judul_indonesia'],
            'judul_inggris' => $validated['judul_inggris'] ?? null,
            'rumus_empiris' => $validated['rumus_empiris'] ?? null,
            'judul_iupac' => $validated['judul_iupac'] ?? null,
            'referensi' => $validated['referensi'] ?? null,
            'keterangan' => $validated['keterangan'] ?? null,
            'attachment' => json_encode($files),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Testing parameter berhasil dibuat',
            'id' => $id
        ]);
    }

    public function show($id)
    {
        $item = DB::table('testing_parameters')->where('id_testing_parameter', $id)->first();
        if (!$item) {
            return response()->json(['message' => 'Testing parameter tidak ditemukan'], 404);
        }
        return response()->json($item);
    }

    public function edit($id)
    {
        $item = DB::table('testing_parameters')->where('id_testing_parameter', $id)->first();
        if (!$item) abort(404);
        return view('testing-parameters.edit', compact('item'));
    }

    public function update(Request $request, $id)
    {

        $validated = $request->validate([
            'kelompok' => 'nullable|string|max:255',
            'kode' => 'required|string|max:100',
            'judul_indonesia' => 'required|string|max:255',
            'judul_inggris' => 'nullable|string|max:255',
            'rumus_empiris' => 'nullable|string',
            'judul_iupac' => 'nullable|string|max:255',
            'referensi' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string'
        ]);



        try {

            // ambil data lama
            $data = DB::table('testing_parameters')
                ->where('id_testing_parameter', $id)
                ->first();

            $existing = $request->existing_attachments ?? [];
            $newFiles = [];
            if ($request->hasFile('attachments')) {

                $upload = uploadAttachment(
                    $request->file('attachments'),
                    'testing_parameters'
                );

                $newFiles = $upload['files'];
            }

            $attachments = array_merge($existing, $newFiles);

            $before = DB::table('testing_parameters')
                ->where('id_testing_parameter', $id)
                ->get()->toJson();


            DB::table('testing_parameters')
                ->where('id_testing_parameter', $id)
                ->update([
                    'kelompok' => $validated['kelompok'] ?? null,
                    'kode' => $validated['kode'],
                    'judul_indonesia' => $validated['judul_indonesia'],
                    'judul_inggris' => $validated['judul_inggris'] ?? null,
                    'rumus_empiris' => $validated['rumus_empiris'] ?? null,
                    'judul_iupac' => $validated['judul_iupac'] ?? null,
                    'referensi' => $validated['referensi'] ?? null,
                    'keterangan' => $validated['keterangan'] ?? null,

                    'attachment' => json_encode($attachments),

                    'updated_at' => now(),
                ]);

            $after = DB::table('testing_parameters')
                ->where('id_testing_parameter', $id)
                ->get()->toJson();

            saveAudit(
                'testing_parameters',
                $id,
                'update',
                $before,
                $after
            );

            return response()->json([
                'success' => true,
                'message' => 'Testing parameter berhasil diperbarui'
            ]);
        } catch (\Throwable $e) {
            dd($e);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::table('testing_parameters')->where('id_testing_parameter', $id)->delete();
        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus'
        ]);
    }

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('testing_parameters')
            ->where('kode', 'like', "%{$search}%")
            ->orWhere('judul_indonesia', 'like', "%{$search}%")
            ->limit(10)
            ->get();

        return response()->json(
            $data->map(function ($item) {
                return [
                    'id' => $item->id_testing_parameter,
                    'text' => $item->kode . ' - ' . $item->judul_indonesia,
                ];
            })
        );
    }

    public function select2byid(Request $request)
    {
        // dd('kocak', $request->all());

        $search = $request->q;

        $data = DB::table('testing_parameters')
            ->where('id_testing_parameter', $search)
            ->limit(10)
            ->get();

        return response()->json(
            $data->map(function ($item) {
                return [
                    'id' => $item->id_testing_parameter,
                    'text' => $item->kode . ' - ' . $item->judul_indonesia,
                ];
            })
        );
    }


    public function deleteAttachment(Request $request)
    {

        $id = $request->id;
        $file = $request->file;

        $data = DB::table('testing_parameters')
            ->where('id_testing_parameter', $id)
            ->first();

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ]);
        }

        $attachments = json_decode($data->attachment, true) ?? [];

        // hapus file dari array
        $attachments = array_filter($attachments, function ($item) use ($file) {
            return $item != $file;
        });

        // hapus file dari storage

        Storage::disk('public')->delete($file);

        // update database
        DB::table('testing_parameters')
            ->where('id_testing_parameter', $id)
            ->update([
                'attachment' => json_encode(array_values($attachments)),
                'updated_at' => now()
            ]);

        return response()->json([
            'success' => true
        ]);
    }

    protected function auditExcludeFields(): array
    {
        return ['updated_at', 'created_at']; // ← field yang di-skip
    }
}
