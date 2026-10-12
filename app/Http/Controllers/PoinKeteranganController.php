<?php

namespace App\Http\Controllers;

use App\Support\PoinKeteranganTemplate as Tpl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;

/**
 * Master "Poin Keterangan" — varian poin keterangan penawaran (SQ) yang dipilih user
 * supaya isi keterangan konsisten (bukan teks bebas). Isian yang boleh diubah per SQ
 * ditulis di template: {kunci:tipe=default} (lihat App\Support\PoinKeteranganTemplate).
 */
class PoinKeteranganController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'poin_keterangan'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_poin']; }

    private function validated(Request $request): array
    {
        $v = $request->validate([
            'kategori' => ['required', Rule::in(array_keys(Tpl::KATEGORI))],
            'template' => 'required|string|max:2000',
        ], [
            'kategori.required' => 'Kategori wajib dipilih.',
            'kategori.in'       => 'Kategori tidak valid.',
            'template.required' => 'Teks poin wajib diisi.',
        ]);

        if ($msg = Tpl::validate($v['template'])) {
            abort(response()->json(['message' => $msg, 'errors' => ['template' => [$msg]]], 422));
        }

        return $v;
    }

    public function index()
    {
        return view('poin-keterangan.index', ['title' => 'Poin Keterangan']);
    }

    public function data()
    {
        $query = DB::table('poin_keterangan')
            ->whereNull('deleted_at')
            ->orderBy('kategori')
            ->orderBy('id_poin')
            ->select(['id_poin', 'kategori', 'template', 'is_default', 'is_aktif']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('kategori', fn ($row) => e(Tpl::KATEGORI[$row->kategori] ?? $row->kategori))
            ->editColumn('template', fn ($row) => nl2br(e(Tpl::render($row->template))))
            ->editColumn('is_default', fn ($row) => $row->is_default
                ? '<span class="pm-badge pm-badge--blue">Paket Standar</span>'
                : '<span class="text-muted">—</span>')
            ->editColumn('is_aktif', fn ($row) => $row->is_aktif
                ? '<span class="pm-badge pm-badge--green">Aktif</span>'
                : '<span class="pm-badge pm-badge--red">Non Aktif</span>')
            ->rawColumns(['kategori', 'template', 'is_default', 'is_aktif'])
            ->make(true);
    }

    public function create()
    {
        return view('poin-keterangan.create', [
            'title'    => 'Tambah Poin Keterangan',
            'kategori' => Tpl::KATEGORI,
            'tipe'     => Tpl::TIPE,
        ]);
    }

    public function store(Request $request)
    {
        $v = $this->validated($request);
        $isDefault = $request->boolean('is_default');

        $id = DB::table('poin_keterangan')->insertGetId([
            'kategori'       => $v['kategori'],
            'template'       => $v['template'],
            'is_default'     => $isDefault ? 1 : 0,
            'urutan_default' => $isDefault ? $this->nextUrutanDefault() : 0,
            'is_aktif'       => 1,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $after = DB::table('poin_keterangan')->where('id_poin', $id)->get()->toJson();
        saveAudit('poin_keterangan', $id, 'Create', '', $after);

        return response()->json(['success' => true, 'message' => 'Poin keterangan berhasil dibuat', 'id' => $id]);
    }

    public function show($id)
    {
        $data = DB::table('poin_keterangan')->where('id_poin', $id)->whereNull('deleted_at')->first();
        if (!$data) return response()->json(['message' => 'Poin keterangan tidak ditemukan'], 404);

        $data->isian       = Tpl::parse($data->template);
        $data->preview     = Tpl::render($data->template);
        $data->kategori_label = Tpl::KATEGORI[$data->kategori] ?? $data->kategori;
        $data->kategori_opsi  = collect(Tpl::KATEGORI)->map(fn ($l, $k) => ['value' => $k, 'label' => $l])->values();
        return response()->json($data);
    }

    public function detail($id)
    {
        return $this->show($id);
    }

    public function update(Request $request, $id)
    {
        $v = $this->validated($request);
        $row = DB::table('poin_keterangan')->where('id_poin', $id)->whereNull('deleted_at')->first();
        if (!$row) return response()->json(['message' => 'Poin keterangan tidak ditemukan'], 404);

        $before    = DB::table('poin_keterangan')->where('id_poin', $id)->get()->toJson();
        $isDefault = $request->boolean('is_default');

        DB::table('poin_keterangan')->where('id_poin', $id)->update([
            'kategori'       => $v['kategori'],
            'template'       => $v['template'],
            'is_default'     => $isDefault ? 1 : 0,
            // urutan hanya dibuat saat baru dijadikan standar; dilepas → 0
            'urutan_default' => $isDefault ? ($row->urutan_default ?: $this->nextUrutanDefault()) : 0,
            'is_aktif'       => $request->boolean('is_aktif') ? 1 : 0,
            'updated_at'     => now(),
        ]);

        $after = DB::table('poin_keterangan')->where('id_poin', $id)->get()->toJson();
        saveAudit('poin_keterangan', $id, 'update', $before, $after);

        return response()->json(['success' => true, 'message' => 'Poin keterangan berhasil diperbarui']);
    }

    public function destroy($id)
    {
        if (DB::table('sq_keterangan')->where('id_poin', $id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Poin sudah dipakai di keterangan Sales Quotation. Nonaktifkan saja (ubah Status menjadi tidak aktif) agar SQ lama tetap terbaca.',
            ], 422);
        }

        $before = DB::table('poin_keterangan')->where('id_poin', $id)->get()->toJson();
        DB::table('poin_keterangan')->where('id_poin', $id)->update(['deleted_at' => now()]);
        $after = DB::table('poin_keterangan')->where('id_poin', $id)->get()->toJson();
        saveAudit('poin_keterangan', $id, 'delete', $before, $after);

        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    // Poin aktif, dikelompokkan per kategori — untuk dipilih di SQ (fase 2)
    public function select2(Request $request)
    {
        $data = DB::table('poin_keterangan')
            ->whereNull('deleted_at')
            ->where('is_aktif', 1)
            ->when($request->kategori, fn ($q, $k) => $q->where('kategori', $k))
            ->orderBy('kategori')
            ->orderBy('id_poin')
            ->get();

        return response()->json(
            $data->map(fn ($item) => [
                'id'       => $item->id_poin,
                'text'     => Tpl::render($item->template),
                'kategori' => $item->kategori,
                'isian'    => Tpl::parse($item->template),
            ])
        );
    }

    public function history($id)
    {
        return $this->auditHistory($id);
    }

    private function nextUrutanDefault(): int
    {
        return (int) DB::table('poin_keterangan')->whereNull('deleted_at')->where('is_default', 1)->max('urutan_default') + 1;
    }
}
