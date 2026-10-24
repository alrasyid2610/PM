<?php

namespace App\Http\Controllers;

use App\Support\OutputJudul;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasAuditHistory;

/**
 * "Output Others" — level WO (`id_wo`) ATAU level SO (`id_so`, sejak 2026-10-26), berdiri sendiri
 * (tidak terhubung ke BOQ Other). Gabungan baris tagihan (qty/satuan) + tracking dokumen
 * (status/attachments/link_drive/tanggal), lihat Project Reference bagian
 * "PRD — Struktur Modul Lab Testing". Satu baris hanya punya salah satu pemilik: id_wo ATAU id_so.
 * Route WO: /wo-output-other (izin work-orders); route SO: /so-output-other (izin sales-orders).
 */
class OutputTambahanController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'output_tambahan'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_output_tambahan']; }

    /**
     * Pesan penolakan bila pemilik item terkunci, atau null bila boleh diubah.
     * WO terkunci saat completed; SO terkunci saat completed/cancel atau sudah dihapus.
     */
    private function lockMessage($idWo, $idSo, string $aksi): ?string
    {
        if ($idWo) {
            $wo = DB::table('work_orders')->where('id_wo', $idWo)->first(['status']);
            return ($wo && $wo->status === 'completed') ? "WO sudah selesai, tidak dapat {$aksi} item." : null;
        }
        if ($idSo) {
            $so = DB::table('sales_orders')->where('id_so', $idSo)->first(['status', 'deleted_at']);
            if (!$so || $so->deleted_at) return "SO tidak ditemukan atau sudah dihapus, tidak dapat {$aksi} item.";
            return in_array($so->status, ['completed', 'cancel'], true) ? "SO sudah {$so->status}, tidak dapat {$aksi} item." : null;
        }
        return null;
    }

    /** Induk audit: item level WO tercatat di History WO, level SO di History SO. */
    private function auditParent(object|array $row): array
    {
        $row = (object) $row;
        return $row->id_wo ? ['work_orders', $row->id_wo] : ['sales_orders', $row->id_so];
    }

    /**
     * Transisi ke 'siap' hanya untuk user ber-izin aksi khusus `output-pekerjaan-siap` (sama dengan
     * Output Pekerjaan). Tanpa izin, status yang diminta diturunkan diam-diam ke status sebelumnya
     * (create: belum_siap) supaya tidak jadi celah lewat form tambah/ubah biasa.
     */
    private function guardSiap(string $diminta, string $fallback): string
    {
        if ($diminta === 'siap' && $fallback !== 'siap' && !userCan('output-pekerjaan-siap', 'can_update')) {
            return $fallback;
        }
        return $diminta;
    }

    /** Aksi cepat ubah status (tombol "Siap" di daftar Output Other WO & SO). */
    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:belum_siap,siap,terkirim']);

        $row = DB::table('output_tambahan')->where('id_output_tambahan', $id)->whereNull('deleted_at')->first();
        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);

        if ($request->status === 'siap' && !userCan('output-pekerjaan-siap', 'can_update')) {
            return response()->json(['message' => 'Anda tidak memiliki akses untuk aksi Siap Output'], 403);
        }
        if ($msg = $this->lockMessage($row->id_wo, $row->id_so ?? null, 'mengubah')) {
            return response()->json(['success' => false, 'message' => $msg], 403);
        }

        $before = DB::table('output_tambahan')->where('id_output_tambahan', $id)->get()->toJson();
        DB::table('output_tambahan')->where('id_output_tambahan', $id)->update(['status' => $request->status, 'updated_at' => now()]);
        $after = DB::table('output_tambahan')->where('id_output_tambahan', $id)->get()->toJson();
        saveAudit('output_tambahan', $id, 'Update', $before, $after, $this->auditParent($row));

        return response()->json(['success' => true, 'status' => $request->status]);
    }

    public function listBySo($id_so)
    {
        $rows = DB::table('output_tambahan as t')
            ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 't.id_satuan')
            ->where('t.id_so', $id_so)
            ->whereNull('t.deleted_at')
            ->orderBy('t.created_at')
            ->select([
                't.id_output_tambahan', 't.nama_item', 't.qty', 't.id_satuan', 'sat.nama as satuan',
                't.status', 't.tanggal_mulai', 't.tanggal_selesai',
                't.attachments', 't.link_drive', 't.keterangan', 't.created_at',
            ])
            ->get();

        $rows->each(function ($r) {
            $r->attachments = $r->attachments ? json_decode($r->attachments) : [];
        });

        $so = DB::table('sales_orders')->where('id_so', $id_so)->first(['status']);

        return response()->json([
            'data'      => $rows,
            'so_status' => $so->status ?? null,
            'locked'    => $this->lockMessage(null, $id_so, 'mengubah') !== null,
        ]);
    }

    public function listByWo($id_wo)
    {
        $rows = DB::table('output_tambahan as t')
            ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 't.id_satuan')
            ->where('t.id_wo', $id_wo)
            ->whereNull('t.deleted_at')
            ->orderBy('t.created_at')
            ->select([
                't.id_output_tambahan', 't.nama_item', 't.qty', 't.id_satuan', 'sat.nama as satuan',
                't.status', 't.tanggal_mulai', 't.tanggal_selesai',
                't.attachments', 't.link_drive', 't.keterangan', 't.created_at',
            ])
            ->get();

        $rows->each(function ($r) {
            $r->attachments = $r->attachments ? json_decode($r->attachments) : [];
        });

        $wo = DB::table('work_orders')->where('id_wo', $id_wo)->first(['status']);

        return response()->json([
            'data'      => $rows,
            'wo_status' => $wo->status ?? null,
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nama_item'       => 'required|string|max:255',
            'qty'             => 'required|integer|min:1',
            'id_satuan'       => 'nullable|integer|exists:satuan,id_satuan',
            'status'          => 'nullable|in:belum_siap,siap,terkirim',
            'tanggal_mulai'   => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'link_drive'      => 'nullable|string|max:2048',
            'keterangan'      => 'nullable|string',
            'attachments.*'   => 'nullable|file|max:153600',
        ]);
    }

    public function store(Request $request)
    {
        // Pemilik: tepat satu dari id_wo (level WO) atau id_so (level SO)
        $idWo = $request->filled('id_wo') ? (int) $request->id_wo : null;
        $idSo = $request->filled('id_so') ? (int) $request->id_so : null;
        if (($idWo === null) === ($idSo === null)) {
            return response()->json(['success' => false, 'message' => 'Item harus terkait ke tepat satu WO atau SO.'], 422);
        }
        if ($idSo !== null && !DB::table('sales_orders')->where('id_so', $idSo)->exists()) {
            return response()->json(['success' => false, 'message' => 'SO tidak ditemukan.'], 422);
        }

        if ($msg = $this->lockMessage($idWo, $idSo, 'menambah')) {
            return response()->json(['success' => false, 'message' => $msg], 403);
        }

        $validated = $this->validated($request);

        $files = [];
        if ($request->hasFile('attachments')) {
            $upload = uploadAttachment($request->file('attachments'), 'output_tambahan');
            $files  = $upload['files'];
        }

        $id = DB::table('output_tambahan')->insertGetId([
            'id_wo'           => $idWo,
            'id_so'           => $idSo,
            'nama_item'       => OutputJudul::resolve('other', $validated['nama_item']),
            'qty'             => $validated['qty'],
            'id_satuan'       => $validated['id_satuan'] ?? null,
            'status'          => $this->guardSiap($validated['status'] ?? 'belum_siap', 'belum_siap'),
            'tanggal_mulai'   => $validated['tanggal_mulai'] ?? null,
            'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
            'attachments'     => $files ? json_encode($files) : null,
            'link_drive'      => $validated['link_drive'] ?? null,
            'keterangan'      => $validated['keterangan'] ?? null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $after = DB::table('output_tambahan')->where('id_output_tambahan', $id)->get()->toJson();
        saveAudit('output_tambahan', $id, 'Create', '', $after, $idWo ? ['work_orders', $idWo] : ['sales_orders', $idSo]);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function show($id)
    {
        $row = DB::table('output_tambahan')->where('id_output_tambahan', $id)->whereNull('deleted_at')->first();
        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);
        $row->attachments = $row->attachments ? json_decode($row->attachments) : [];
        return response()->json($row);
    }

    public function update(Request $request, $id)
    {
        $row = DB::table('output_tambahan')->where('id_output_tambahan', $id)->first();
        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);

        if ($msg = $this->lockMessage($row->id_wo, $row->id_so ?? null, 'mengubah')) {
            return response()->json(['success' => false, 'message' => $msg], 403);
        }

        $validated = $this->validated($request);

        $existing = $request->input('existing_attachments', []);
        $newFiles = [];
        if ($request->hasFile('attachments')) {
            $upload   = uploadAttachment($request->file('attachments'), 'output_tambahan');
            $newFiles = $upload['files'];
        }
        $allFiles = array_merge($existing, $newFiles);

        $before = DB::table('output_tambahan')->where('id_output_tambahan', $id)->get()->toJson();

        DB::table('output_tambahan')->where('id_output_tambahan', $id)->update([
            'nama_item'       => OutputJudul::resolve('other', $validated['nama_item']),
            'qty'             => $validated['qty'],
            'id_satuan'       => $validated['id_satuan'] ?? null,
            'status'          => $this->guardSiap($validated['status'] ?? $row->status, $row->status),
            'tanggal_mulai'   => $validated['tanggal_mulai'] ?? null,
            'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
            'attachments'     => $allFiles ? json_encode($allFiles) : null,
            'link_drive'      => $validated['link_drive'] ?? null,
            'keterangan'      => $validated['keterangan'] ?? null,
            'updated_at'      => now(),
        ]);

        $after = DB::table('output_tambahan')->where('id_output_tambahan', $id)->get()->toJson();
        saveAudit('output_tambahan', $id, 'Update', $before, $after, $this->auditParent($row));

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $row = DB::table('output_tambahan')->where('id_output_tambahan', $id)->first();
        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);

        if ($msg = $this->lockMessage($row->id_wo, $row->id_so ?? null, 'menghapus')) {
            return response()->json(['success' => false, 'message' => $msg], 403);
        }

        $before = DB::table('output_tambahan')->where('id_output_tambahan', $id)->get()->toJson();
        DB::table('output_tambahan')->where('id_output_tambahan', $id)->update(['deleted_at' => now()]);
        saveAudit('output_tambahan', $id, 'Delete', $before, '', $this->auditParent($row));

        return response()->json(['success' => true]);
    }

    public function history($id)
    {
        return $this->auditHistory($id);
    }
}
