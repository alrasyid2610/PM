<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasAuditHistory;

class LabSampleController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'lab_samples'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_lab_sample']; }

    private function fwoIsCompleted($id_fwo): bool
    {
        $fwo = DB::table('fieldworks')->where('id_fwo', $id_fwo)->first(['status']);
        return $fwo && $fwo->status === 'completed';
    }

    private function nextNoSample(): string
    {
        $year = date('y'); // 2 digit, misal "26"
        $prefix = "FWOS.{$year}.";
        $last = DB::table('lab_samples')
            ->where('no_sample', 'like', "{$prefix}%")
            ->orderByRaw('CAST(SUBSTRING(no_sample, ? + 1) AS UNSIGNED) DESC', [strlen($prefix)])
            ->value('no_sample');

        $next = 1;
        if ($last) {
            $num = (int) substr($last, strlen($prefix));
            $next = $num + 1;
        }

        return $prefix . $next;
    }

    public function samplingPointsBySite($id_site)
    {
        $rows = DB::table('brs_sampling_points')
            ->where('id_site', $id_site)
            ->where('is_aktif', 1)
            ->whereNull('deleted_at')
            ->orderBy('jenis')
            ->orderBy('nama')
            ->get(['id_sp', 'jenis', 'kode', 'nama']);

        $results = $rows->map(fn($r) => [
            'id'   => $r->id_sp,
            'text' => "[{$r->jenis}] " . ($r->kode ? "{$r->kode} – " : '') . $r->nama,
        ]);

        return response()->json($results);
    }

    public function listByFwo($id_fwo)
    {
        $boqs = DB::table('fieldwork_boq as fb')
            ->join('boq as b', 'b.id_boq', '=', 'fb.id_boq')
            ->leftJoin('testing_points as tp', 'tp.id_testing_point', '=', 'b.id_testing_point')
            ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
            ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
            ->where('fb.id_fwo', $id_fwo)
            ->whereNull('fb.deleted_at')
            ->get([
                'fb.id_fwo_boq',
                'fb.qty',
                DB::raw("COALESCE(TRIM(CONCAT_WS(' ', NULLIF(tms.judul_indonesia,''), NULLIF(ts.nomor,''), NULLIF(tp.nama,''))), b.item_produk_alternate) as nama_boq"),
            ]);

        // Nama alasan tidak terlaksana — termasuk alasan yang sudah dinonaktifkan/dihapus lunak
        // (data lama tetap terbaca), jadi tidak difilter is_aktif/deleted_at.
        $alasanNama = DB::table('alasan_tidak_terlaksana')->pluck('nama', 'id_alasan');

        foreach ($boqs as $boq) {
            $boq->samples = DB::table('lab_samples')
                ->where('id_fwo_boq', $boq->id_fwo_boq)
                ->orderBy('no_urut')
                ->get();
            foreach ($boq->samples as $sample) {
                $sample->attachments = $sample->attachments ? json_decode($sample->attachments) : [];
                $sample->alasan_nama = $sample->id_alasan_tidak_terlaksana
                    ? ($alasanNama[$sample->id_alasan_tidak_terlaksana] ?? null)
                    : null;
            }
        }

        $fwo = DB::table('fieldworks')->where('id_fwo', $id_fwo)->first(['status', 'id_site_pelanggan_pekerjaan']);

        return response()->json([
            'data'       => $boqs,
            'fwo_status' => $fwo->status ?? null,
            'id_site'    => $fwo->id_site_pelanggan_pekerjaan ?? null,
        ]);
    }

    public function show($id)
    {
        $sample = DB::table('lab_samples')->where('id_lab_sample', $id)->first();
        if (!$sample) return response()->json(['message' => 'Tidak ditemukan'], 404);
        $sample->attachments = $sample->attachments ? json_decode($sample->attachments) : [];
        $sample->alasan_nama = $sample->id_alasan_tidak_terlaksana
            ? DB::table('alasan_tidak_terlaksana')->where('id_alasan', $sample->id_alasan_tidak_terlaksana)->value('nama')
            : null;
        return response()->json(['data' => $sample]);
    }

    // Generate otomatis slot yang belum ada hingga qty, dengan no_sample auto-format
    public function generateSlots(Request $request, $id_fwo_boq)
    {
        $boq = DB::table('fieldwork_boq')->where('id_fwo_boq', $id_fwo_boq)->first();
        if (!$boq) return response()->json(['message' => 'BOQ tidak ditemukan'], 404);

        if ($this->fwoIsCompleted($boq->id_fwo)) {
            return response()->json(['success' => false, 'message' => 'FWO sudah selesai.'], 403);
        }

        $existing = DB::table('lab_samples')
            ->where('id_fwo_boq', $id_fwo_boq)
            ->orderBy('no_urut')
            ->get();

        $existingUruts = $existing->pluck('no_urut')->toArray();
        $toCreate = [];

        for ($i = 1; $i <= $boq->qty; $i++) {
            if (!in_array($i, $existingUruts)) {
                $noSample = $this->nextNoSample();
                $toCreate[] = [
                    'id_fwo'     => $boq->id_fwo,
                    'id_fwo_boq' => $id_fwo_boq,
                    'no_urut'    => $i,
                    'no_sample'  => $noSample,
                    'status'     => 'belum_diambil',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                // Insert satu per satu agar nextNoSample() dapat sequence yang benar
                DB::table('lab_samples')->insert(end($toCreate));
                array_pop($toCreate);
            }
        }

        return response()->json(['success' => true, 'message' => 'Slot berhasil dibuat.']);
    }

    // Tambah 1 slot kosong dengan no_urut berikutnya
    public function addOne($id_fwo_boq)
    {
        $boq = DB::table('fieldwork_boq')->where('id_fwo_boq', $id_fwo_boq)->first();
        if (!$boq) return response()->json(['message' => 'BOQ tidak ditemukan'], 404);

        if ($this->fwoIsCompleted($boq->id_fwo)) {
            return response()->json(['success' => false, 'message' => 'FWO sudah selesai.'], 403);
        }

        $currentCount = DB::table('lab_samples')->where('id_fwo_boq', $id_fwo_boq)->count();
        if ($currentCount >= (int)($boq->qty ?? 0)) {
            return response()->json([
                'success' => false,
                'message' => "Jumlah sample sudah mencapai batas maksimum ({$boq->qty} sample sesuai qty BOQ).",
            ], 422);
        }

        $maxUrut  = DB::table('lab_samples')->where('id_fwo_boq', $id_fwo_boq)->max('no_urut') ?? 0;
        $nextUrut = $maxUrut + 1;
        $noSample = $this->nextNoSample();

        $id = DB::table('lab_samples')->insertGetId([
            'id_fwo'     => $boq->id_fwo,
            'id_fwo_boq' => $id_fwo_boq,
            'no_urut'    => $nextUrut,
            'no_sample'  => $noSample,
            'status'     => 'belum_diambil',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'id_lab_sample' => $id]);
    }

    public function destroy($id)
    {
        $sample = DB::table('lab_samples')->where('id_lab_sample', $id)->first();
        if (!$sample) return response()->json(['message' => 'Tidak ditemukan'], 404);

        if ($this->fwoIsCompleted($sample->id_fwo)) {
            return response()->json(['success' => false, 'message' => 'FWO sudah selesai.'], 403);
        }

        DB::table('lab_samples')->where('id_lab_sample', $id)->delete();

        return response()->json(['success' => true]);
    }

    public function bulkFill(Request $request, $id_fwo_boq)
    {
        $boq = DB::table('fieldwork_boq')->where('id_fwo_boq', $id_fwo_boq)->first();
        if (!$boq) return response()->json(['message' => 'BOQ tidak ditemukan'], 404);

        if ($this->fwoIsCompleted($boq->id_fwo)) {
            return response()->json(['success' => false, 'message' => 'FWO sudah selesai.'], 403);
        }

        $request->validate([
            'jenis_sample'        => 'nullable|in:env,we,mp,product',
            'tanggal_pengambilan' => 'nullable|date',
            'status'              => 'nullable|in:belum_diambil,diambil,dikirim',
            'kondisi_sample'      => 'nullable|in:baik,rusak,tidak_lengkap',
        ]);

        $update = ['updated_at' => now()];
        if ($request->filled('jenis_sample'))        $update['jenis_sample']        = $request->jenis_sample;
        if ($request->filled('tanggal_pengambilan')) $update['tanggal_pengambilan'] = $request->tanggal_pengambilan;
        if ($request->filled('status'))              $update['status']              = $request->status;
        if ($request->filled('kondisi_sample'))      $update['kondisi_sample']      = $request->kondisi_sample;

        if (count($update) <= 1) {
            return response()->json(['success' => false, 'message' => 'Tidak ada field yang diisi.'], 422);
        }

        // Slot Tidak Terlaksana tidak ikut di-overwrite (statusnya hasil keputusan lapangan + alasan)
        DB::table('lab_samples')
            ->where('id_fwo_boq', $id_fwo_boq)
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '!=', 'tidak_terlaksana'))
            ->update($update);

        return response()->json(['success' => true]);
    }

    public function updateField(Request $request, $id)
    {
        $sample = DB::table('lab_samples')->where('id_lab_sample', $id)->first();
        if (!$sample) return response()->json(['message' => 'Tidak ditemukan'], 404);

        if ($this->fwoIsCompleted($sample->id_fwo)) {
            return response()->json(['success' => false, 'message' => 'FWO sudah selesai.'], 403);
        }

        $allowed = ['titik_lokasi', 'jenis_sample', 'kondisi_sample', 'status', 'tanggal_pengambilan', 'keterangan'];
        $field   = $request->input('field');

        if (!in_array($field, $allowed)) {
            return response()->json(['message' => 'Field tidak valid.'], 422);
        }

        // Titik Sample sample Tidak Terlaksana tidak boleh dikosongkan
        if ($field === 'titik_lokasi' && $sample->status === 'tidak_terlaksana' && trim((string) $request->input('value')) === '') {
            return response()->json(['success' => false, 'message' => 'Titik Sample wajib diisi untuk sample Tidak Terlaksana.'], 422);
        }

        $update = [
            $field       => $request->input('value') ?: null,
            'updated_at' => now(),
        ];

        if ($field === 'status') {
            $value = $request->input('value');
            if ($value === 'tidak_terlaksana') {
                // Wajib lewat endpoint khusus supaya alasan ikut tercatat
                return response()->json(['message' => 'Status Tidak Terlaksana harus diisi beserta alasannya.'], 422);
            }
            // Status dikembalikan (boleh selama FWO belum selesai) → alasan lama dibuang
            $update['id_alasan_tidak_terlaksana'] = null;
        }

        DB::table('lab_samples')->where('id_lab_sample', $id)->update($update);

        return response()->json(['success' => true]);
    }

    /**
     * Cek isian "Tidak Terlaksana": alasan wajib & aktif, keterangan wajib kalau
     * alasan menandainya (wajib_keterangan), dan sample yang sudah diregistrasi di
     * Lab Sample Reg tidak boleh ditandai tidak terlaksana. Return pesan error / null.
     */
    private function tidakTerlaksanaError($sample, $idAlasan, $keterangan, $titik): ?string
    {
        if ($sample->id_wo_lab_sample_reg) {
            return 'Sample sudah diregistrasi di Lab Sample Reg, tidak bisa ditandai Tidak Terlaksana.';
        }
        // Titik Sample wajib: datanya tetap dipakai walau titik ini tidak terlaksana
        if (trim((string) $titik) === '') {
            return 'Titik Sample wajib diisi sebelum ditandai Tidak Terlaksana.';
        }

        $alasan = $idAlasan
            ? DB::table('alasan_tidak_terlaksana')->where('id_alasan', $idAlasan)->whereNull('deleted_at')->first()
            : null;

        if (!$alasan) {
            return 'Alasan wajib dipilih.';
        }
        // Alasan nonaktif hanya boleh dipertahankan kalau memang sudah tercatat di sample ini
        if (!$alasan->is_aktif && (int) $sample->id_alasan_tidak_terlaksana !== (int) $idAlasan) {
            return 'Alasan yang dipilih sudah tidak aktif.';
        }
        if ($alasan->wajib_keterangan && !trim((string) $keterangan)) {
            return "Keterangan wajib diisi untuk alasan \"{$alasan->nama}\".";
        }

        return null;
    }

    // Tandai 1 titik sample Tidak Terlaksana beserta alasan (master) + keterangan opsional
    public function tidakTerlaksana(Request $request, $id)
    {
        $sample = DB::table('lab_samples')->where('id_lab_sample', $id)->first();
        if (!$sample) return response()->json(['message' => 'Tidak ditemukan'], 404);

        if ($this->fwoIsCompleted($sample->id_fwo)) {
            return response()->json(['success' => false, 'message' => 'FWO sudah selesai.'], 403);
        }

        $request->validate([
            'id_alasan'  => 'required|integer',
            'keterangan' => 'nullable|string',
        ], [
            'id_alasan.required' => 'Alasan wajib dipilih.',
        ]);

        if ($msg = $this->tidakTerlaksanaError($sample, $request->id_alasan, $request->keterangan, $sample->titik_lokasi)) {
            return response()->json(['success' => false, 'message' => $msg], 422);
        }

        $before = DB::table('lab_samples')->where('id_lab_sample', $id)->get()->toJson();

        DB::table('lab_samples')->where('id_lab_sample', $id)->update([
            'status'                     => 'tidak_terlaksana',
            'id_alasan_tidak_terlaksana' => (int) $request->id_alasan,
            'keterangan'                 => $request->keterangan ?: $sample->keterangan,
            'updated_at'                 => now(),
        ]);

        $after = DB::table('lab_samples')->where('id_lab_sample', $id)->get()->toJson();
        saveAudit('lab_samples', $id, 'Update', $before, $after);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, $id)
    {
        $sample = DB::table('lab_samples')->where('id_lab_sample', $id)->first();
        if (!$sample) return response()->json(['message' => 'Tidak ditemukan'], 404);

        if ($this->fwoIsCompleted($sample->id_fwo)) {
            return response()->json(['success' => false, 'message' => 'FWO sudah selesai, tidak dapat mengubah sample.'], 403);
        }

        $request->validate([
            'jenis_sample'        => 'nullable|in:env,we,mp,product',
            'tanggal_pengambilan' => 'nullable|date',
            'titik_lokasi'        => 'nullable|string|max:255',
            'kondisi_sample'      => 'nullable|in:baik,rusak,tidak_lengkap',
            'status'              => 'required|in:belum_diambil,diambil,dikirim,tidak_terlaksana',
            'id_alasan_tidak_terlaksana' => 'nullable|integer',
            'keterangan'          => 'nullable|string',
            'attachments.*'       => 'nullable|file|max:153600',
        ]);

        $isTidakTerlaksana = $request->status === 'tidak_terlaksana';
        if ($isTidakTerlaksana) {
            if ($msg = $this->tidakTerlaksanaError($sample, $request->id_alasan_tidak_terlaksana, $request->keterangan, $request->titik_lokasi)) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
        }

        $existing = $request->input('existing_attachments', []);
        $newFiles = [];
        if ($request->hasFile('attachments')) {
            $upload   = uploadAttachment($request->file('attachments'), 'lab_samples');
            $newFiles = $upload['files'];
        }
        $allFiles = array_merge($existing, $newFiles);

        $before = DB::table('lab_samples')->where('id_lab_sample', $id)->get()->toJson();

        DB::table('lab_samples')->where('id_lab_sample', $id)->update([
            'jenis_sample'        => $request->jenis_sample ?: null,
            'tanggal_pengambilan' => $request->tanggal_pengambilan ?: null,
            'titik_lokasi'        => $request->titik_lokasi ?: null,
            'kondisi_sample'      => $request->kondisi_sample ?: null,
            'status'              => $request->status,
            'id_alasan_tidak_terlaksana' => $isTidakTerlaksana ? (int) $request->id_alasan_tidak_terlaksana : null,
            'keterangan'          => $request->keterangan ?: null,
            'attachments'         => $allFiles ? json_encode($allFiles) : null,
            'updated_at'          => now(),
        ]);

        $after = DB::table('lab_samples')->where('id_lab_sample', $id)->get()->toJson();
        saveAudit('lab_samples', $id, 'Update', $before, $after);

        return response()->json(['success' => true]);
    }
}
