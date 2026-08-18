<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasAuditHistory;

/**
 * "Output Others" — level WO, berdiri sendiri (tidak terhubung ke BOQ
 * Other). Gabungan baris tagihan (qty/satuan/harga) + tracking dokumen
 * (status/attachments/link_drive/tanggal), lihat Project Reference bagian
 * "PRD — Struktur Modul Lab Testing".
 */
class OutputTambahanController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'output_tambahan'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_output_tambahan']; }

    private function woIsCompleted($id_wo): bool
    {
        $wo = DB::table('work_orders')->where('id_wo', $id_wo)->first(['status']);
        return $wo && $wo->status === 'completed';
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
                't.harga', 't.status', 't.tanggal_mulai', 't.tanggal_selesai',
                't.attachments', 't.link_drive', 't.keterangan', 't.created_at',
            ])
            ->get();

        $rows->each(function ($r) {
            $r->attachments = $r->attachments ? json_decode($r->attachments) : [];
        });

        $wo = DB::table('work_orders')->where('id_wo', $id_wo)->first(['status']);

        return response()->json([
            'data'      => $rows,
            'total'     => $rows->sum(fn($r) => (int) $r->qty * (int) $r->harga),
            'wo_status' => $wo->status ?? null,
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nama_item'       => 'required|string|max:255',
            'qty'             => 'required|integer|min:1',
            'id_satuan'       => 'nullable|integer|exists:satuan,id_satuan',
            'harga'           => 'required|integer|min:0',
            'status'          => 'nullable|in:belum_siap,siap,terkirim',
            'tanggal_mulai'   => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'link_drive'      => 'nullable|string|max:2048',
            'keterangan'      => 'nullable|string',
            'attachments.*'   => 'nullable|file|max:10240',
        ]);
    }

    public function store(Request $request)
    {
        if ($this->woIsCompleted($request->id_wo)) {
            return response()->json(['success' => false, 'message' => 'WO sudah selesai, tidak dapat menambah item.'], 403);
        }

        $validated = $this->validated($request);

        $files = [];
        if ($request->hasFile('attachments')) {
            $upload = uploadAttachment($request->file('attachments'), 'output_tambahan');
            $files  = $upload['files'];
        }

        $id = DB::table('output_tambahan')->insertGetId([
            'id_wo'           => $request->id_wo,
            'nama_item'       => $validated['nama_item'],
            'qty'             => $validated['qty'],
            'id_satuan'       => $validated['id_satuan'] ?? null,
            'harga'           => $validated['harga'],
            'status'          => $validated['status'] ?? 'belum_siap',
            'tanggal_mulai'   => $validated['tanggal_mulai'] ?? null,
            'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
            'attachments'     => $files ? json_encode($files) : null,
            'link_drive'      => $validated['link_drive'] ?? null,
            'keterangan'      => $validated['keterangan'] ?? null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $after = DB::table('output_tambahan')->where('id_output_tambahan', $id)->get()->toJson();
        saveAudit('output_tambahan', $id, 'Create', '', $after);

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

        if ($this->woIsCompleted($row->id_wo)) {
            return response()->json(['success' => false, 'message' => 'WO sudah selesai, tidak dapat mengubah item.'], 403);
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
            'nama_item'       => $validated['nama_item'],
            'qty'             => $validated['qty'],
            'id_satuan'       => $validated['id_satuan'] ?? null,
            'harga'           => $validated['harga'],
            'status'          => $validated['status'] ?? 'belum_siap',
            'tanggal_mulai'   => $validated['tanggal_mulai'] ?? null,
            'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
            'attachments'     => $allFiles ? json_encode($allFiles) : null,
            'link_drive'      => $validated['link_drive'] ?? null,
            'keterangan'      => $validated['keterangan'] ?? null,
            'updated_at'      => now(),
        ]);

        $after = DB::table('output_tambahan')->where('id_output_tambahan', $id)->get()->toJson();
        saveAudit('output_tambahan', $id, 'Update', $before, $after);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $row = DB::table('output_tambahan')->where('id_output_tambahan', $id)->first();
        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);

        if ($this->woIsCompleted($row->id_wo)) {
            return response()->json(['success' => false, 'message' => 'WO sudah selesai, tidak dapat menghapus item.'], 403);
        }

        $before = DB::table('output_tambahan')->where('id_output_tambahan', $id)->get()->toJson();
        DB::table('output_tambahan')->where('id_output_tambahan', $id)->update(['deleted_at' => now()]);
        saveAudit('output_tambahan', $id, 'Delete', $before, '');

        return response()->json(['success' => true]);
    }

    public function history($id)
    {
        return $this->auditHistory($id);
    }
}
