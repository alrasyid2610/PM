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
            ->where('fb.id_fwo', $id_fwo)
            ->whereNull('fb.deleted_at')
            ->get([
                'fb.id_fwo_boq',
                'fb.qty',
                DB::raw("COALESCE(tp.nama, b.item_produk_alternate) as nama_boq"),
            ]);

        foreach ($boqs as $boq) {
            $boq->samples = DB::table('lab_samples')
                ->where('id_fwo_boq', $boq->id_fwo_boq)
                ->orderBy('no_urut')
                ->get();
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

        DB::table('lab_samples')->where('id_fwo_boq', $id_fwo_boq)->update($update);

        return response()->json(['success' => true]);
    }

    public function updateField(Request $request, $id)
    {
        $sample = DB::table('lab_samples')->where('id_lab_sample', $id)->first();
        if (!$sample) return response()->json(['message' => 'Tidak ditemukan'], 404);

        if ($this->fwoIsCompleted($sample->id_fwo)) {
            return response()->json(['success' => false, 'message' => 'FWO sudah selesai.'], 403);
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

        if ($this->fwoIsCompleted($sample->id_fwo)) {
            return response()->json(['success' => false, 'message' => 'FWO sudah selesai, tidak dapat mengubah sample.'], 403);
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
}
