<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;

class SatuanController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'satuan'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_satuan']; }

    public function index()
    {
        return view('satuan.index', ['title' => 'Satuan']);
    }

    public function data()
    {
        $query = DB::table('satuan')
            ->whereNull('deleted_at')
            ->select(['id_satuan', 'nama', 'is_aktif', 'created_at']);

        return DataTables::of($query)->addIndexColumn()->make(true);
    }

    public function create()
    {
        return view('satuan.create', ['title' => 'Tambah Satuan']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'     => 'required|string|max:50|unique:satuan,nama',
            'is_aktif' => 'nullable|boolean',
        ], [
            'nama.unique' => 'Nama satuan sudah digunakan.',
        ]);

        $id = DB::table('satuan')->insertGetId([
            'nama'       => $validated['nama'],
            'is_aktif'   => $request->boolean('is_aktif', true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $after = DB::table('satuan')->where('id_satuan', $id)->get()->toJson();
        saveAudit('satuan', $id, 'Create', '', $after);

        return response()->json(['success' => true, 'message' => 'Satuan berhasil dibuat', 'id' => $id]);
    }

    public function show($id)
    {
        $data = DB::table('satuan')->where('id_satuan', $id)->whereNull('deleted_at')->first();
        if (!$data) return response()->json(['message' => 'Satuan tidak ditemukan'], 404);
        return response()->json($data);
    }

    public function detail($id)
    {
        return $this->show($id);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama'     => ['required', 'string', 'max:50', Rule::unique('satuan', 'nama')->ignore((int) $id, 'id_satuan')],
            'is_aktif' => 'nullable|boolean',
        ], [
            'nama.unique' => 'Nama satuan sudah digunakan.',
        ]);

        $before = DB::table('satuan')->where('id_satuan', $id)->get()->toJson();

        DB::table('satuan')->where('id_satuan', $id)->update([
            'nama'       => $validated['nama'],
            'is_aktif'   => $request->boolean('is_aktif', true),
            'updated_at' => now(),
        ]);

        $after = DB::table('satuan')->where('id_satuan', $id)->get()->toJson();
        saveAudit('satuan', $id, 'update', $before, $after);

        return response()->json(['success' => true, 'message' => 'Satuan berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $inUse = DB::table('boq')->where('id_satuan', $id)->whereNull('deleted_at')->exists();
        if ($inUse) {
            return response()->json([
                'success' => false,
                'message' => 'Satuan tidak dapat dihapus karena masih digunakan oleh data BOQ.',
            ], 422);
        }

        $before = DB::table('satuan')->where('id_satuan', $id)->get()->toJson();
        DB::table('satuan')->where('id_satuan', $id)->update(['deleted_at' => now()]);
        $after = DB::table('satuan')->where('id_satuan', $id)->get()->toJson();
        saveAudit('satuan', $id, 'delete', $before, $after);

        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('satuan')
            ->whereNull('deleted_at')
            ->where('nama', 'like', "%{$search}%")
            ->orderBy('nama')
            ->limit(20)
            ->get();

        return response()->json(
            $data->map(fn($item) => ['id' => $item->id_satuan, 'text' => $item->nama])
        );
    }

    public function history($id)
    {
        return $this->auditHistory($id);
    }
}
