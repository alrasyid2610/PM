<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;

class EntitasController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'entitas'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_entitas']; }

    public function index()
    {
        return view('entitas.index', ['title' => 'Entitas']);
    }

    public function data()
    {
        $query = DB::table('entitas')
            ->whereNull('deleted_at')
            ->select(['id_entitas', 'nama', 'is_aktif', 'created_at']);

        return DataTables::of($query)->addIndexColumn()->make(true);
    }

    public function create()
    {
        return view('entitas.create', ['title' => 'Tambah Entitas']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'     => 'required|string|max:100|unique:entitas,nama',
            'is_aktif' => 'nullable|boolean',
        ], [
            'nama.unique' => 'Nama entitas sudah digunakan.',
        ]);

        $id = DB::table('entitas')->insertGetId([
            'nama'       => $validated['nama'],
            'is_aktif'   => $request->boolean('is_aktif', true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $after = DB::table('entitas')->where('id_entitas', $id)->get()->toJson();
        saveAudit('entitas', $id, 'Create', '', $after);

        return response()->json(['success' => true, 'message' => 'Entitas berhasil dibuat', 'id' => $id]);
    }

    public function show($id)
    {
        $data = DB::table('entitas')->where('id_entitas', $id)->whereNull('deleted_at')->first();
        if (!$data) return response()->json(['message' => 'Entitas tidak ditemukan'], 404);
        return response()->json($data);
    }

    public function detail($id)
    {
        return $this->show($id);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama'     => ['required', 'string', 'max:100', Rule::unique('entitas', 'nama')->ignore((int) $id, 'id_entitas')],
            'is_aktif' => 'nullable|boolean',
        ], [
            'nama.unique' => 'Nama entitas sudah digunakan.',
        ]);

        $before = DB::table('entitas')->where('id_entitas', $id)->get()->toJson();

        DB::table('entitas')->where('id_entitas', $id)->update([
            'nama'       => $validated['nama'],
            'is_aktif'   => $request->boolean('is_aktif', true),
            'updated_at' => now(),
        ]);

        $after = DB::table('entitas')->where('id_entitas', $id)->get()->toJson();
        saveAudit('entitas', $id, 'update', $before, $after);

        return response()->json(['success' => true, 'message' => 'Entitas berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $inUse = DB::table('business_relations')->where('id_entitas', $id)->exists();
        if ($inUse) {
            return response()->json([
                'success' => false,
                'message' => 'Entitas tidak dapat dihapus karena masih digunakan oleh data Business Relation.',
            ], 422);
        }

        $before = DB::table('entitas')->where('id_entitas', $id)->get()->toJson();
        DB::table('entitas')->where('id_entitas', $id)->update(['deleted_at' => now()]);
        $after = DB::table('entitas')->where('id_entitas', $id)->get()->toJson();
        saveAudit('entitas', $id, 'delete', $before, $after);

        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('entitas')
            ->whereNull('deleted_at')
            ->where('nama', 'like', "%{$search}%")
            ->orderBy('nama')
            ->limit(20)
            ->get();

        return response()->json(
            $data->map(fn($item) => ['id' => $item->id_entitas, 'text' => $item->nama])
        );
    }

    public function history($id)
    {
        return $this->auditHistory($id);
    }
}
