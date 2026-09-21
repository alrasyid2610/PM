<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;

class OfficeController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'office'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_office']; }

    public function index()
    {
        return view('office.index', ['title' => 'Office']);
    }

    public function data()
    {
        $query = DB::table('office')
            ->whereNull('deleted_at')
            ->select(['id_office', 'name', 'alamat', 'created_at']);

        return DataTables::of($query)->addIndexColumn()->make(true);
    }

    public function create()
    {
        return view('office.create', ['title' => 'Tambah Office']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'   => 'required|string|max:255|unique:office,name',
            'alamat' => 'nullable|string|max:255',
        ], [
            'name.unique' => 'Nama office sudah digunakan.',
        ]);

        $id = DB::table('office')->insertGetId([
            'name'       => $validated['name'],
            'alamat'     => $validated['alamat'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $after = DB::table('office')->where('id_office', $id)->get()->toJson();
        saveAudit('office', $id, 'Create', '', $after);

        return response()->json(['success' => true, 'message' => 'Office berhasil dibuat', 'id' => $id]);
    }

    public function show($id)
    {
        $data = DB::table('office')->where('id_office', $id)->whereNull('deleted_at')->first();
        if (!$data) return response()->json(['message' => 'Office tidak ditemukan'], 404);
        return response()->json($data);
    }

    public function detail($id)
    {
        return $this->show($id);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255', Rule::unique('office', 'name')->ignore((int) $id, 'id_office')],
            'alamat' => 'nullable|string|max:255',
        ], [
            'name.unique' => 'Nama office sudah digunakan.',
        ]);

        $before = DB::table('office')->where('id_office', $id)->get()->toJson();

        DB::table('office')->where('id_office', $id)->update([
            'name'       => $validated['name'],
            'alamat'     => $validated['alamat'] ?? null,
            'updated_at' => now(),
        ]);

        $after = DB::table('office')->where('id_office', $id)->get()->toJson();
        saveAudit('office', $id, 'update', $before, $after);

        return response()->json(['success' => true, 'message' => 'Office berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $inUse = DB::table('sales_orders')->where('id_office', $id)->whereNull('deleted_at')->exists();
        if ($inUse) {
            return response()->json([
                'success' => false,
                'message' => 'Office tidak dapat dihapus karena masih digunakan oleh data Sales Order.',
            ], 422);
        }

        $before = DB::table('office')->where('id_office', $id)->get()->toJson();
        DB::table('office')->where('id_office', $id)->update(['deleted_at' => now()]);
        $after = DB::table('office')->where('id_office', $id)->get()->toJson();
        saveAudit('office', $id, 'delete', $before, $after);

        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('office')
            ->whereNull('deleted_at')
            ->where('name', 'like', "%{$search}%")
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json(
            $data->map(fn($item) => ['id' => $item->id_office, 'text' => $item->name])
        );
    }

    public function history($id)
    {
        return $this->auditHistory($id);
    }
}
