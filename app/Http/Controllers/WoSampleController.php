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

    public function boqByWo($id_wo)
    {
        $wo = DB::table('work_orders')->where('id_wo', $id_wo)->first(['status']);
        if (!$wo) return response()->json(['message' => 'WO tidak ditemukan'], 404);

        $boqs = DB::table('boq as b')
            ->leftJoin('testing_points as tp', 'tp.id_testing_point', '=', 'b.id_testing_point')
            ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
            ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
            ->where('b.id_wo', $id_wo)
            ->whereNull('b.deleted_at')
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
                ->orderBy('no_urut')
                ->get();
        }

        return response()->json([
            'data'      => $boqs,
            'wo_status' => $wo->status,
        ]);
    }

    public function show($id)
    {
        $sample = DB::table('lab_samples')->where('id_lab_sample', $id)->first();
        if (!$sample) return response()->json(['message' => 'Tidak ditemukan'], 404);
        return response()->json(['data' => $sample]);
    }

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
        $noSample = $this->nextNoSample();

        $id = DB::table('lab_samples')->insertGetId([
            'id_fwo'     => null,
            'id_fwo_boq' => null,
            'id_wo'      => $id_wo,
            'id_boq'     => $id_boq,
            'no_urut'    => $maxUrut + 1,
            'no_sample'  => $noSample,
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

        $allowed = ['titik_lokasi', 'no_sample', 'jenis_sample', 'kondisi_sample', 'status', 'tanggal_pengambilan', 'keterangan'];
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
            'no_sample'           => 'nullable|string|max:100',
            'tanggal_pengambilan' => 'nullable|date',
            'titik_lokasi'        => 'nullable|string|max:255',
            'kondisi_sample'      => 'nullable|in:baik,rusak,tidak_lengkap',
            'status'              => 'required|in:belum_diambil,diambil,dikirim',
            'keterangan'          => 'nullable|string',
        ]);

        $before = DB::table('lab_samples')->where('id_lab_sample', $id)->get()->toJson();

        DB::table('lab_samples')->where('id_lab_sample', $id)->update([
            'jenis_sample'        => $request->jenis_sample ?: null,
            'no_sample'           => $request->no_sample ?: null,
            'tanggal_pengambilan' => $request->tanggal_pengambilan ?: null,
            'titik_lokasi'        => $request->titik_lokasi ?: null,
            'kondisi_sample'      => $request->kondisi_sample ?: null,
            'status'              => $request->status,
            'keterangan'          => $request->keterangan ?: null,
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
