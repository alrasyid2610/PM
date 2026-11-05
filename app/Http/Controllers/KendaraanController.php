<?php

namespace App\Http\Controllers;

use App\Support\KendaraanOptions;
use App\Traits\HasAttachment;
use App\Traits\HasAuditHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class KendaraanController extends Controller
{
    use HasAuditHistory, HasAttachment;

    protected function attachmentTable(): string      { return 'kendaraan'; }
    protected function attachmentPrimaryKey(): string { return 'id_kendaraan'; }
    protected function auditTable(): string           { return 'kendaraan'; }
    protected function auditExcludeFields(): array    { return ['updated_at', 'created_at', 'id_kendaraan']; }

    public function index()
    {
        return view('kendaraan.index', ['title' => 'Kendaraan']);
    }

    public function data()
    {
        $query = DB::table('kendaraan')
            ->whereNull('deleted_at')
            ->select(['id_kendaraan', 'nama', 'no_polisi', 'jenis', 'kepemilikan', 'nama_vendor', 'masa_berlaku_stnk', 'is_aktif', 'created_at']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('jenis', fn ($r) => KendaraanOptions::JENIS[$r->jenis] ?? $r->jenis)
            ->editColumn('kepemilikan', fn ($r) => KendaraanOptions::KEPEMILIKAN[$r->kepemilikan] ?? $r->kepemilikan)
            ->editColumn('is_aktif', fn ($r) => $r->is_aktif ? 'Aktif' : 'Tidak Aktif')
            ->make(true);
    }

    public function create()
    {
        return view('kendaraan.create', ['title' => 'Tambah Kendaraan']);
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'nama'              => 'required|string|max:150',
            'no_polisi'         => ['required', 'string', 'max:20',
                Rule::unique('kendaraan', 'no_polisi')->whereNull('deleted_at')->ignore($ignoreId, 'id_kendaraan')],
            'jenis'             => KendaraanOptions::jenisRule(),
            'merek'             => 'nullable|string|max:100',
            'model'             => 'nullable|string|max:100',
            'tahun'             => 'nullable|integer|min:1980|max:' . (now()->year + 1),
            'warna'             => 'nullable|string|max:50',
            'kepemilikan'       => KendaraanOptions::kepemilikanRule(),
            'nama_vendor'       => 'nullable|required_if:kepemilikan,sewa|string|max:150',
            'kontak_vendor'     => 'nullable|string|max:100',
            'masa_berlaku_stnk' => 'nullable|date',
            'keterangan'        => 'nullable|string',
            'is_aktif'          => 'nullable|boolean',
            'attachments.*'     => 'nullable|file|max:153600',
        ];
    }

    private const MESSAGES = [
        'no_polisi.unique'            => 'No. polisi sudah terdaftar pada kendaraan lain.',
        'nama_vendor.required_if'     => 'Nama vendor wajib diisi untuk kendaraan sewa.',
    ];

    /** Field yang disimpan ke tabel. Vendor hanya berlaku untuk kendaraan sewa. */
    private function payload(array $v, Request $request): array
    {
        $sewa = $v['kepemilikan'] === 'sewa';

        return [
            'nama'              => $v['nama'],
            'no_polisi'         => strtoupper(trim($v['no_polisi'])),
            'jenis'             => $v['jenis'],
            'merek'             => $v['merek'] ?? null,
            'model'             => $v['model'] ?? null,
            'tahun'             => $v['tahun'] ?? null,
            'warna'             => $v['warna'] ?? null,
            'kepemilikan'       => $v['kepemilikan'],
            'nama_vendor'       => $sewa ? ($v['nama_vendor'] ?? null) : null,
            'kontak_vendor'     => $sewa ? ($v['kontak_vendor'] ?? null) : null,
            'masa_berlaku_stnk' => $v['masa_berlaku_stnk'] ?? null,
            'keterangan'        => $v['keterangan'] ?? null,
            'is_aktif'          => $request->boolean('is_aktif', true),
        ];
    }

    public function store(Request $request)
    {
        $v = $request->validate($this->rules(), self::MESSAGES);

        $upload = uploadAttachment($request->file('attachments'), 'kendaraan');

        $id = DB::table('kendaraan')->insertGetId($this->payload($v, $request) + [
            'attachment' => json_encode($upload['files']),
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_kendaraan');

        $after = DB::table('kendaraan')->where('id_kendaraan', $id)->get()->toJson();
        saveAudit('kendaraan', $id, 'Create', '', $after);

        return response()->json(['success' => true, 'message' => 'Kendaraan berhasil dibuat', 'id' => $id]);
    }

    public function show($id)
    {
        $data = DB::table('kendaraan')->where('id_kendaraan', $id)->whereNull('deleted_at')->first();

        if (!$data) {
            return response()->json(['message' => 'Kendaraan tidak ditemukan'], 404);
        }

        return response()->json($data);
    }

    public function detail($id)
    {
        return $this->show($id);
    }

    public function update(Request $request, $id)
    {
        $v = $request->validate($this->rules((int) $id), self::MESSAGES);

        $before = DB::table('kendaraan')->where('id_kendaraan', $id)->get()->toJson();

        $newFiles = [];
        if ($request->hasFile('attachments')) {
            $newFiles = uploadAttachment($request->file('attachments'), 'kendaraan')['files'];
        }
        $attachments = array_merge($request->existing_attachments ?? [], $newFiles);

        DB::table('kendaraan')->where('id_kendaraan', $id)->update($this->payload($v, $request) + [
            'attachment' => json_encode($attachments),
            'updated_at' => now(),
        ]);

        $after = DB::table('kendaraan')->where('id_kendaraan', $id)->get()->toJson();
        saveAudit('kendaraan', $id, 'update', $before, $after);

        return response()->json(['success' => true, 'message' => 'Kendaraan berhasil diperbarui']);
    }

    public function destroy($id)
    {
        if (DB::table('fieldwork_kendaraan')->where('id_kendaraan', $id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kendaraan tidak dapat dihapus karena masih dipakai di Fieldwork Order.',
            ], 422);
        }

        $before = DB::table('kendaraan')->where('id_kendaraan', $id)->get()->toJson();
        DB::table('kendaraan')->where('id_kendaraan', $id)->update(['deleted_at' => now()]);
        $after = DB::table('kendaraan')->where('id_kendaraan', $id)->get()->toJson();
        saveAudit('kendaraan', $id, 'delete', $before, $after);

        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    /** Select2 (ajax): kendaraan aktif, dicari lewat nama / no. polisi. */
    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('kendaraan')
            ->whereNull('deleted_at')
            ->where('is_aktif', 1)
            ->where(fn ($q) => $q->where('nama', 'like', "%{$search}%")->orWhere('no_polisi', 'like', "%{$search}%"))
            ->orderBy('nama')
            ->limit(20)
            ->get();

        return response()->json(
            $data->map(fn ($k) => [
                'id'   => $k->id_kendaraan,
                'text' => $k->nama . ' (' . $k->no_polisi . ') — ' . (KendaraanOptions::KEPEMILIKAN[$k->kepemilikan] ?? $k->kepemilikan),
            ])
        );
    }
}
