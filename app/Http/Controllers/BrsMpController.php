<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasAuditHistory;

class BrsMpController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'brs_mp'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_mp']; }

    public function listBySite(int $id_site)
    {
        $rows = DB::table('brs_mp')
            ->where('id_site', $id_site)
            ->whereNull('deleted_at')
            ->orderBy('nama')
            ->get(['id_mp', 'no_karyawan', 'nama', 'is_aktif']);

        return response()->json(['data' => $rows]);
    }

    public function show(int $id)
    {
        $row = DB::table('brs_mp')->where('id_mp', $id)->first();
        if (!$row) return response()->json(['message' => 'Data tidak ditemukan'], 404);
        return response()->json($row);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_site'     => 'required|integer',
            'no_karyawan' => 'required|string|max:100',
            'nama'        => 'required|string|max:255',
            'is_aktif'    => 'required|in:0,1',
        ]);

        $id = DB::table('brs_mp')->insertGetId([
            'id_site'     => $validated['id_site'],
            'no_karyawan' => $validated['no_karyawan'],
            'nama'        => $validated['nama'],
            'is_aktif'    => $validated['is_aktif'],
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $after = DB::table('brs_mp')->where('id_mp', $id)->get()->toJson();
        saveAudit('brs_mp', $id, 'Create', '[]', $after);

        return response()->json(['success' => true, 'id_mp' => $id]);
    }

    public function update(Request $request, int $id)
    {
        $row = DB::table('brs_mp')->where('id_mp', $id)->first();
        if (!$row) return response()->json(['message' => 'Data tidak ditemukan'], 404);

        $validated = $request->validate([
            'no_karyawan' => 'required|string|max:100',
            'nama'        => 'required|string|max:255',
            'is_aktif'    => 'required|in:0,1',
        ]);

        $before = DB::table('brs_mp')->where('id_mp', $id)->get()->toJson();

        DB::table('brs_mp')->where('id_mp', $id)->update([
            'no_karyawan' => $validated['no_karyawan'],
            'nama'        => $validated['nama'],
            'is_aktif'    => $validated['is_aktif'],
            'updated_at'  => now(),
        ]);

        $after = DB::table('brs_mp')->where('id_mp', $id)->get()->toJson();
        saveAudit('brs_mp', $id, 'Update', $before, $after);

        return response()->json(['success' => true]);
    }

    public function destroy(int $id)
    {
        $row = DB::table('brs_mp')->where('id_mp', $id)->first();
        if (!$row) return response()->json(['message' => 'Data tidak ditemukan'], 404);

        $before = DB::table('brs_mp')->where('id_mp', $id)->get()->toJson();

        DB::table('brs_mp')->where('id_mp', $id)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        saveAudit('brs_mp', $id, 'Delete', $before, '[]');

        return response()->json(['success' => true]);
    }
}
