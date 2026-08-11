<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;

class SubKategoriBisnisController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'sub_kategori_bisnis'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_sub_kategori_bisnis']; }

    public function index()
    {
        return view('sub-kategori-bisnis.index', ['title' => 'Sub Kategori Bisnis']);
    }

    public function data()
    {
        $query = DB::table('sub_kategori_bisnis as skb')
            ->leftJoin('kategori_bisnis as kb', 'kb.id_kategori_bisnis', '=', 'skb.id_kategori_bisnis')
            ->whereNull('skb.deleted_at')
            ->select([
                'skb.id_sub_kategori_bisnis',
                'kb.nama as nama_kategori_bisnis',
                'skb.nama',
                'skb.is_aktif',
                'skb.created_at',
            ]);

        return DataTables::of($query)->addIndexColumn()->make(true);
    }

    public function create()
    {
        return view('sub-kategori-bisnis.create', ['title' => 'Tambah Sub Kategori Bisnis']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_kategori_bisnis' => 'required|integer|exists:kategori_bisnis,id_kategori_bisnis',
            'nama'               => 'required|string|max:150',
            'is_aktif'           => 'nullable|boolean',
        ]);

        $id = DB::table('sub_kategori_bisnis')->insertGetId([
            'id_kategori_bisnis' => $validated['id_kategori_bisnis'],
            'nama'               => $validated['nama'],
            'is_aktif'           => $request->boolean('is_aktif', true),
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $after = DB::table('sub_kategori_bisnis')->where('id_sub_kategori_bisnis', $id)->get()->toJson();
        saveAudit('sub_kategori_bisnis', $id, 'Create', '', $after);

        return response()->json(['success' => true, 'message' => 'Sub Kategori Bisnis berhasil dibuat', 'id' => $id]);
    }

    private function detailQuery($id)
    {
        return DB::table('sub_kategori_bisnis as skb')
            ->leftJoin('kategori_bisnis as kb', 'kb.id_kategori_bisnis', '=', 'skb.id_kategori_bisnis')
            ->where('skb.id_sub_kategori_bisnis', $id)
            ->whereNull('skb.deleted_at')
            ->select(['skb.*', 'kb.nama as nama_kategori_bisnis'])
            ->first();
    }

    public function show($id)
    {
        $data = $this->detailQuery($id);
        if (!$data) return response()->json(['message' => 'Sub Kategori Bisnis tidak ditemukan'], 404);
        return response()->json($data);
    }

    public function detail($id)
    {
        return $this->show($id);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'id_kategori_bisnis' => 'required|integer|exists:kategori_bisnis,id_kategori_bisnis',
            'nama'               => 'required|string|max:150',
            'is_aktif'           => 'nullable|boolean',
        ]);

        $before = DB::table('sub_kategori_bisnis')->where('id_sub_kategori_bisnis', $id)->get()->toJson();

        DB::table('sub_kategori_bisnis')->where('id_sub_kategori_bisnis', $id)->update([
            'id_kategori_bisnis' => $validated['id_kategori_bisnis'],
            'nama'               => $validated['nama'],
            'is_aktif'           => $request->boolean('is_aktif', true),
            'updated_at'         => now(),
        ]);

        $after = DB::table('sub_kategori_bisnis')->where('id_sub_kategori_bisnis', $id)->get()->toJson();
        saveAudit('sub_kategori_bisnis', $id, 'update', $before, $after);

        return response()->json(['success' => true, 'message' => 'Sub Kategori Bisnis berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $inUse = DB::table('business_relations')->where('id_sub_kategori_bisnis', $id)->exists();
        if ($inUse) {
            return response()->json([
                'success' => false,
                'message' => 'Sub Kategori Bisnis tidak dapat dihapus karena masih digunakan oleh data Business Relation.',
            ], 422);
        }

        $before = DB::table('sub_kategori_bisnis')->where('id_sub_kategori_bisnis', $id)->get()->toJson();
        DB::table('sub_kategori_bisnis')->where('id_sub_kategori_bisnis', $id)->update(['deleted_at' => now()]);
        $after = DB::table('sub_kategori_bisnis')->where('id_sub_kategori_bisnis', $id)->get()->toJson();
        saveAudit('sub_kategori_bisnis', $id, 'delete', $before, $after);

        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    // Select2 di-filter berdasar Kategori Bisnis yang dipilih (id_kategori_bisnis) — kalau
    // belum dipilih, tampilkan semua (opsional, dipakai saat load awal edit form).
    public function select2(Request $request)
    {
        $search = $request->q;
        $idKategori = $request->input('id_kategori_bisnis');

        $data = DB::table('sub_kategori_bisnis')
            ->whereNull('deleted_at')
            ->when($idKategori, fn($q) => $q->where('id_kategori_bisnis', $idKategori))
            ->where('nama', 'like', "%{$search}%")
            ->orderBy('nama')
            ->limit(20)
            ->get();

        return response()->json(
            $data->map(fn($item) => ['id' => $item->id_sub_kategori_bisnis, 'text' => $item->nama])
        );
    }

    public function history($id)
    {
        return $this->auditHistory($id);
    }
}
