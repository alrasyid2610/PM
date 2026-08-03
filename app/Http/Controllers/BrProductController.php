<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasAuditHistory;

class BrProductController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'br_products'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_product']; }

    public function listByBr(int $id_br)
    {
        $rows = DB::table('br_products')
            ->where('id_br', $id_br)
            ->whereNull('deleted_at')
            ->orderBy('nama_product')
            ->get(['id_product', 'nama_product', 'seri_product', 'keterangan', 'is_aktif']);

        return response()->json(['data' => $rows]);
    }

    public function show(int $id)
    {
        $row = DB::table('br_products')->where('id_product', $id)->first();
        if (!$row) return response()->json(['message' => 'Data tidak ditemukan'], 404);
        return response()->json($row);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_br'        => 'required|integer',
            'nama_product' => 'required|string|max:255',
            'seri_product' => 'nullable|string|max:100',
            'keterangan'   => 'nullable|string',
            'is_aktif'     => 'required|in:0,1',
        ]);

        $id = DB::table('br_products')->insertGetId([
            'id_br'        => $validated['id_br'],
            'nama_product' => $validated['nama_product'],
            'seri_product' => $validated['seri_product'] ?: null,
            'keterangan'   => $validated['keterangan'] ?: null,
            'is_aktif'     => $validated['is_aktif'],
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $after = DB::table('br_products')->where('id_product', $id)->get()->toJson();
        saveAudit('br_products', $id, 'Create', '[]', $after);

        return response()->json(['success' => true, 'id_product' => $id]);
    }

    public function update(Request $request, int $id)
    {
        $row = DB::table('br_products')->where('id_product', $id)->first();
        if (!$row) return response()->json(['message' => 'Data tidak ditemukan'], 404);

        $validated = $request->validate([
            'nama_product' => 'required|string|max:255',
            'seri_product' => 'nullable|string|max:100',
            'keterangan'   => 'nullable|string',
            'is_aktif'     => 'required|in:0,1',
        ]);

        $before = DB::table('br_products')->where('id_product', $id)->get()->toJson();

        DB::table('br_products')->where('id_product', $id)->update([
            'nama_product' => $validated['nama_product'],
            'seri_product' => $validated['seri_product'] ?: null,
            'keterangan'   => $validated['keterangan'] ?: null,
            'is_aktif'     => $validated['is_aktif'],
            'updated_at'   => now(),
        ]);

        $after = DB::table('br_products')->where('id_product', $id)->get()->toJson();
        saveAudit('br_products', $id, 'Update', $before, $after);

        return response()->json(['success' => true]);
    }

    public function destroy(int $id)
    {
        $row = DB::table('br_products')->where('id_product', $id)->first();
        if (!$row) return response()->json(['message' => 'Data tidak ditemukan'], 404);

        $before = DB::table('br_products')->where('id_product', $id)->get()->toJson();

        DB::table('br_products')->where('id_product', $id)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        saveAudit('br_products', $id, 'Delete', $before, '[]');

        return response()->json(['success' => true]);
    }
}
