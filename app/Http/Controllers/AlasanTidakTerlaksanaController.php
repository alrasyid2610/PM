<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;

/**
 * Master "Alasan Tidak Terlaksana" — alasan sebuah titik sample FWO tidak bisa
 * diambil. Dipakai tab Sample FWO (lab_samples.id_alasan_tidak_terlaksana).
 * Alasan dinonaktifkan (is_aktif), bukan dihapus, supaya data lama tetap terbaca;
 * hapus hanya boleh kalau belum pernah dipakai.
 */
class AlasanTidakTerlaksanaController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'alasan_tidak_terlaksana'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_alasan']; }

    public function index()
    {
        return view('alasan-tidak-terlaksana.index', ['title' => 'Alasan Tidak Terlaksana']);
    }

    public function data()
    {
        $query = DB::table('alasan_tidak_terlaksana')
            ->whereNull('deleted_at')
            ->orderBy('nama')
            ->select(['id_alasan', 'nama', 'wajib_keterangan', 'is_aktif']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('wajib_keterangan', fn ($row) => $row->wajib_keterangan
                ? '<span class="pm-badge pm-badge--blue">Wajib</span>'
                : '<span class="text-muted">—</span>')
            ->editColumn('is_aktif', fn ($row) => $row->is_aktif
                ? '<span class="pm-badge pm-badge--green">Aktif</span>'
                : '<span class="pm-badge pm-badge--red">Non Aktif</span>')
            ->rawColumns(['wajib_keterangan', 'is_aktif'])
            ->make(true);
    }

    public function create()
    {
        return view('alasan-tidak-terlaksana.create', ['title' => 'Tambah Alasan Tidak Terlaksana']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('alasan_tidak_terlaksana', 'nama')->whereNull('deleted_at')],
        ], [
            'nama.unique' => 'Alasan tersebut sudah ada.',
        ]);

        $id = DB::table('alasan_tidak_terlaksana')->insertGetId([
            'nama'             => $validated['nama'],
            'wajib_keterangan' => $request->boolean('wajib_keterangan') ? 1 : 0,
            'is_aktif'         => 1,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $after = DB::table('alasan_tidak_terlaksana')->where('id_alasan', $id)->get()->toJson();
        saveAudit('alasan_tidak_terlaksana', $id, 'Create', '', $after);

        return response()->json(['success' => true, 'message' => 'Alasan berhasil dibuat', 'id' => $id]);
    }

    public function show($id)
    {
        $data = DB::table('alasan_tidak_terlaksana')->where('id_alasan', $id)->whereNull('deleted_at')->first();
        if (!$data) return response()->json(['message' => 'Alasan tidak ditemukan'], 404);

        $data->jumlah_dipakai = DB::table('lab_samples')->where('id_alasan_tidak_terlaksana', $id)->count();
        return response()->json($data);
    }

    public function detail($id)
    {
        return $this->show($id);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('alasan_tidak_terlaksana', 'nama')->ignore((int) $id, 'id_alasan')->whereNull('deleted_at')],
        ], [
            'nama.unique' => 'Alasan tersebut sudah ada.',
        ]);

        $before = DB::table('alasan_tidak_terlaksana')->where('id_alasan', $id)->get()->toJson();

        DB::table('alasan_tidak_terlaksana')->where('id_alasan', $id)->update([
            'nama'             => $validated['nama'],
            'wajib_keterangan' => $request->boolean('wajib_keterangan') ? 1 : 0,
            'is_aktif'         => $request->boolean('is_aktif') ? 1 : 0,
            'updated_at'       => now(),
        ]);

        $after = DB::table('alasan_tidak_terlaksana')->where('id_alasan', $id)->get()->toJson();
        saveAudit('alasan_tidak_terlaksana', $id, 'update', $before, $after);

        return response()->json(['success' => true, 'message' => 'Alasan berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $inUse = DB::table('lab_samples')->where('id_alasan_tidak_terlaksana', $id)->exists();
        if ($inUse) {
            return response()->json([
                'success' => false,
                'message' => 'Alasan sudah pernah dipakai pada sample. Nonaktifkan saja (ubah Status menjadi tidak aktif) agar data lama tetap terbaca.',
            ], 422);
        }

        $before = DB::table('alasan_tidak_terlaksana')->where('id_alasan', $id)->get()->toJson();
        DB::table('alasan_tidak_terlaksana')->where('id_alasan', $id)->update(['deleted_at' => now()]);
        $after = DB::table('alasan_tidak_terlaksana')->where('id_alasan', $id)->get()->toJson();
        saveAudit('alasan_tidak_terlaksana', $id, 'delete', $before, $after);

        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    // Hanya alasan aktif yang bisa dipilih untuk catatan baru
    public function select2(Request $request)
    {
        $data = DB::table('alasan_tidak_terlaksana')
            ->whereNull('deleted_at')
            ->where('is_aktif', 1)
            ->when($request->q, fn ($q, $s) => $q->where('nama', 'like', "%{$s}%"))
            ->orderBy('nama')
            ->get();

        return response()->json(
            $data->map(fn ($item) => [
                'id'               => $item->id_alasan,
                'text'             => $item->nama,
                'wajib_keterangan' => (int) $item->wajib_keterangan,
            ])
        );
    }

    public function history($id)
    {
        return $this->auditHistory($id);
    }
}
