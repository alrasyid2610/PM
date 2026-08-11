<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;

class KategoriBisnisController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'kategori_bisnis'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_kategori_bisnis']; }

    public function index()
    {
        return view('kategori-bisnis.index', ['title' => 'Kategori Bisnis']);
    }

    public function data()
    {
        $query = DB::table('kategori_bisnis')
            ->whereNull('deleted_at')
            ->select(['id_kategori_bisnis', 'nama', 'is_aktif', 'created_at']);

        return DataTables::of($query)->addIndexColumn()->make(true);
    }

    public function create()
    {
        return view('kategori-bisnis.create', ['title' => 'Tambah Kategori Bisnis']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'     => 'required|string|max:150|unique:kategori_bisnis,nama',
            'is_aktif' => 'nullable|boolean',
        ], [
            'nama.unique' => 'Nama kategori bisnis sudah digunakan.',
        ]);

        $id = DB::table('kategori_bisnis')->insertGetId([
            'nama'       => $validated['nama'],
            'is_aktif'   => $request->boolean('is_aktif', true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $after = DB::table('kategori_bisnis')->where('id_kategori_bisnis', $id)->get()->toJson();
        saveAudit('kategori_bisnis', $id, 'Create', '', $after);

        return response()->json(['success' => true, 'message' => 'Kategori Bisnis berhasil dibuat', 'id' => $id]);
    }

    public function show($id)
    {
        $data = DB::table('kategori_bisnis')->where('id_kategori_bisnis', $id)->whereNull('deleted_at')->first();
        if (!$data) return response()->json(['message' => 'Kategori Bisnis tidak ditemukan'], 404);
        return response()->json($data);
    }

    public function detail($id)
    {
        return $this->show($id);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama'     => ['required', 'string', 'max:150', Rule::unique('kategori_bisnis', 'nama')->ignore((int) $id, 'id_kategori_bisnis')],
            'is_aktif' => 'nullable|boolean',
        ], [
            'nama.unique' => 'Nama kategori bisnis sudah digunakan.',
        ]);

        $before = DB::table('kategori_bisnis')->where('id_kategori_bisnis', $id)->get()->toJson();

        DB::table('kategori_bisnis')->where('id_kategori_bisnis', $id)->update([
            'nama'       => $validated['nama'],
            'is_aktif'   => $request->boolean('is_aktif', true),
            'updated_at' => now(),
        ]);

        $after = DB::table('kategori_bisnis')->where('id_kategori_bisnis', $id)->get()->toJson();
        saveAudit('kategori_bisnis', $id, 'update', $before, $after);

        return response()->json(['success' => true, 'message' => 'Kategori Bisnis berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $usedByBr = DB::table('business_relations')->where('id_kategori_bisnis', $id)->exists();
        if ($usedByBr) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori Bisnis tidak dapat dihapus karena masih digunakan oleh data Business Relation.',
            ], 422);
        }

        $hasSub = DB::table('sub_kategori_bisnis')->where('id_kategori_bisnis', $id)->whereNull('deleted_at')->exists();
        if ($hasSub) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori Bisnis tidak dapat dihapus karena masih memiliki Sub Kategori Bisnis.',
            ], 422);
        }

        $before = DB::table('kategori_bisnis')->where('id_kategori_bisnis', $id)->get()->toJson();
        DB::table('kategori_bisnis')->where('id_kategori_bisnis', $id)->update(['deleted_at' => now()]);
        $after = DB::table('kategori_bisnis')->where('id_kategori_bisnis', $id)->get()->toJson();
        saveAudit('kategori_bisnis', $id, 'delete', $before, $after);

        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('kategori_bisnis')
            ->whereNull('deleted_at')
            ->where('nama', 'like', "%{$search}%")
            ->orderBy('nama')
            ->limit(20)
            ->get();

        return response()->json(
            $data->map(fn($item) => ['id' => $item->id_kategori_bisnis, 'text' => $item->nama])
        );
    }

    public function history($id)
    {
        return $this->auditHistory($id);
    }
}
