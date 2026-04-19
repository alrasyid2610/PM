<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistoryWithLines;

class TestingPointController extends Controller
{
    use HasAuditHistoryWithLines;

    protected function auditTable(): string { return 'testing_points'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_testing_point', '_lines']; }
    protected function auditLinesTable(): string { return 'testing_items'; }
    protected function auditLinesForeignKey(): string { return 'id_testing_point'; }
    protected function auditLinesPrimaryKey(): string { return 'id_testing_item'; }
    protected function auditLinesExcludeFields(): array { return ['updated_at', 'created_at', 'id_testing_item', 'id_testing_point']; }

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
                DB::raw(
                    "CONCAT(tms.kode, '-', ts.nomor, '-', tp.nama) as Kode"
                ),
                // 'tp.nama',
                'tp.nomor_halaman as testing_poin_nomor_halaman',
                'tp.deskripsi as testing_poin_deskripsi',
                // 'tp.keterangan as testing_poin_keterangan',
                // 'ts.nomor as matrik_standard_nomor',
                // 'ts.judul as matrik_standard_judul',
                // 'tkms.judul_indonesia as kelompok_matriks_sample_judul_indonesia',
                // 'tms.kode as matriks_sample_kode',
                // 'tms.judul_indonesia as matriks_sample_judul_indonesia',
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

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('testing_points')
            ->where('nama', 'like', "%{$search}%")
            ->limit(10)
            ->get();

        return response()->json(
            $data->map(function ($item) {
                return [
                    'id' => $item->id_testing_standard,
                    'text' => $item->nomor . ' - ' . $item->judul,
                ];
            })
        );
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


        try {
            $validated = $request->validate([
                'id_testing_standard' => 'required|integer',
                'id_testing_matriks_sample' => 'required|integer',
                'nama' => 'required|string|max:255',
                'deskripsi' => 'nullable|string',
                'nomor_halaman' => 'nullable|string|max:50',
                'attachment' => 'nullable|string|max:255',
                'keterangan_point' => 'nullable|string',
                'is_aktif' => 'required|boolean',
            ]);

            // dd($request->all());
        } catch (\Throwable $th) {
            dd($th);
        }

        // Capture BEFORE state (point + lines)
        $beforePoint = (array) DB::table('testing_points')->where('id_testing_point', $id)->first();
        $beforeLines = DB::table('testing_items')->where('id_testing_point', $id)->get()->map(fn($r) => (array)$r)->toArray();
        $beforePoint['_lines'] = $beforeLines;

        DB::beginTransaction();

        try {

            $ids            = $request->id_testing_item ?? [];
            $judul_indonesia = $request->judul_indonesia ?? [];
            $judul_inggris  = $request->judul_inggris ?? [];
            $parameter      = $request->parameter ?? [];
            $unit           = $request->unit ?? [];
            $nilai          = $request->nilai ?? [];
            $keterangan     = $request->keterangan ?? [];
            $status         = $request->status ?? [];
            $nomor          = $request->nomor ?? [];

            $existingIds = DB::table('testing_items')
                ->where('id_testing_point', $id)
                ->pluck('id_testing_item')
                ->toArray();

            $currentIds = [];

            foreach ($judul_indonesia as $i => $val) {

                $itemId = $ids[$i] ?? null;

                $dataItem = [
                    'id_testing_point'   => $id,
                    'nomor'              => $nomor[$i] ?? ($i + 1),
                    'judul_indonesia'    => $judul_indonesia[$i] ?? null,
                    'judul_inggris'      => $judul_inggris[$i] ?? null,
                    'id_testing_parameter' => $parameter[$i] ?? null,
                    'id_testing_unit'    => $unit[$i] ?? null,
                    'nilai'              => $nilai[$i] ?? null,
                    'keterangan'         => $keterangan[$i] ?? null,
                    'is_aktif'           => isset($status[$i]) ? $status[$i] : 0,
                    'updated_at'         => now(),
                ];

                if ($itemId) {
                    DB::table('testing_items')->where('id_testing_item', $itemId)->update($dataItem);
                    $currentIds[] = $itemId;
                } else {
                    $newId = DB::table('testing_items')->insertGetId([...$dataItem, 'created_at' => now()]);
                    $currentIds[] = $newId;
                }
            }

            $idsToDelete = array_diff($existingIds, $currentIds);
            if (!empty($idsToDelete)) {
                DB::table('testing_items')->whereIn('id_testing_item', $idsToDelete)->delete();
            }

            $existing = $request->existing_attachments ?? [];
            $newFiles = [];
            if ($request->hasFile('attachments')) {
                $upload   = uploadAttachment($request->file('attachments'), 'testing_points');
                $newFiles = $upload['files'];
            }

            $attachments = array_merge($existing, $newFiles);
            $validated['attachment'] = json_encode($attachments);

            $validated['keterangan'] = $validated['keterangan_point'] ?? null;
            unset($validated['keterangan_point']);

            DB::table('testing_points')
                ->where('id_testing_point', $id)
                ->update([...$validated, 'updated_at' => now()]);

            // Capture AFTER state (point + lines)
            $afterPoint = (array) DB::table('testing_points')->where('id_testing_point', $id)->first();
            $afterLines = DB::table('testing_items')->where('id_testing_point', $id)->get()->map(fn($r) => (array)$r)->toArray();
            $afterPoint['_lines'] = $afterLines;

            saveAudit('testing_points', $id, 'update', json_encode($beforePoint), json_encode($afterPoint));

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            throw $th;
        }

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        DB::table('testing_points')
            ->where('id_testing_point', $id)
            ->delete();

        return response()->json(['success' => true]);
    }


    public function deleteAttachment(Request $request)
    {

        $id = $request->id;
        $file = $request->file;

        $data = DB::table('testing_points')
            ->where('id_testing_point', $id)
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
        DB::table('testing_points')
            ->where('id_testing_point', $id)
            ->update([
                'attachment' => json_encode(array_values($attachments)),
                'updated_at' => now()
            ]);

        return response()->json([
            'success' => true
        ]);
    }
}
