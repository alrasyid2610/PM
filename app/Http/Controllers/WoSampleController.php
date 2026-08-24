<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasAuditHistory;

/**
 * Sample yang dibuat langsung di level WO, tanpa perlu FWO. Terikat ke BOQ
 * milik WO (`boq.id_wo`), bukan `fieldwork_boq`. Sisa qty dihitung independen
 * dari pemakaian FWO (lihat LabSampleController untuk jalur FWO yang lama).
 */
class WoSampleController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'lab_samples'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_lab_sample']; }

    private function woIsCompleted($id_wo): bool
    {
        $wo = DB::table('work_orders')->where('id_wo', $id_wo)->first(['status']);
        return $wo && $wo->status === 'completed';
    }

    private function nextNoSample(): string
    {
        $year = date('y');
        $prefix = "Lab.{$year}.";
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

    // Nomor registrasi lab — terpisah dari no_sample (nomor sampling lapangan).
    // Dibuat begitu sample "diterima" oleh bagian lab, lewat Lab Sample Reg.
    private function nextNoRegLab(): string
    {
        $year = date('y');
        $prefix = "Reg.{$year}.";
        $last = DB::table('lab_samples')
            ->where('no_reg_lab', 'like', "{$prefix}%")
            ->orderByRaw('CAST(SUBSTRING(no_reg_lab, ? + 1) AS UNSIGNED) DESC', [strlen($prefix)])
            ->value('no_reg_lab');

        $next = 1;
        if ($last) {
            $num = (int) substr($last, strlen($prefix));
            $next = $num + 1;
        }

        return $prefix . $next;
    }

    /**
     * Cari event registrasi (wo_lab_sample_regs) yang sudah ada untuk WO ini
     * dengan tanggal_diterima + penerima yang sama — kalau ada, sample baru
     * digabung ke situ (bukan bikin event baru), supaya tidak numpuk jadi
     * banyak grup terpisah kalau user registrasi beberapa kali di hari &
     * penerima yang sama (dikonfirmasi user 2026-08-24).
     */
    private function findOrCreateRegHeader($id_wo, array $validated): int
    {
        $query = DB::table('wo_lab_sample_regs')
            ->where('id_wo', $id_wo)
            ->where('tanggal_diterima', $validated['tanggal_diterima']);

        if (!empty($validated['id_personnel_penerima'])) {
            $query->where('id_personnel_penerima', $validated['id_personnel_penerima']);
        } else {
            $query->whereNull('id_personnel_penerima');
        }

        $existing = $query->orderByDesc('id_wo_lab_sample_reg')->first();
        if ($existing) {
            return $existing->id_wo_lab_sample_reg;
        }

        return DB::table('wo_lab_sample_regs')->insertGetId([
            'id_wo'                 => $id_wo,
            'tanggal_diterima'      => $validated['tanggal_diterima'],
            'id_personnel_penerima' => $validated['id_personnel_penerima'] ?? null,
            'keterangan'            => $validated['keterangan'] ?? null,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
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

    public function boqByWo($id_wo)
    {
        $wo = DB::table('work_orders')->where('id_wo', $id_wo)->first(['status', 'id_site_pelanggan_pekerjaan']);
        if (!$wo) return response()->json(['message' => 'WO tidak ditemukan'], 404);

        $hasFwo = DB::table('fieldworks')->where('id_wo', $id_wo)->whereNull('deleted_at')->exists();

        $response = [
            'wo_status' => $wo->status,
            'id_site'   => $wo->id_site_pelanggan_pekerjaan,
            'has_fwo'   => $hasFwo,
            'regs'      => $this->regGroupsByWo($id_wo), // sample yang sudah diregistrasi — selalu ditampilkan kalau ada
        ];

        // BOQ item yang belum PERNAH dipakai di FWO manapun tetap bisa dibuatkan
        // sample langsung (jalur b) — gating-nya per BOQ item, bukan per WO,
        // supaya WO campuran (sebagian BOQ disampling via FWO, sebagian tidak)
        // tetap bisa jalan dua-duanya.
        $boqIdsWithFwo = DB::table('fieldwork_boq as fb')
            ->join('fieldworks as fw', 'fw.id_fwo', '=', 'fb.id_fwo')
            ->where('fw.id_wo', $id_wo)
            ->whereNull('fw.deleted_at')
            ->whereNull('fb.deleted_at')
            ->pluck('fb.id_boq')
            ->unique()
            ->all();

        $boqs = DB::table('boq as b')
            ->leftJoin('testing_points as tp', 'tp.id_testing_point', '=', 'b.id_testing_point')
            ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
            ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
            ->where('b.id_wo', $id_wo)
            ->whereNull('b.deleted_at')
            ->whereNotIn('b.id_boq', $boqIdsWithFwo)
            ->select([
                'b.id_boq', 'b.qty',
                DB::raw("COALESCE(TRIM(CONCAT_WS(' ', NULLIF(tms.judul_indonesia,''), NULLIF(ts.nomor,''), NULLIF(tp.nama,''))), b.item_produk_alternate) as nama_boq"),
            ])
            ->orderBy('b.id_boq')
            ->get();

        foreach ($boqs as $boq) {
            $used = DB::table('lab_samples')->where('id_boq', $boq->id_boq)->whereNull('id_fwo_boq')->count();
            $boq->sisa    = max(0, (int) $boq->qty - $used);
            $boq->samples = DB::table('lab_samples')
                ->where('id_wo', $id_wo)
                ->where('id_boq', $boq->id_boq)
                ->whereNull('id_wo_lab_sample_reg') // yang sudah diregistrasi sudah muncul di 'regs'
                ->orderBy('no_urut')
                ->get();
            foreach ($boq->samples as $sample) {
                $sample->attachments = $sample->attachments ? json_decode($sample->attachments) : [];
            }
        }

        $response['boqs'] = $boqs;

        return response()->json($response);
    }

    /**
     * List sample yang sudah diregistrasi lab, dikelompokkan per event
     * registrasi (1 wo_lab_sample_regs = 1 grup). Dipakai untuk WO manapun
     * (baik jalur FWO maupun WO-langsung).
     */
    private function regGroupsByWo($id_wo): array
    {
        $fwoBoqNames = DB::table('fieldwork_boq as fb')
            ->join('fieldworks as fw', 'fw.id_fwo', '=', 'fb.id_fwo')
            ->join('boq as b', 'b.id_boq', '=', 'fb.id_boq')
            ->leftJoin('testing_points as tp', 'tp.id_testing_point', '=', 'b.id_testing_point')
            ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
            ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
            ->where('fw.id_wo', $id_wo)
            ->select([
                'fb.id_fwo_boq',
                DB::raw("COALESCE(TRIM(CONCAT_WS(' ', NULLIF(tms.judul_indonesia,''), NULLIF(ts.nomor,''), NULLIF(tp.nama,''))), b.item_produk_alternate) as nama_boq"),
            ])
            ->get()
            ->pluck('nama_boq', 'id_fwo_boq');

        $woBoqNames = DB::table('boq as b')
            ->leftJoin('testing_points as tp', 'tp.id_testing_point', '=', 'b.id_testing_point')
            ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
            ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
            ->where('b.id_wo', $id_wo)
            ->select([
                'b.id_boq',
                DB::raw("COALESCE(TRIM(CONCAT_WS(' ', NULLIF(tms.judul_indonesia,''), NULLIF(ts.nomor,''), NULLIF(tp.nama,''))), b.item_produk_alternate) as nama_boq"),
            ])
            ->get()
            ->pluck('nama_boq', 'id_boq');

        $regs = DB::table('wo_lab_sample_regs as r')
            ->leftJoin('personnel as p', 'p.id_personnel', '=', 'r.id_personnel_penerima')
            ->where('r.id_wo', $id_wo)
            ->orderByDesc('r.id_wo_lab_sample_reg')
            ->get(['r.id_wo_lab_sample_reg', 'r.tanggal_diterima', 'r.keterangan', 'p.nama as nama_personnel']);

        foreach ($regs as $reg) {
            $samples = DB::table('lab_samples')
                ->where('id_wo_lab_sample_reg', $reg->id_wo_lab_sample_reg)
                ->orderBy('no_urut')
                ->get();

            foreach ($samples as $s) {
                $s->attachments = $s->attachments ? json_decode($s->attachments) : [];
                $s->nama_boq = $s->id_fwo_boq
                    ? ($fwoBoqNames[$s->id_fwo_boq] ?? '-')
                    : ($woBoqNames[$s->id_boq] ?? '-');
            }

            $reg->samples = $samples;
        }

        return $regs->all();
    }

    /**
     * List sample yang belum "diterima" lab (belum punya id_wo_lab_sample_reg)
     * untuk WO ini — baik yang berasal dari FWO maupun dari BOQ WO-langsung.
     */
    public function availableFwoSamples($id_wo)
    {
        $fwoIds = DB::table('fieldworks')
            ->where('id_wo', $id_wo)
            ->whereNull('deleted_at')
            ->pluck('id_fwo');

        $groups = [];

        // Pool dari FWO
        $fwoBoqs = DB::table('fieldwork_boq as fb')
            ->join('boq as b', 'b.id_boq', '=', 'fb.id_boq')
            ->join('fieldworks as fw', 'fw.id_fwo', '=', 'fb.id_fwo')
            ->leftJoin('testing_points as tp', 'tp.id_testing_point', '=', 'b.id_testing_point')
            ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
            ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
            ->whereIn('fb.id_fwo', $fwoIds)
            ->whereNull('fb.deleted_at')
            ->select([
                'fb.id_fwo_boq',
                DB::raw("CONCAT('FWO ', fw.no_fwo, ' — ', COALESCE(TRIM(CONCAT_WS(' ', NULLIF(tms.judul_indonesia,''), NULLIF(ts.nomor,''), NULLIF(tp.nama,''))), b.item_produk_alternate)) as label"),
            ])
            ->orderBy('fw.no_fwo')
            ->get();

        foreach ($fwoBoqs as $boq) {
            $samples = DB::table('lab_samples')
                ->where('id_fwo_boq', $boq->id_fwo_boq)
                ->whereNull('id_wo_lab_sample_reg')
                ->orderBy('no_urut')
                ->get(['id_lab_sample', 'no_sample', 'jenis_sample', 'titik_lokasi', 'status']);

            if ($samples->isEmpty()) continue;
            $groups[] = ['label' => $boq->label, 'samples' => $samples];
        }

        // Pool dari BOQ WO-langsung (jalur b — sample yang sudah dibuat tapi belum diregistrasi)
        $directBoqs = DB::table('boq as b')
            ->leftJoin('testing_points as tp', 'tp.id_testing_point', '=', 'b.id_testing_point')
            ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
            ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
            ->where('b.id_wo', $id_wo)
            ->whereNull('b.deleted_at')
            ->select([
                'b.id_boq',
                DB::raw("CONCAT('BOQ WO — ', COALESCE(TRIM(CONCAT_WS(' ', NULLIF(tms.judul_indonesia,''), NULLIF(ts.nomor,''), NULLIF(tp.nama,''))), b.item_produk_alternate)) as label"),
            ])
            ->get();

        foreach ($directBoqs as $boq) {
            $samples = DB::table('lab_samples')
                ->where('id_wo', $id_wo)
                ->where('id_boq', $boq->id_boq)
                ->whereNull('id_wo_lab_sample_reg')
                ->orderBy('no_urut')
                ->get(['id_lab_sample', 'no_sample', 'jenis_sample', 'titik_lokasi', 'status']);

            if ($samples->isEmpty()) continue;
            $groups[] = ['label' => $boq->label, 'samples' => $samples];
        }

        return response()->json(['data' => $groups]);
    }

    /**
     * Tarik sample yang sudah dipilih (dari FWO ATAU dari BOQ WO-langsung,
     * keduanya milik WO ini) ke dalam 1 event registrasi lab — assign
     * no_reg_lab + status_lab.
     */
    public function pullFromFwo(Request $request, $id_wo)
    {
        if ($this->woIsCompleted($id_wo)) {
            return response()->json(['success' => false, 'message' => 'WO sudah selesai.'], 403);
        }

        $validated = $request->validate([
            'id_lab_sample'          => 'required|array|min:1',
            'id_lab_sample.*'        => 'integer',
            'tanggal_diterima'       => 'required|date',
            'id_personnel_penerima'  => 'nullable|integer',
            'keterangan'             => 'nullable|string',
        ]);

        // Pastikan semua sample yang dipilih benar milik WO ini (FWO ATAU BOQ WO-langsung) & belum diregistrasi
        $fwoIds = DB::table('fieldworks')->where('id_wo', $id_wo)->whereNull('deleted_at')->pluck('id_fwo');
        $validCount = DB::table('lab_samples')
            ->whereIn('id_lab_sample', $validated['id_lab_sample'])
            ->where(function ($q) use ($fwoIds, $id_wo) {
                $q->whereIn('id_fwo', $fwoIds)->orWhere('id_wo', $id_wo);
            })
            ->whereNull('id_wo_lab_sample_reg')
            ->count();

        if ($validCount !== count($validated['id_lab_sample'])) {
            return response()->json(['success' => false, 'message' => 'Sebagian sample tidak valid atau sudah diregistrasi sebelumnya.'], 422);
        }

        DB::beginTransaction();
        try {
            $idReg = $this->findOrCreateRegHeader($id_wo, $validated);

            foreach ($validated['id_lab_sample'] as $idSample) {
                DB::table('lab_samples')->where('id_lab_sample', $idSample)->update([
                    'id_wo_lab_sample_reg' => $idReg,
                    'no_reg_lab'           => $this->nextNoRegLab(),
                    'status_lab'           => 'diterima',
                    'updated_at'           => now(),
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server'], 500);
        }

        return response()->json(['success' => true, 'id_wo_lab_sample_reg' => $idReg]);
    }

    /**
     * Registrasi sample ad-hoc yang tidak berasal dari BOQ manapun (WO maupun
     * FWO) — mis. sample dibawa langsung oleh pelanggan tanpa proses sampling
     * lapangan. Create + register jadi 1 langkah sekaligus, karena tidak ada
     * tahap "sudah diambil tapi belum diregistrasi" untuk kasus ini.
     */
    public function storeWithoutBoq(Request $request, $id_wo)
    {
        if ($this->woIsCompleted($id_wo)) {
            return response()->json(['success' => false, 'message' => 'WO sudah selesai.'], 403);
        }

        $validated = $request->validate([
            'jenis_sample'          => 'nullable|in:env,we,mp,product',
            'titik_lokasi'          => 'nullable|string|max:255',
            'tanggal_pengambilan'   => 'nullable|date',
            'kondisi_sample'        => 'nullable|in:baik,rusak,tidak_lengkap',
            'keterangan_sample'     => 'nullable|string',
            'tanggal_diterima'      => 'required|date',
            'id_personnel_penerima' => 'nullable|integer',
            'keterangan'            => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $idReg = $this->findOrCreateRegHeader($id_wo, $validated);

            $maxUrut = (int) (DB::table('lab_samples')->where('id_wo', $id_wo)->whereNull('id_boq')->max('no_urut') ?? 0);

            $idSample = DB::table('lab_samples')->insertGetId([
                'id_fwo'               => null,
                'id_fwo_boq'           => null,
                'id_wo'                => $id_wo,
                'id_boq'               => null,
                'id_wo_lab_sample_reg' => $idReg,
                'no_urut'              => $maxUrut + 1,
                'jenis_sample'         => $validated['jenis_sample'] ?? null,
                'no_sample'            => $this->nextNoSample(),
                'no_reg_lab'           => $this->nextNoRegLab(),
                'tanggal_pengambilan'  => $validated['tanggal_pengambilan'] ?? null,
                'titik_lokasi'         => $validated['titik_lokasi'] ?? null,
                'kondisi_sample'       => $validated['kondisi_sample'] ?? null,
                'keterangan'           => $validated['keterangan_sample'] ?? null,
                'status'               => 'belum_diambil',
                'status_lab'           => 'diterima',
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server'], 500);
        }

        return response()->json(['success' => true, 'id_lab_sample' => $idSample]);
    }

    public function show($id)
    {
        $sample = DB::table('lab_samples')->where('id_lab_sample', $id)->first();
        if (!$sample) return response()->json(['message' => 'Tidak ditemukan'], 404);
        $sample->attachments = $sample->attachments ? json_decode($sample->attachments) : [];
        return response()->json(['data' => $sample]);
    }

    /**
     * Jalur (b) Lab Sample Reg: dipakai kalau WO tidak punya FWO sama sekali.
     * Cuma bikin sample-nya dulu (belum "diterima" lab) — registrasinya lewat
     * langkah terpisah (pullFromFwo, sekarang men-generalisasi juga sample
     * WO-langsung, bukan cuma FWO).
     */
    public function generateSlots($id_wo, $id_boq)
    {
        if ($this->woIsCompleted($id_wo)) {
            return response()->json(['success' => false, 'message' => 'WO sudah selesai.'], 403);
        }

        $boq = DB::table('boq')->where('id_boq', $id_boq)->where('id_wo', $id_wo)->whereNull('deleted_at')->first();
        if (!$boq) return response()->json(['message' => 'BOQ tidak ditemukan'], 404);

        $used = DB::table('lab_samples')->where('id_boq', $id_boq)->whereNull('id_fwo_boq')->count();
        $sisa = max(0, (int) $boq->qty - $used);
        if ($sisa <= 0) {
            return response()->json(['success' => false, 'message' => "Qty BOQ sudah terpenuhi ({$boq->qty})."], 422);
        }

        $maxUrut = (int) (DB::table('lab_samples')->where('id_wo', $id_wo)->where('id_boq', $id_boq)->max('no_urut') ?? 0);

        for ($i = 1; $i <= $sisa; $i++) {
            DB::table('lab_samples')->insert([
                'id_fwo'     => null,
                'id_fwo_boq' => null,
                'id_wo'      => $id_wo,
                'id_boq'     => $id_boq,
                'no_urut'    => $maxUrut + $i,
                'no_sample'  => $this->nextNoSample(),
                'status'     => 'belum_diambil',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Slot berhasil dibuat.']);
    }

    public function addOne($id_wo, $id_boq)
    {
        if ($this->woIsCompleted($id_wo)) {
            return response()->json(['success' => false, 'message' => 'WO sudah selesai.'], 403);
        }

        $boq = DB::table('boq')->where('id_boq', $id_boq)->where('id_wo', $id_wo)->whereNull('deleted_at')->first();
        if (!$boq) return response()->json(['message' => 'BOQ tidak ditemukan'], 404);

        $used = DB::table('lab_samples')->where('id_boq', $id_boq)->whereNull('id_fwo_boq')->count();
        if ($used >= (int) ($boq->qty ?? 0)) {
            return response()->json([
                'success' => false,
                'message' => "Jumlah sample sudah mencapai batas maksimum ({$boq->qty} sample sesuai qty BOQ).",
            ], 422);
        }

        $maxUrut  = (int) (DB::table('lab_samples')->where('id_wo', $id_wo)->where('id_boq', $id_boq)->max('no_urut') ?? 0);

        $id = DB::table('lab_samples')->insertGetId([
            'id_fwo'     => null,
            'id_fwo_boq' => null,
            'id_wo'      => $id_wo,
            'id_boq'     => $id_boq,
            'no_urut'    => $maxUrut + 1,
            'no_sample'  => $this->nextNoSample(),
            'status'     => 'belum_diambil',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'id_lab_sample' => $id]);
    }

    public function bulkFill(Request $request, $id_wo, $id_boq)
    {
        if ($this->woIsCompleted($id_wo)) {
            return response()->json(['success' => false, 'message' => 'WO sudah selesai.'], 403);
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

        DB::table('lab_samples')->where('id_wo', $id_wo)->where('id_boq', $id_boq)->update($update);

        return response()->json(['success' => true]);
    }

    public function updateField(Request $request, $id)
    {
        $sample = DB::table('lab_samples')->where('id_lab_sample', $id)->first();
        if (!$sample) return response()->json(['message' => 'Tidak ditemukan'], 404);

        if ($this->woIsCompleted($sample->id_wo)) {
            return response()->json(['success' => false, 'message' => 'WO sudah selesai.'], 403);
        }

        $allowed = ['titik_lokasi', 'jenis_sample', 'kondisi_sample', 'status', 'tanggal_pengambilan', 'keterangan'];
        $field   = $request->input('field');

        if (!in_array($field, $allowed)) {
            return response()->json(['message' => 'Field tidak valid.'], 422);
        }

        DB::table('lab_samples')->where('id_lab_sample', $id)->update([
            $field       => $request->input('value') ?: null,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, $id)
    {
        $sample = DB::table('lab_samples')->where('id_lab_sample', $id)->first();
        if (!$sample) return response()->json(['message' => 'Tidak ditemukan'], 404);

        if ($this->woIsCompleted($sample->id_wo)) {
            return response()->json(['success' => false, 'message' => 'WO sudah selesai, tidak dapat mengubah sample.'], 403);
        }

        $request->validate([
            'jenis_sample'        => 'nullable|in:env,we,mp,product',
            'tanggal_pengambilan' => 'nullable|date',
            'titik_lokasi'        => 'nullable|string|max:255',
            'kondisi_sample'      => 'nullable|in:baik,rusak,tidak_lengkap',
            'status'              => 'required|in:belum_diambil,diambil,dikirim',
            'keterangan'          => 'nullable|string',
            'attachments.*'       => 'nullable|file|max:10240',
        ]);

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
            'keterangan'          => $request->keterangan ?: null,
            'attachments'         => $allFiles ? json_encode($allFiles) : null,
            'updated_at'          => now(),
        ]);

        $after = DB::table('lab_samples')->where('id_lab_sample', $id)->get()->toJson();
        saveAudit('lab_samples', $id, 'Update', $before, $after);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $sample = DB::table('lab_samples')->where('id_lab_sample', $id)->first();
        if (!$sample) return response()->json(['message' => 'Tidak ditemukan'], 404);

        if ($this->woIsCompleted($sample->id_wo)) {
            return response()->json(['success' => false, 'message' => 'WO sudah selesai.'], 403);
        }

        DB::table('lab_samples')->where('id_lab_sample', $id)->delete();

        return response()->json(['success' => true]);
    }
}
