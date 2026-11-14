<?php

namespace App\Http\Controllers;

use App\Support\ListFilter\Sets\OutputMasterFilters;
use App\Traits\HasAuditHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

/**
 * Master Output (sejak 2026-11-14): daftar judul yang boleh dipakai di Output Pekerjaan (tipe `pekerjaan`) dan
 * Output Other (tipe `other`). Judul output tidak lagi diketik bebas — dipilih dari sini (lihat App\Support\OutputJudul).
 * Output menyimpan `id_output_judul` + salinan teks judul; mengubah judul di sini ikut memperbarui salinan teks itu.
 */
class OutputMasterController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'output_judul'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_output_judul']; }

    /** Jumlah output yang memakai 1 judul master: [pekerjaan, other]. */
    private function pemakaian(int $id): array
    {
        return [
            (int) DB::table('output_pekerjaan')->where('id_output_judul', $id)->count(),
            (int) DB::table('output_tambahan')->where('id_output_judul', $id)->whereNull('deleted_at')->count(),
        ];
    }

    public function index()
    {
        return view('output-master.index', ['title' => 'Master Output']);
    }

    public function data(Request $request)
    {
        $set     = new OutputMasterFilters();
        $filters = $set->normalize($request->input('filters'));

        $query = DB::table('output_judul as m')
            ->select([
                'm.id_output_judul',
                'm.judul',
                'm.tipe',
                'm.is_lhu',
                'm.is_aktif',
                DB::raw('(SELECT COUNT(*) FROM output_pekerjaan op WHERE op.id_output_judul = m.id_output_judul)'
                    . ' + (SELECT COUNT(*) FROM output_tambahan ot WHERE ot.id_output_judul = m.id_output_judul AND ot.deleted_at IS NULL) AS dipakai'),
                'm.created_at',
            ])
            ->tap(fn ($q) => $set->apply($q, $filters))
            ->orderBy('m.tipe')
            ->orderBy('m.judul');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('tipe', fn ($r) => $r->tipe === 'other' ? 'Other' : 'Pekerjaan')
            ->editColumn('is_lhu', fn ($r) => $r->is_lhu ? 'LHU' : '—')
            ->editColumn('is_aktif', fn ($r) => $r->is_aktif ? 'Aktif' : 'Tidak Aktif')
            ->make(true);
    }

    public function create()
    {
        return view('output-master.create', ['title' => 'Tambah Master Output']);
    }

    /** Rapikan spasi (sama dengan aturan lama OutputJudul) sebelum divalidasi / disimpan. */
    private function normalisasi(Request $request): void
    {
        $request->merge(['judul' => trim(preg_replace('/\s+/u', ' ', (string) $request->judul))]);
    }

    private function rules(Request $request, ?int $ignoreId = null): array
    {
        return [
            'judul'    => ['required', 'string', 'max:255',
                Rule::unique('output_judul', 'judul')->where('tipe', $request->tipe)->ignore($ignoreId, 'id_output_judul')],
            'tipe'     => ['required', 'in:pekerjaan,other'],
            'is_aktif' => 'nullable|boolean',
            'is_lhu'   => 'nullable|boolean',
        ];
    }

    private const MESSAGES = [
        'judul.unique' => 'Judul ini sudah ada di Master Output untuk tipe yang sama.',
    ];

    public function store(Request $request)
    {
        $this->normalisasi($request);
        $v = $request->validate($this->rules($request), self::MESSAGES);

        $id = DB::table('output_judul')->insertGetId([
            'tipe'       => $v['tipe'],
            'judul'      => $v['judul'],
            'is_aktif'   => $request->boolean('is_aktif', true) ? 1 : 0,
            // Penanda LHU hanya berarti untuk Output Pekerjaan
            'is_lhu'     => $v['tipe'] === 'pekerjaan' && $request->boolean('is_lhu') ? 1 : 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_output_judul');

        saveAudit('output_judul', $id, 'Create', '', DB::table('output_judul')->where('id_output_judul', $id)->get()->toJson());

        return response()->json(['success' => true, 'message' => 'Judul output berhasil ditambahkan', 'id' => $id]);
    }

    public function show($id)
    {
        $row = DB::table('output_judul')->where('id_output_judul', $id)->first();
        if (!$row) {
            return response()->json(['message' => 'Judul output tidak ditemukan'], 404);
        }

        [$pekerjaan, $other] = $this->pemakaian((int) $id);
        $row->dipakai_pekerjaan = $pekerjaan;
        $row->dipakai_other     = $other;
        $row->dipakai           = $pekerjaan + $other;

        return response()->json($row);
    }

    public function detail($id)
    {
        return $this->show($id);
    }

    public function update(Request $request, $id)
    {
        $row = DB::table('output_judul')->where('id_output_judul', $id)->first();
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Judul output tidak ditemukan'], 404);
        }

        $this->normalisasi($request);
        $v = $request->validate($this->rules($request, (int) $id), self::MESSAGES);

        [$pekerjaan, $other] = $this->pemakaian((int) $id);
        if ($v['tipe'] !== $row->tipe && ($pekerjaan + $other) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tipe tidak bisa diubah karena judul ini sudah dipakai ' . ($pekerjaan + $other) . ' output. Buat judul baru dengan tipe yang diinginkan, lalu gabungkan.',
            ], 422);
        }

        $before = DB::table('output_judul')->where('id_output_judul', $id)->get()->toJson();

        DB::transaction(function () use ($id, $row, $v, $request) {
            DB::table('output_judul')->where('id_output_judul', $id)->update([
                'tipe'       => $v['tipe'],
                'judul'      => $v['judul'],
                'is_aktif'   => $request->boolean('is_aktif') ? 1 : 0,
                'is_lhu'     => $v['tipe'] === 'pekerjaan' && $request->boolean('is_lhu') ? 1 : 0,
                'updated_at' => now(),
            ]);

            // Ubah judul → salinan teks di output yang memakainya ikut diperbarui (Termin, SO, dll membaca teks itu)
            if ($v['judul'] !== $row->judul) {
                DB::table('output_pekerjaan')->where('id_output_judul', $id)->update(['judul_output' => $v['judul']]);
                DB::table('output_tambahan')->where('id_output_judul', $id)->update(['nama_item' => $v['judul']]);
            }
        });

        saveAudit('output_judul', $id, 'update', $before, DB::table('output_judul')->where('id_output_judul', $id)->get()->toJson());

        return response()->json(['success' => true, 'message' => 'Judul output berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $row = DB::table('output_judul')->where('id_output_judul', $id)->first();
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Judul output tidak ditemukan'], 404);
        }

        [$pekerjaan, $other] = $this->pemakaian((int) $id);
        if (($pekerjaan + $other) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Judul ini dipakai ' . ($pekerjaan + $other) . ' output sehingga tidak bisa dihapus. Nonaktifkan saja (output lama tetap valid), atau gabungkan ke judul lain.',
            ], 422);
        }

        $before = DB::table('output_judul')->where('id_output_judul', $id)->get()->toJson();
        DB::table('output_judul')->where('id_output_judul', $id)->delete();
        saveAudit('output_judul', $id, 'delete', $before, '');

        return response()->json(['success' => true, 'message' => 'Judul output berhasil dihapus']);
    }

    /**
     * Gabungkan judul ini (sumber) ke judul lain (target, harus tipe sama & aktif): semua output yang memakai sumber
     * dipindah ke target (id + salinan teks), lalu sumber dinonaktifkan. Untuk merapikan varian ejaan.
     */
    public function merge(Request $request, $id)
    {
        $request->validate(['target_id' => 'required|integer']);

        $source = DB::table('output_judul')->where('id_output_judul', $id)->first();
        $target = DB::table('output_judul')->where('id_output_judul', (int) $request->target_id)->first();

        if (!$source || !$target) {
            return response()->json(['success' => false, 'message' => 'Judul sumber atau tujuan tidak ditemukan'], 404);
        }
        if ((int) $source->id_output_judul === (int) $target->id_output_judul) {
            return response()->json(['success' => false, 'message' => 'Judul tujuan harus berbeda dari judul sumber'], 422);
        }
        if ($source->tipe !== $target->tipe) {
            return response()->json(['success' => false, 'message' => 'Hanya bisa digabung ke judul bertipe sama'], 422);
        }
        if (!$target->is_aktif) {
            return response()->json(['success' => false, 'message' => 'Judul tujuan harus aktif'], 422);
        }

        $jumlah = 0;
        DB::transaction(function () use ($source, $target, &$jumlah) {
            $jumlah += DB::table('output_pekerjaan')->where('id_output_judul', $source->id_output_judul)
                ->update(['id_output_judul' => $target->id_output_judul, 'judul_output' => $target->judul]);
            $jumlah += DB::table('output_tambahan')->where('id_output_judul', $source->id_output_judul)
                ->update(['id_output_judul' => $target->id_output_judul, 'nama_item' => $target->judul]);

            DB::table('output_judul')->where('id_output_judul', $source->id_output_judul)
                ->update(['is_aktif' => 0, 'updated_at' => now()]);
        });

        saveAudit('output_judul', $id, 'update',
            json_encode([$source]),
            json_encode([['judul' => $source->judul, 'is_aktif' => 0, 'digabung_ke' => $target->judul, 'output_dipindah' => $jumlah]])
        );

        return response()->json([
            'success' => true,
            'message' => "{$jumlah} output dipindah ke \"{$target->judul}\"; \"{$source->judul}\" dinonaktifkan.",
        ]);
    }

    public function history($id)
    {
        return $this->auditHistory($id);
    }
}
