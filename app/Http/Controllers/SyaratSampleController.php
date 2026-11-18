<?php

namespace App\Http\Controllers;

use App\Support\SyaratSampleTemplate as Tpl;
use App\Traits\HasAuditHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

/**
 * Master "Syarat Sample" — daftar syarat pengambilan/penerimaan sample (mis. "Menggunakan {jumlah:angka=5} botol").
 * Dipilih per Testing Point (tab Syarat) dengan nilai isian masing-masing; dipakai Lab Sample Reg.
 * Pola sama dengan Poin Keterangan SQ.
 */
class SyaratSampleController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'syarat_sample'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_syarat', 'deleted_at']; }

    private function validated(Request $request): array
    {
        $request->merge(['template' => trim((string) $request->template)]);
        $v = $request->validate([
            'template' => 'required|string|max:1000',
        ], [
            'template.required' => 'Teks syarat wajib diisi.',
        ]);

        if ($msg = Tpl::validate($v['template'])) {
            abort(response()->json(['message' => $msg, 'errors' => ['template' => [$msg]]], 422));
        }

        return $v;
    }

    private function pemakaian(int $id): int
    {
        return (int) DB::table('testing_point_syarat')->where('id_syarat', $id)->count();
    }

    public function index()
    {
        return view('syarat-sample.index', ['title' => 'Syarat Sample']);
    }

    public function data()
    {
        $query = DB::table('syarat_sample as s')
            ->whereNull('s.deleted_at')
            ->orderBy('s.id_syarat')
            ->select([
                's.id_syarat', 's.template', 's.is_aktif',
                DB::raw('(SELECT COUNT(*) FROM testing_point_syarat t WHERE t.id_syarat = s.id_syarat) AS dipakai'),
            ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('template', fn ($row) => e(Tpl::render($row->template)))
            ->editColumn('is_aktif', fn ($row) => $row->is_aktif
                ? '<span class="pm-badge pm-badge--green">Aktif</span>'
                : '<span class="pm-badge pm-badge--red">Non Aktif</span>')
            ->rawColumns(['template', 'is_aktif'])
            ->make(true);
    }

    /** Syarat aktif untuk dipilih di tab Syarat Testing Point. */
    public function select2()
    {
        $rows = DB::table('syarat_sample')->whereNull('deleted_at')->where('is_aktif', 1)->orderBy('id_syarat')->get();

        return response()->json($rows->map(fn ($r) => [
            'id'    => $r->id_syarat,
            'text'  => Tpl::render($r->template),
            'isian' => Tpl::parse($r->template),
        ]));
    }

    public function create()
    {
        return view('syarat-sample.create', ['title' => 'Tambah Syarat Sample']);
    }

    public function store(Request $request)
    {
        $v = $this->validated($request);

        $id = DB::table('syarat_sample')->insertGetId([
            'template'   => $v['template'],
            'is_aktif'   => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_syarat');

        saveAudit('syarat_sample', $id, 'Create', '', DB::table('syarat_sample')->where('id_syarat', $id)->get()->toJson());

        return response()->json(['success' => true, 'message' => 'Syarat sample berhasil dibuat', 'id' => $id]);
    }

    public function show($id)
    {
        $row = DB::table('syarat_sample')->where('id_syarat', $id)->whereNull('deleted_at')->first();
        if (!$row) return response()->json(['message' => 'Syarat sample tidak ditemukan'], 404);

        $row->isian   = Tpl::parse($row->template);
        $row->preview = Tpl::render($row->template);
        $row->dipakai = $this->pemakaian((int) $id);

        return response()->json($row);
    }

    public function detail($id)
    {
        return $this->show($id);
    }

    public function update(Request $request, $id)
    {
        $row = DB::table('syarat_sample')->where('id_syarat', $id)->whereNull('deleted_at')->first();
        if (!$row) return response()->json(['message' => 'Syarat sample tidak ditemukan'], 404);

        $v = $this->validated($request);

        // Isian yang sudah dipakai tidak boleh diubah tipe/hilang diam-diam: nilai tersimpan per kunci. Hapus/ganti kunci
        // hanya membuat nilai lama tidak terpakai (aman), jadi cukup diinformasikan lewat pesan.
        $before = DB::table('syarat_sample')->where('id_syarat', $id)->get()->toJson();

        DB::table('syarat_sample')->where('id_syarat', $id)->update([
            'template'   => $v['template'],
            'is_aktif'   => $request->boolean('is_aktif') ? 1 : 0,
            'updated_at' => now(),
        ]);

        saveAudit('syarat_sample', $id, 'update', $before, DB::table('syarat_sample')->where('id_syarat', $id)->get()->toJson());

        return response()->json(['success' => true, 'message' => 'Syarat sample berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $row = DB::table('syarat_sample')->where('id_syarat', $id)->whereNull('deleted_at')->first();
        if (!$row) return response()->json(['success' => false, 'message' => 'Syarat sample tidak ditemukan'], 404);

        $n = $this->pemakaian((int) $id);
        if ($n > 0) {
            return response()->json([
                'success' => false,
                'message' => "Syarat ini dipakai {$n} Testing Point sehingga tidak bisa dihapus. Nonaktifkan saja (Testing Point yang sudah memakainya tetap menampilkannya).",
            ], 422);
        }

        $before = DB::table('syarat_sample')->where('id_syarat', $id)->get()->toJson();
        DB::table('syarat_sample')->where('id_syarat', $id)->update(['deleted_at' => now()]);
        saveAudit('syarat_sample', $id, 'delete', $before, DB::table('syarat_sample')->where('id_syarat', $id)->get()->toJson());

        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function history($id)
    {
        return $this->auditHistory($id);
    }
}
