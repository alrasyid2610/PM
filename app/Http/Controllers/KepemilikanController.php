<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;

class KepemilikanController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'kepemilikan'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_kepemilikan']; }

    public function index()
    {
        return view('kepemilikan.index', ['title' => 'Kepemilikan']);
    }

    public function data()
    {
        $query = DB::table('kepemilikan')
            ->whereNull('deleted_at')
            ->select(['id_kepemilikan', 'nama', 'is_aktif', 'created_at']);

        return DataTables::of($query)->addIndexColumn()->make(true);
    }

    public function create()
    {
        return view('kepemilikan.create', ['title' => 'Tambah Kepemilikan']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'     => 'required|string|max:100|unique:kepemilikan,nama',
            'is_aktif' => 'nullable|boolean',
        ], [
            'nama.unique' => 'Nama kepemilikan sudah digunakan.',
        ]);

        $id = DB::table('kepemilikan')->insertGetId([
            'nama'       => $validated['nama'],
            'is_aktif'   => $request->boolean('is_aktif', true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $after = DB::table('kepemilikan')->where('id_kepemilikan', $id)->get()->toJson();
        saveAudit('kepemilikan', $id, 'Create', '', $after);

        return response()->json(['success' => true, 'message' => 'Kepemilikan berhasil dibuat', 'id' => $id]);
    }

    public function show($id)
    {
        $data = DB::table('kepemilikan')->where('id_kepemilikan', $id)->whereNull('deleted_at')->first();
        if (!$data) return response()->json(['message' => 'Kepemilikan tidak ditemukan'], 404);
        return response()->json($data);
    }

    public function detail($id)
    {
        return $this->show($id);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama'     => ['required', 'string', 'max:100', Rule::unique('kepemilikan', 'nama')->ignore((int) $id, 'id_kepemilikan')],
            'is_aktif' => 'nullable|boolean',
        ], [
            'nama.unique' => 'Nama kepemilikan sudah digunakan.',
        ]);

        $before = DB::table('kepemilikan')->where('id_kepemilikan', $id)->get()->toJson();

        DB::table('kepemilikan')->where('id_kepemilikan', $id)->update([
            'nama'       => $validated['nama'],
            'is_aktif'   => $request->boolean('is_aktif', true),
            'updated_at' => now(),
        ]);

        $after = DB::table('kepemilikan')->where('id_kepemilikan', $id)->get()->toJson();
        saveAudit('kepemilikan', $id, 'update', $before, $after);

        return response()->json(['success' => true, 'message' => 'Kepemilikan berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $inUse = DB::table('business_relations')->where('id_kepemilikan', $id)->exists();
        if ($inUse) {
            return response()->json([
                'success' => false,
                'message' => 'Kepemilikan tidak dapat dihapus karena masih digunakan oleh data Business Relation.',
            ], 422);
        }

        $before = DB::table('kepemilikan')->where('id_kepemilikan', $id)->get()->toJson();
        DB::table('kepemilikan')->where('id_kepemilikan', $id)->update(['deleted_at' => now()]);
        $after = DB::table('kepemilikan')->where('id_kepemilikan', $id)->get()->toJson();
        saveAudit('kepemilikan', $id, 'delete', $before, $after);

        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('kepemilikan')
            ->whereNull('deleted_at')
            ->where('nama', 'like', "%{$search}%")
            ->orderBy('nama')
            ->limit(20)
            ->get();

        return response()->json(
            $data->map(fn($item) => ['id' => $item->id_kepemilikan, 'text' => $item->nama])
        );
    }

    public function history($id)
    {
        return $this->auditHistory($id);
    }
}
