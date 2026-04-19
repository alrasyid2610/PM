<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;

class TestingUnitController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string
    {
        return 'testing_units'; // ← nama table di audit_logs
    }

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

        $after = DB::table('testing_units')->where('id_testing_unit', $id)
            ->get()->toJson();

        saveAudit(
            'testing_units',
            $id,
            'Create',
            '',
            $after
        );



        return response()->json([
            'success' => true,
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

            $before = DB::table('testing_units')
                ->where('id_testing_unit', $id)
                ->get()->toJson();


            DB::table('testing_units')->where('id_testing_unit', $id)->update([
                'kode' => $validated['kode'],
                'judul_indonesia' => $validated['judul_indonesia'],
                'judul_inggris' => $validated['judul_inggris'],
                'keterangan' => $validated['keterangan'] ?? null,
                'updated_at' => now(),
            ]);

            $after = DB::table('testing_units')->where('id_testing_unit', $id)
                ->get()->toJson();

            saveAudit(
                'testing_units',
                $id,
                'update',
                $before,
                $after
            );

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
                'success' => false,
                'message' => 'Testing Unit tidak ditemukan'
            ], 404);
        }

        return response()->json($data);
    }

    protected function auditExcludeFields(): array
    {
        return ['updated_at', 'created_at', 'id_testing_unit']; // ← field yang di-skip
    }


    // public function history($id)
    // {
    //     $logs = DB::table('audit_logs as a')
    //         ->leftJoin('users as u', 'u.id', '=', 'a.created_by')
    //         ->where('a.nama_table', 'testing_units')
    //         ->where('a.row_id', $id)
    //         ->select([
    //             'a.id',
    //             'a.action',
    //             'a.old_value',
    //             'a.new_value',
    //             'a.created_at',
    //             'u.name as created_by_name',
    //         ])
    //         ->orderByDesc('a.created_at')
    //         ->get()
    //         ->map(function ($log) {

    //             $old = $this->parseAuditValue($log->old_value);
    //             $new = $this->parseAuditValue($log->new_value);

    //             $changes = [];

    //             if (is_array($old) && is_array($new)) {

    //                 foreach ($new as $key => $newVal) {
    //                     $excludeFields = ['updated_at', 'created_at', 'id_testing_unit'];
    //                     if (in_array($key, $excludeFields)) continue;

    //                     $oldVal = $old[$key] ?? null;
    //                     if ((string)$oldVal !== (string)$newVal) {
    //                         $changes[] = [
    //                             'field'     => $key,
    //                             'old_value' => $oldVal,
    //                             'new_value' => $newVal,
    //                         ];
    //                     }
    //                 }
    //             }

    //             return [
    //                 'id'              => $log->id,
    //                 'action'          => $log->action,
    //                 'changes'         => $changes,
    //                 'total_changes'   => count($changes),
    //                 'created_by_name' => $log->created_by_name ?? 'System',
    //                 'created_at'      => $log->created_at,
    //             ];
    //         });

    //     return response()->json($logs);
    // }

    // // ← tambah helper method ini
    // private function parseAuditValue($value)
    // {
    //     if (empty($value)) return [];

    //     // Decode pertama
    //     $decoded = json_decode($value, true);

    //     // Kalau masih string (double encoded), decode lagi
    //     if (is_string($decoded)) {
    //         $decoded = json_decode($decoded, true);
    //     }

    //     // Kalau array dan ada index 0 (wrapped array), ambil index 0
    //     if (is_array($decoded) && isset($decoded[0])) {
    //         return is_array($decoded[0]) ? $decoded[0] : $decoded;
    //     }

    //     // Kalau sudah array langsung return
    //     if (is_array($decoded)) {
    //         return $decoded;
    //     }

    //     return [];
    // }
}
