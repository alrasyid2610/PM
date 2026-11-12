<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistoryWithLines;
use App\Traits\HasAttachment;

class TestingPointController extends Controller
{
    use HasAuditHistoryWithLines, HasAttachment;

    protected function attachmentTable(): string      { return 'testing_points'; }
    protected function attachmentPrimaryKey(): string { return 'id_testing_point'; }

    protected function auditTable(): string { return 'testing_points'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_testing_point', '_lines']; }
    protected function auditLinesTable(): string { return 'testing_items'; }
    protected function auditLinesForeignKey(): string { return 'id_testing_point'; }
    protected function auditLinesPrimaryKey(): string { return 'id_testing_item'; }
    protected function auditLinesExcludeFields(): array { return ['updated_at', 'created_at', 'id_testing_item', 'id_testing_point']; }

    public function index()
    {
        return view('testing-points.index');
    }

    /** Pesan validasi data Testing Point (Bahasa Indonesia, bukan nama kolom teknis). */
    private function pointMessages(): array
    {
        return [
            'id_testing_standard.required'       => 'Testing Standard wajib dipilih.',
            'id_testing_matriks_sample.required' => 'Testing Matriks Sample wajib dipilih.',
            'nama.required'                      => 'Nama Testing Point wajib diisi.',
            'is_aktif.required'                  => 'Status wajib dipilih.',
        ];
    }

    /**
     * Pilih baris Testing Item yang disimpan dari form (tabel dinamis).
     * - Baris yang SELURUH isinya kosong (dan belum punya ID item) dilewati, tetapi Testing Point wajib punya minimal 1 item lengkap (422 bila tidak).
     * - Baris yang sebagian terisi (atau item lama yang dikosongkan) tetapi ada kolom wajib kosong → 422 dengan pesan
     *   yang menyebut baris & kolomnya. Kolom wajib (NOT NULL di `testing_items`): Judul Indonesia, Judul Inggris,
     *   Parameter, Nilai. Dulu jadi error SQL mentah setelah Testing Point terlanjur tersimpan.
     * @return int[] indeks baris yang disimpan
     */
    private function itemRowsToSave(Request $request, string $ketKey, bool $hasIds = false): array
    {
        $wajib = ['judul_indonesia' => 'Judul Indonesia', 'judul_inggris' => 'Judul Inggris', 'parameter' => 'Parameter', 'nilai' => 'Nilai'];
        $judul = $request->judul_indonesia ?? [];
        $keep = [];
        $pesan = [];
        foreach ($judul as $i => $val) {
            $ada = fn ($k) => trim((string) (($request->$k ?? [])[$i] ?? '')) !== '';
            $adaId = $hasIds && !empty(($request->id_testing_item ?? [])[$i]);
            $lainIsi = $ada('judul_indonesia') || $ada('judul_inggris') || $ada('parameter') || $ada('unit') || $ada('nilai') || $ada($ketKey);

            if (!$adaId && !$lainIsi) continue; // baris kosong sama sekali

            $kurang = [];
            foreach ($wajib as $k => $label) {
                if (!$ada($k)) $kurang[] = $label;
            }
            if ($kurang) {
                $pesan[] = 'Testing Item baris ke-' . ($i + 1) . ': ' . implode(', ', $kurang) . ' wajib diisi.';
                continue;
            }
            $keep[] = $i;
        }

        if ($pesan) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                // 1 string (handler form hanya menampilkan elemen pertama tiap kunci)
                'items' => [implode(' ', array_slice($pesan, 0, 5)) . (count($pesan) > 5 ? ' (+' . (count($pesan) - 5) . ' baris lain)' : '') . ' Lengkapi barisnya, atau kosongkan seluruh baris / hapus barisnya bila tidak dipakai.'],
            ]);
        }

        // Testing Point wajib punya minimal 1 Testing Item (lengkap)
        if (!$keep) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'items' => ['Testing Point minimal harus punya 1 Testing Item. Isi minimal satu baris item secara lengkap.'],
            ]);
        }
        return $keep;
    }

    public function data()
    {
        $query = DB::table('testing_points as tp')
            ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
            ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
            ->leftJoin('testing_kelompok_matriks_samples as tkms', 'tkms.id_testing_kelompok_matriks_sample', '=', 'tms.id_testing_kelompok_matriks_sample')
            ->whereNull('tp.deleted_at')
            ->select([
                'tp.id_testing_point',
                DB::raw(
                    "CONCAT(tms.kode, '-', ts.nomor, '-', tp.nama) as Kode"
                ),
                // 'tp.nama',
                // Nama-nama paket (tabel testing_point_paket) digabung, dipisah koma
                DB::raw(
                    "(SELECT GROUP_CONCAT(pk.nama ORDER BY pk.nama SEPARATOR ', ')
                        FROM testing_point_paket pk
                        WHERE pk.id_testing_point = tp.id_testing_point) as paket"
                ),
                // "id:nama||id:nama" — dipakai renderer kolom Paket di index (tombol per paket); key ber-prefix id_ tidak jadi kolom
                DB::raw(
                    "(SELECT GROUP_CONCAT(CONCAT(pk.id_paket, ':', pk.nama) ORDER BY pk.nama SEPARATOR '||')
                        FROM testing_point_paket pk
                        WHERE pk.id_testing_point = tp.id_testing_point) as id_paket_list"
                ),
                'tp.nomor_halaman as testing_poin_nomor_halaman',
                'tp.deskripsi as testing_poin_deskripsi',
                // 'tp.keterangan as testing_poin_keterangan',
                // 'ts.nomor as matrik_standard_nomor',
                // 'ts.judul as matrik_standard_judul',
                // 'tkms.judul_indonesia as kelompok_matriks_sample_judul_indonesia',
                // 'tms.kode as matriks_sample_kode',
                // 'tms.judul_indonesia as matriks_sample_judul_indonesia',
                'tp.is_aktif',
                'tp.created_at'
            ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('is_aktif', function ($row) {
                return $row->is_aktif ? 'Aktif' : 'Tidak Aktif';
            })
            ->make(true);
    }

    public function create()
    {
        return view('testing-points.create');
    }

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('testing_points as tp')
            ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
            ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
            ->whereNull('tp.deleted_at')
            ->where(function ($q) use ($search) {
                $q->where('tp.nama', 'like', "%{$search}%")
                  ->orWhere('tms.judul_indonesia', 'like', "%{$search}%")
                  ->orWhere('ts.nomor', 'like', "%{$search}%");
            })
            ->select([
                'tp.id_testing_point',
                'tp.nama',
                'tms.judul_indonesia as tms_judul',
                'ts.nomor as ts_nomor',
            ])
            ->limit(20)
            ->get();

        return response()->json(
            $data->map(function ($item) {
                $text = trim(implode(' ', array_filter([
                    $item->tms_judul,
                    $item->ts_nomor,
                    $item->nama,
                ])));
                return [
                    'id'   => $item->id_testing_point,
                    'text' => $text,
                ];
            })
        );
    }

    public function store(Request $request)
    {
        $table = 'testing_points';
        $validated = $request->validate([
            'id_testing_standard' => 'required|integer',
            'id_testing_matriks_sample' => 'required|integer',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'nomor_halaman' => 'nullable|string|max:50',
            'keterangan' => 'nullable|string',
            'is_aktif' => 'required|boolean',
            'attachments.*' => 'nullable|file|max:153600',
        ], $this->pointMessages());
        unset($validated['attachments']); // wildcard rule ikut masuk ke $validated, tapi kolom DB-nya 'attachment' (singular)

        // Validasi terpisah untuk field per-item (dynamic table) — sengaja
        // TIDAK digabung ke $validated di atas (yang di-spread ke insert
        // testing_points) supaya tidak kena bug yang sama seperti kasus
        // 'attachments' (field array ikut nyasar ke kolom yang salah).
        $request->validate([
            'judul_indonesia.*' => 'nullable|string|max:255',
            'judul_inggris.*'   => 'nullable|string|max:255',
            'parameter.*'       => 'nullable|integer',
            'unit.*'            => 'nullable|integer',
            'nilai.*'           => 'nullable|string|max:100',
            'item_keterangan.*' => 'nullable|string|max:255',
        ]);

        // Cek baris item SEBELUM apa pun disimpan (baris kosong dilewati, baris setengah terisi → 422)
        $keepRows = $this->itemRowsToSave($request, 'item_keterangan');

        $upload = uploadAttachment($request->file('attachments'), $table);
        $files = $upload['files'];


        $validated['attachment'] = json_encode($files);

        DB::beginTransaction();
        try {
        $id = DB::table('testing_points')->insertGetId([
            ...$validated,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert testing items
        $judul_indonesia = $request->judul_indonesia ?? [];
        $judul_inggris   = $request->judul_inggris   ?? [];
        $parameter       = $request->parameter       ?? [];
        $unit            = $request->unit            ?? [];
        $nilai           = $request->nilai           ?? [];
        $item_keterangan = $request->item_keterangan ?? [];
        $status          = $request->status          ?? [];
        $nomor           = $request->nomor           ?? [];

        foreach ($judul_indonesia as $i => $val) {
            if (!in_array($i, $keepRows, true)) continue;
            DB::table('testing_items')->insert([
                'id_testing_point'     => $id,
                'nomor'                => (int) ($nomor[$i] ?? ($i + 1)),
                'judul_indonesia'      => $val,
                'judul_inggris'        => $judul_inggris[$i]   ?? null,
                'id_testing_parameter' => $parameter[$i]       ?? null,
                'id_testing_unit'      => $unit[$i]            ?? null,
                'nilai'                => $nilai[$i]           ?? null,
                'keterangan'           => $item_keterangan[$i] ?? null,
                'is_aktif'             => isset($status[$i]) ? $status[$i] : 0,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }

        $after = DB::table('testing_points')->where('id_testing_point', $id)->get()->toJson();
        saveAudit('testing_points', $id, 'Create', '', $after);

        DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th; // semua-atau-tidak-sama-sekali: Testing Point tidak tersimpan setengah jalan
        }

        return response()->json([
            'status' => 'success',
            'id' => $id
        ]);
    }

    public function detail($id)
    {
        $data = DB::table('testing_points as a')
            ->leftJoin('testing_standards as b', 'a.id_testing_standard', '=', 'b.id_testing_standard')
            ->leftJoin('testing_matriks_samples as c', 'c.id_testing_matriks_sample', '=', 'a.id_testing_matriks_sample')
            ->where('a.id_testing_point', $id)
            ->whereNull('a.deleted_at')
            ->select(
                'a.*',
                'b.nomor as standard_nomor',
                'b.judul as standard_judul',
                'c.kode as matrik_sample_kode',
                'c.judul_indonesia as matrik_sample_judul_indonesia',
            )
            ->first();

        if (!$data) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($data);
    }

    public function update(Request $request, $id)
    {


        $validated = $request->validate([
            'id_testing_standard' => 'required|integer',
            'id_testing_matriks_sample' => 'required|integer',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'nomor_halaman' => 'nullable|string|max:50',
            'attachment' => 'nullable|string|max:255',
            'attachments.*' => 'nullable|file|max:153600',
            'keterangan_point' => 'nullable|string',
            'is_aktif' => 'required|boolean',
        ], $this->pointMessages());
        unset($validated['attachments']); // wildcard rule ikut masuk ke $validated, tapi kolom DB-nya 'attachment' (singular)

        // Validasi terpisah untuk field per-item (dynamic table) — sengaja
        // TIDAK digabung ke $validated di atas (yang di-spread ke update
        // testing_points) supaya tidak kena bug yang sama seperti kasus
        // 'attachments' (field array ikut nyasar ke kolom yang salah).
        $request->validate([
            'id_testing_item.*' => 'nullable|integer',
            'judul_indonesia.*' => 'nullable|string|max:255',
            'judul_inggris.*'   => 'nullable|string|max:255',
            'parameter.*'       => 'nullable|integer',
            'unit.*'            => 'nullable|integer',
            'nilai.*'           => 'nullable|string|max:100',
            'keterangan.*'      => 'nullable|string|max:255',
            'nomor.*'           => 'nullable|integer',
        ]);

        $keepRows = $this->itemRowsToSave($request, 'keterangan', true);

        // Capture BEFORE state (point + lines)
        $beforePoint = (array) DB::table('testing_points')->where('id_testing_point', $id)->first();
        $beforeLines = DB::table('testing_items')->where('id_testing_point', $id)->get()->map(fn($r) => (array)$r)->toArray();
        $beforePoint['_lines'] = $beforeLines;

        DB::beginTransaction();

        try {

            $ids            = $request->id_testing_item ?? [];
            $judul_indonesia = $request->judul_indonesia ?? [];
            $judul_inggris  = $request->judul_inggris ?? [];
            $parameter      = $request->parameter ?? [];
            $unit           = $request->unit ?? [];
            $nilai          = $request->nilai ?? [];
            // Field di form edit (testing-points/form.js) bernama "keterangan[]",
            // BEDA dari form create yang pakai "item_keterangan[]" — sebelumnya
            // di sini salah baca "item_keterangan" (selalu kosong di form edit),
            // jadi keterangan tiap item selalu ke-null-kan tiap kali Simpan.
            $keterangan     = $request->keterangan ?? [];
            $status         = $request->status ?? [];
            $nomor          = $request->nomor ?? [];

            $existingIds = DB::table('testing_items')
                ->where('id_testing_point', $id)
                ->pluck('id_testing_item')
                ->toArray();

            $currentIds = [];

            foreach ($judul_indonesia as $i => $val) {
                if (!in_array($i, $keepRows, true)) continue; // baris kosong sama sekali

                $itemId = $ids[$i] ?? null;

                $dataItem = [
                    'id_testing_point'   => $id,
                    'nomor'              => (int) ($nomor[$i] ?? ($i + 1)),
                    'judul_indonesia'    => $judul_indonesia[$i] ?? null,
                    'judul_inggris'      => $judul_inggris[$i] ?? null,
                    'id_testing_parameter' => $parameter[$i] ?? null,
                    'id_testing_unit'    => $unit[$i] ?? null,
                    'nilai'              => $nilai[$i] ?? null,
                    'keterangan'         => $keterangan[$i] ?? null,
                    'is_aktif'           => isset($status[$i]) ? $status[$i] : 0,
                    'updated_at'         => now(),
                ];

                if ($itemId) {
                    DB::table('testing_items')->where('id_testing_item', $itemId)->update($dataItem);
                    $currentIds[] = $itemId;
                } else {
                    $newId = DB::table('testing_items')->insertGetId([...$dataItem, 'created_at' => now()]);
                    $currentIds[] = $newId;
                }
            }

            $idsToDelete = array_diff($existingIds, $currentIds);
            if (!empty($idsToDelete)) {
                DB::table('testing_items')->whereIn('id_testing_item', $idsToDelete)->delete();
                // Item yang dihapus dari induk ikut lepas dari semua Paket
                TestingPointPaketController::lepasItem($idsToDelete);
            }

            $existing = $request->existing_attachments ?? [];
            $newFiles = [];
            if ($request->hasFile('attachments')) {
                $upload   = uploadAttachment($request->file('attachments'), 'testing_points');
                $newFiles = $upload['files'];
            }

            $attachments = array_merge($existing, $newFiles);
            $validated['attachment'] = json_encode($attachments);

            $validated['keterangan'] = $validated['keterangan_point'] ?? null;
            unset($validated['keterangan_point']);

            DB::table('testing_points')
                ->where('id_testing_point', $id)
                ->update([...$validated, 'updated_at' => now()]);

            // Capture AFTER state (point + lines)
            $afterPoint = (array) DB::table('testing_points')->where('id_testing_point', $id)->first();
            $afterLines = DB::table('testing_items')->where('id_testing_point', $id)->get()->map(fn($r) => (array)$r)->toArray();
            $afterPoint['_lines'] = $afterLines;

            saveAudit('testing_points', $id, 'update', json_encode($beforePoint), json_encode($afterPoint));

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server'], 500);
        }

        return response()->json(['success' => true]);
    }

    // Clone Testing Point ("Salin Testing Point ini") — full control: user
    // bebas edit field Informasi & baris Testing Items (pre-filled dari
    // sumber) sebelum disimpan sebagai record baru yang independen. Tidak ada
    // field yang dikunci ikut sumber (beda dari Clone WO/FWO yang terikat
    // parent) karena Testing Point bukan child dari record lain. Attachment
    // sengaja TIDAK ikut disalin (pola sama seperti WorkOrderController /
    // FieldworkController::duplicate()) — record baru mulai bersih.
    public function duplicate(Request $request, $id)
    {
        $source = DB::table('testing_points')->where('id_testing_point', $id)->whereNull('deleted_at')->first();
        if (!$source) {
            return response()->json(['message' => 'Testing Point sumber tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'id_testing_standard' => 'required|integer',
            'id_testing_matriks_sample' => 'required|integer',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'nomor_halaman' => 'nullable|string|max:50',
            'keterangan' => 'nullable|string',
            'is_aktif' => 'required|boolean',
        ], $this->pointMessages());

        // Validasi terpisah untuk field per-item, sama seperti store() — tidak
        // digabung ke $validated supaya tidak ikut ke-spread ke insert
        // testing_points.
        $request->validate([
            'judul_indonesia.*' => 'nullable|string|max:255',
            'judul_inggris.*'   => 'nullable|string|max:255',
            'parameter.*'       => 'nullable|integer',
            'unit.*'            => 'nullable|integer',
            'nilai.*'           => 'nullable|string|max:100',
            'item_keterangan.*' => 'nullable|string|max:255',
        ]);

        // Keunikan Testing Point = kombinasi Standard + Matriks Sample + Nama
        // (tidak ada kolom kode sendiri) — cek dulu sebelum insert supaya
        // hasil clone (setelah diedit user) tidak bentrok sama data lain.
        $duplicateExists = DB::table('testing_points')
            ->where('id_testing_standard', $validated['id_testing_standard'])
            ->where('id_testing_matriks_sample', $validated['id_testing_matriks_sample'])
            ->where('nama', $validated['nama'])
            ->whereNull('deleted_at')
            ->exists();
        if ($duplicateExists) {
            return response()->json([
                'message' => 'Testing Point dengan kombinasi Standard + Matriks Sample + Nama ini sudah ada',
            ], 422);
        }

        $keepRows = $this->itemRowsToSave($request, 'item_keterangan');

        DB::beginTransaction();

        try {
            $newId = DB::table('testing_points')->insertGetId([
                ...$validated,
                'attachment'  => null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $judul_indonesia = $request->judul_indonesia ?? [];
            $judul_inggris   = $request->judul_inggris   ?? [];
            $parameter       = $request->parameter       ?? [];
            $unit            = $request->unit            ?? [];
            $nilai           = $request->nilai           ?? [];
            $item_keterangan = $request->item_keterangan ?? [];
            $status          = $request->status          ?? [];
            $nomor           = $request->nomor           ?? [];

            foreach ($judul_indonesia as $i => $val) {
                if (!in_array($i, $keepRows, true)) continue;
                DB::table('testing_items')->insert([
                    'id_testing_point'     => $newId,
                    'nomor'                => (int) ($nomor[$i] ?? ($i + 1)),
                    'judul_indonesia'      => $val,
                    'judul_inggris'        => $judul_inggris[$i]   ?? null,
                    'id_testing_parameter' => $parameter[$i]       ?? null,
                    'id_testing_unit'      => $unit[$i]            ?? null,
                    'nilai'                => $nilai[$i]           ?? null,
                    'keterangan'           => $item_keterangan[$i] ?? null,
                    'is_aktif'             => isset($status[$i]) ? $status[$i] : 0,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            }

            $after = DB::table('testing_points')->where('id_testing_point', $newId)->get()->toJson();
            saveAudit('testing_points', $newId, 'Create', '', $after);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Testing Point berhasil disalin',
            'id'      => $newId,
        ]);
    }

    public function destroy($id)
    {
        $before = DB::table('testing_points')->where('id_testing_point', $id)->get()->toJson();
        DB::table('testing_points')->where('id_testing_point', $id)->update(['deleted_at' => now()]);
        TestingPointPaketController::hapusSemua($id); // Paket ikut dihapus bersama Testing Point-nya
        $after = DB::table('testing_points')->where('id_testing_point', $id)->get()->toJson();
        saveAudit('testing_points', $id, 'delete', $before, $after);

        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }


}
