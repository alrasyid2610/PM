<?php

namespace App\Http\Controllers;

use App\Support\OutputJudul;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OutputPekerjaanController extends Controller
{
    public function byWo($id_wo)
    {
        $outputs = DB::table('output_pekerjaan')
            ->where('id_wo', $id_wo)
            ->orderBy('id_output')
            ->get();

        // Sample FWO yang dicakup tiap LHU (satu query untuk semua output)
        $samples = $outputs->isEmpty() ? collect() : DB::table('output_pekerjaan_samples as os')
            ->join('lab_samples as ls', 'ls.id_lab_sample', '=', 'os.id_lab_sample')
            ->leftJoin('fieldworks as f', 'f.id_fwo', '=', 'ls.id_fwo')
            ->whereIn('os.id_output', $outputs->pluck('id_output'))
            ->orderBy('f.no_fwo')->orderBy('ls.no_sample')
            ->get(['os.id_output', 'ls.id_lab_sample', 'ls.no_sample', 'ls.no_reg_lab', 'ls.jenis_sample', 'ls.titik_lokasi', 'f.no_fwo'])
            ->groupBy('id_output');

        foreach ($outputs as $o) {
            $o->samples = ($samples[$o->id_output] ?? collect())->values();
        }

        return response()->json($outputs);
    }

    /**
     * Sample yang bisa dihubungkan ke LHU di WO ini: sample FWO di bawah WO yang SUDAH registrasi lab
     * (punya No. Reg Lab) dan bukan Tidak Terlaksana. Satu sample hanya boleh ke satu output, jadi sample
     * yang sudah dipakai output lain ikut dikirim (dengan judul outputnya) supaya UI menampilkannya nonaktif.
     */
    public function sampleOptions($id_wo)
    {
        $rows = DB::table('lab_samples as ls')
            ->join('fieldworks as f', 'f.id_fwo', '=', 'ls.id_fwo')
            ->leftJoin('output_pekerjaan_samples as os', 'os.id_lab_sample', '=', 'ls.id_lab_sample')
            ->leftJoin('output_pekerjaan as o', 'o.id_output', '=', 'os.id_output')
            ->where('f.id_wo', $id_wo)
            ->whereNull('f.deleted_at')
            ->whereNotNull('ls.no_reg_lab')->where('ls.no_reg_lab', '<>', '')
            ->where('ls.status', '<>', 'tidak_terlaksana')
            ->orderBy('f.no_fwo')->orderBy('ls.no_sample')
            ->get([
                'ls.id_lab_sample', 'ls.no_sample', 'ls.no_reg_lab', 'ls.jenis_sample', 'ls.titik_lokasi',
                'f.id_fwo', 'f.no_fwo',
                'os.id_output as id_output_terhubung', 'o.judul_output as judul_terhubung',
            ]);

        return response()->json($rows);
    }

    /**
     * ID sample dari $ids yang TIDAK boleh dihubungkan ke output (kosong = semua valid): bukan milik FWO di
     * bawah WO ini, belum registrasi lab, Tidak Terlaksana, atau sudah dipakai output lain ($exceptOutput = output ini).
     */
    private function invalidSampleIds(int $idWo, array $ids, ?int $exceptOutput): array
    {
        if (!$ids) return [];

        $valid = DB::table('lab_samples as ls')
            ->join('fieldworks as f', 'f.id_fwo', '=', 'ls.id_fwo')
            ->leftJoin('output_pekerjaan_samples as os', 'os.id_lab_sample', '=', 'ls.id_lab_sample')
            ->where('f.id_wo', $idWo)
            ->whereNull('f.deleted_at')
            ->whereNotNull('ls.no_reg_lab')->where('ls.no_reg_lab', '<>', '')
            ->where('ls.status', '<>', 'tidak_terlaksana')
            ->whereIn('ls.id_lab_sample', $ids)
            ->where(function ($q) use ($exceptOutput) {
                $q->whereNull('os.id_output');
                if ($exceptOutput) $q->orWhere('os.id_output', $exceptOutput);
            })
            ->pluck('ls.id_lab_sample')->map(fn($v) => (int) $v)->all();

        return array_values(array_diff($ids, $valid));
    }

    /** Samakan penghubung sample dengan pilihan terbaru (bukan LHU → semua penghubung dilepas). */
    private function syncSamples(int $idOutput, bool $isLhu, array $ids): void
    {
        $ids = $isLhu ? $ids : [];

        DB::table('output_pekerjaan_samples')->where('id_output', $idOutput)
            ->when($ids, fn($q) => $q->whereNotIn('id_lab_sample', $ids))
            ->delete();

        $ada = DB::table('output_pekerjaan_samples')->where('id_output', $idOutput)->pluck('id_lab_sample')->map(fn($v) => (int) $v)->all();
        foreach (array_diff($ids, $ada) as $idSample) {
            DB::table('output_pekerjaan_samples')->insert([
                'id_output' => $idOutput, 'id_lab_sample' => $idSample, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    /** Pilihan sample dari request → array int unik. */
    private function sampleIdsFromRequest(Request $request): array
    {
        return array_values(array_unique(array_map('intval', (array) $request->input('sample_ids', []))));
    }

    private function sampleError(array $invalid)
    {
        return response()->json([
            'message' => count($invalid) . ' sample tidak bisa dihubungkan (belum registrasi lab, bukan milik WO ini, Tidak Terlaksana, atau sudah dipakai output lain). Muat ulang pilihan sample lalu coba lagi.',
        ], 422);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_wo'          => 'required|integer',
            'judul_output'   => 'required|string|max:255',
            'judul_dokumen'  => 'nullable|string|max:255',
            'jenis_dokumen'  => 'nullable|in:copy,asli,asli_dan_copy',
            'qty_copy'       => 'nullable|integer|min:0',
            'qty_asli'       => 'nullable|integer|min:0',
            'link_drive'     => 'nullable|string|max:2048',
            'status'         => 'nullable|in:belum_siap,siap',
            'tanggal_mulai'  => 'nullable|date',
            'tanggal_selesai'=> 'nullable|date|after_or_equal:tanggal_mulai',
            'attachments.*'  => 'nullable|file|max:153600',
            'is_lhu'         => 'nullable|boolean',
            'sample_ids'     => 'nullable|array',
            'sample_ids.*'   => 'integer',
        ]);

        // Penghubung sample diperiksa SEBELUM apa pun disimpan (termasuk upload lampiran)
        $isLhu     = $request->boolean('is_lhu');
        $sampleIds = $isLhu ? $this->sampleIdsFromRequest($request) : [];
        if ($invalid = $this->invalidSampleIds((int) $request->id_wo, $sampleIds, null)) {
            return $this->sampleError($invalid);
        }

        $files = [];
        if ($request->hasFile('attachments')) {
            $upload = uploadAttachment($request->file('attachments'), 'output_pekerjaan');
            $files  = $upload['files'];
        }

        // Status 'siap' cuma boleh diset lewat aksi khusus (slug
        // 'output-pekerjaan-siap') — kalau dikirim langsung tanpa akses itu,
        // turunkan diam-diam ke 'belum_siap' supaya tidak jadi celah bypass.
        $status = $request->status ?? 'belum_siap';
        if ($status === 'siap' && !userCan('output-pekerjaan-siap', 'can_update')) {
            $status = 'belum_siap';
        }

        $id = DB::transaction(function () use ($request, $status, $files, $isLhu, $sampleIds) {
        $newId = DB::table('output_pekerjaan')->insertGetId([
            'id_wo'           => $request->id_wo,
            'judul_output'    => OutputJudul::resolve('pekerjaan', $request->judul_output),
            'is_lhu'          => $isLhu ? 1 : 0,
            'judul_dokumen'   => $request->judul_dokumen,
            'jenis_dokumen'   => $request->jenis_dokumen,
            'qty_copy'        => in_array($request->jenis_dokumen, ['copy','asli_dan_copy']) ? ($request->qty_copy ?: null) : null,
            'qty_asli'        => in_array($request->jenis_dokumen, ['asli','asli_dan_copy']) ? ($request->qty_asli ?: null) : null,
            'link_drive'      => $request->link_drive ?: null,
            'status'          => $status,
            'tanggal_mulai'   => $request->tanggal_mulai ?: null,
            'tanggal_selesai' => $request->tanggal_selesai ?: null,
            'attachments'     => $files ? json_encode($files) : null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
        $this->syncSamples($newId, $isLhu, $sampleIds);
        return $newId;
        });

        saveAudit('output_pekerjaan', $id, 'Create', '', $this->outputJson($id), ['work_orders', $request->id_wo]);

        return response()->json([
            'success' => true,
            'data'    => DB::table('output_pekerjaan')->where('id_output', $id)->first(),
        ]);
    }

    /** Snapshot 1 baris output (JSON) untuk audit log. */
    private function outputJson($id): string
    {
        $row = DB::table('output_pekerjaan')->where('id_output', $id)->first();
        if (!$row) return '[]';

        // Sample yang dihubungkan ikut dicatat (satu field berisi daftar No. Sample) supaya perubahannya tampil di History
        $row = (array) $row;
        $nomor = DB::table('output_pekerjaan_samples as os')
            ->join('lab_samples as ls', 'ls.id_lab_sample', '=', 'os.id_lab_sample')
            ->where('os.id_output', $id)->orderBy('ls.no_sample')->pluck('ls.no_sample')->all();
        $row['sample_lhu'] = $nomor ? implode(', ', $nomor) : null;

        return json_encode([$row]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'judul_output'   => 'required|string|max:255',
            'judul_dokumen'  => 'nullable|string|max:255',
            'jenis_dokumen'  => 'nullable|in:copy,asli,asli_dan_copy',
            'qty_copy'       => 'nullable|integer|min:0',
            'qty_asli'       => 'nullable|integer|min:0',
            'link_drive'      => 'nullable|string|max:2048',
            'status'          => 'nullable|in:belum_siap,siap',
            'tanggal_mulai'   => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'attachments.*'   => 'nullable|file|max:153600',
            'is_lhu'          => 'nullable|boolean',
            'sample_ids'      => 'nullable|array',
            'sample_ids.*'    => 'integer',
        ]);

        // Penghubung sample diperiksa SEBELUM apa pun diubah (termasuk upload lampiran)
        $idWo      = (int) DB::table('output_pekerjaan')->where('id_output', $id)->value('id_wo');
        $isLhu     = $request->boolean('is_lhu');
        $sampleIds = $isLhu ? $this->sampleIdsFromRequest($request) : [];
        if ($invalid = $this->invalidSampleIds($idWo, $sampleIds, (int) $id)) {
            return $this->sampleError($invalid);
        }

        $existing = $request->existing_attachments ?? [];
        $newFiles = [];
        if ($request->hasFile('attachments')) {
            $upload   = uploadAttachment($request->file('attachments'), 'output_pekerjaan');
            $newFiles = $upload['files'];
        }
        $allFiles = array_merge($existing, $newFiles);

        // Transisi ke 'siap' cuma boleh lewat aksi khusus (slug
        // 'output-pekerjaan-siap') — kalau form edit biasa coba ubah ke
        // 'siap' tanpa akses itu, pertahankan status lama (field lain tetap
        // ikut ter-update seperti biasa, bukan seluruh request ditolak).
        $before  = $this->outputJson($id);
        $current = DB::table('output_pekerjaan')->where('id_output', $id)->value('status');
        $status  = $request->status ?? 'belum_siap';
        if ($status === 'siap' && $current !== 'siap' && !userCan('output-pekerjaan-siap', 'can_update')) {
            $status = $current;
        }

        DB::transaction(function () use ($request, $id, $status, $allFiles, $isLhu, $sampleIds) {
        DB::table('output_pekerjaan')->where('id_output', $id)->update([
            'judul_output'  => OutputJudul::resolve('pekerjaan', $request->judul_output),
            'is_lhu'        => $isLhu ? 1 : 0,
            'judul_dokumen' => $request->judul_dokumen,
            'jenis_dokumen' => $request->jenis_dokumen,
            'qty_copy'      => in_array($request->jenis_dokumen, ['copy','asli_dan_copy']) ? ($request->qty_copy ?: null) : null,
            'qty_asli'      => in_array($request->jenis_dokumen, ['asli','asli_dan_copy']) ? ($request->qty_asli ?: null) : null,
            'link_drive'      => $request->link_drive ?: null,
            'status'          => $status,
            'tanggal_mulai'   => $request->tanggal_mulai ?: null,
            'tanggal_selesai' => $request->tanggal_selesai ?: null,
            'attachments'     => $allFiles ? json_encode($allFiles) : null,
            'updated_at'      => now(),
        ]);
        $this->syncSamples((int) $id, $isLhu, $sampleIds);
        });

        $this->auditOutput($id, 'Update', $before);

        return response()->json([
            'success' => true,
            'data'    => DB::table('output_pekerjaan')->where('id_output', $id)->first(),
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:belum_siap,siap,terkirim']);

        if ($request->status === 'siap' && !userCan('output-pekerjaan-siap', 'can_update')) {
            return response()->json(['message' => 'Anda tidak memiliki akses untuk aksi Siap Output Pekerjaan'], 403);
        }

        $before = $this->outputJson($id);
        $prev   = DB::table('output_pekerjaan')->where('id_output', $id)->value('status');
        DB::table('output_pekerjaan')->where('id_output', $id)->update([
            'status'     => $request->status,
            'updated_at' => now(),
        ]);
        $this->auditOutput($id, 'Update', $before);

        if ($request->status === 'siap' && $prev !== 'siap') {
            $row = DB::table('output_pekerjaan as o')->leftJoin('work_orders as w', 'w.id_wo', '=', 'o.id_wo')
                ->where('o.id_output', $id)->first(['o.id_wo', 'o.judul_output', 'w.no_wo']);
            \App\Services\NotificationDispatcher::fire('output_pekerjaan.siap', [
                'title' => 'Output Siap: {{nama_output}}',
                'body'  => '{{no_wo}}',
                'url'   => $row?->id_wo ? url('work-orders?open=' . $row->id_wo) : null,
            ], [
                'nama_output' => $row->judul_output ?? '',
                'no_wo'       => $row->no_wo ?? '',
                'nama_user'   => \Illuminate\Support\Facades\Auth::user()?->name,
            ], \App\Support\NotificationRoles::wo($row?->id_wo ? (int) $row->id_wo : null));
        }

        return response()->json(['success' => true, 'status' => $request->status]);
    }

    /** Catat perubahan output; ikut tampil di History WO bila output milik WO (bukan level Termin/BOQ). */
    private function auditOutput($id, string $aksi, string $before): void
    {
        $idWo = DB::table('output_pekerjaan')->where('id_output', $id)->value('id_wo');
        saveAudit('output_pekerjaan', $id, $aksi, $before, $this->outputJson($id), $idWo ? ['work_orders', $idWo] : null);
    }

    public function destroy($id)
    {
        $before = $this->outputJson($id);
        $idWo   = DB::table('output_pekerjaan')->where('id_output', $id)->value('id_wo');
        DB::table('output_pekerjaan')->where('id_output', $id)->delete();
        saveAudit('output_pekerjaan', $id, 'Delete', $before, '', $idWo ? ['work_orders', $idWo] : null);
        return response()->json(['success' => true, 'message' => 'Output berhasil dihapus']);
    }
}
