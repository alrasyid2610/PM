<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasAuditHistory;

class FwoBudgetActualController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'fwo_budget_actuals'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_actual']; }

    private function fwoIsCompleted($id_budget_item): bool
    {
        $fwo = DB::table('fwo_budget_items as bi')
            ->join('fwo_budgets as b', 'b.id_budget', '=', 'bi.id_budget')
            ->join('fieldworks as fw', 'fw.id_fwo', '=', 'b.id_fwo')
            ->where('bi.id_budget_item', $id_budget_item)
            ->value('fw.status');
        return $fwo === 'completed';
    }

    /** Induk FWO dari sebuah item budget (untuk audit log tab Budget di History FWO). */
    private function parentFwo($idBudgetItem): ?array
    {
        $idFwo = DB::table('fwo_budget_items as bi')
            ->join('fwo_budgets as b', 'b.id_budget', '=', 'bi.id_budget')
            ->where('bi.id_budget_item', $idBudgetItem)
            ->value('b.id_fwo');

        return $idFwo ? ['fieldworks', $idFwo] : null;
    }

    /** Snapshot realisasi + nama akun & plan-nya supaya History mudah dibaca. */
    private function actualJson($id): string
    {
        $row = DB::table('fwo_budget_actuals as a')
            ->leftJoin('fwo_budget_items as bi', 'bi.id_budget_item', '=', 'a.id_budget_item')
            ->leftJoin('budget_accounts as ac', 'ac.id_account', '=', 'bi.id_account')
            ->leftJoin('fwo_budgets as b', 'b.id_budget', '=', 'bi.id_budget')
            ->where('a.id_actual', $id)
            ->select(['a.*', 'ac.nama as akun', 'b.label as plan'])
            ->first();

        return $row ? json_encode([$row]) : '';
    }

    public function store(Request $request)
    {
        if ($this->fwoIsCompleted($request->id_budget_item)) {
            return response()->json(['success' => false, 'message' => 'FWO sudah selesai, tidak dapat menambah pengeluaran.'], 403);
        }

        $request->validate([
            'id_budget_item'  => 'required|integer',
            'nominal_actual'  => 'required|integer|min:0',
            'keterangan'      => 'nullable|string',
            'attachments.*'   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:153600',
        ]);

        $files = [];
        if ($request->hasFile('attachments')) {
            $uploaded = uploadAttachment($request->file('attachments'), 'fwo-budget-actuals');
            $files    = $uploaded['files'] ?? [];
        }

        $id = DB::table('fwo_budget_actuals')->insertGetId([
            'id_budget_item' => $request->id_budget_item,
            'nominal_actual' => $request->nominal_actual,
            'keterangan'     => $request->keterangan,
            'attachments'    => $files ? json_encode($files) : null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $after = $this->actualJson($id);
        saveAudit('fwo_budget_actuals', $id, 'Create', '', $after, $this->parentFwo($request->id_budget_item));

        $ctx = DB::table('fwo_budget_items as bi')
            ->join('fwo_budgets as b', 'b.id_budget', '=', 'bi.id_budget')
            ->join('fieldworks as f', 'f.id_fwo', '=', 'b.id_fwo')
            ->where('bi.id_budget_item', $request->id_budget_item)
            ->first(['f.id_fwo', 'f.no_fwo', 'b.label']);
        if ($ctx) {
            \App\Services\NotificationDispatcher::fire('fwo_budget.verifikasi_dibutuhkan', [
                'title' => 'Budget FWO butuh verifikasi: {{no_fwo}}',
                'body'  => '{{label_plan}} — Rp {{nominal}}',
                'url'   => url('fieldworks?open=' . $ctx->id_fwo . '&tab=tabFwoBudget'),
            ], [
                'no_fwo'     => $ctx->no_fwo,
                'label_plan' => $ctx->label,
                'nominal'    => number_format((int) $request->nominal_actual, 0, ',', '.'),
                'nama_user'  => \Illuminate\Support\Facades\Auth::user()?->name,
            ], \App\Support\NotificationRoles::fwo((int) $ctx->id_fwo));
        }

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function show($id)
    {
        $row = DB::table('fwo_budget_actuals')->where('id_actual', $id)->first();
        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);
        return response()->json($row);
    }

    public function update(Request $request, $id)
    {
        $row = DB::table('fwo_budget_actuals')->where('id_actual', $id)->first();
        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);

        if ($this->fwoIsCompleted($row->id_budget_item)) {
            return response()->json(['success' => false, 'message' => 'FWO sudah selesai, tidak dapat mengubah pengeluaran.'], 403);
        }

        $request->validate([
            'nominal_actual' => 'required|integer|min:0',
            'keterangan'     => 'nullable|string',
            'attachments.*'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:153600',
        ]);

        // Attachment lama: yang tidak dikirim balik (dihapus user di modal)
        // dibuang dari DB dan file fisiknya dihapus SETELAH update berhasil.
        // Kalau `existing_attachments_sent` tidak ada, semua file lama dibiarkan.
        $existing = attachmentPathsFromInput($row->attachments);
        if ($request->has('existing_attachments_sent')) {
            $kept    = array_values(array_intersect($existing, attachmentPathsFromInput($request->input('existing_attachments', []))));
            $removed = array_values(array_diff($existing, $kept));
        } else {
            $kept    = $existing;
            $removed = [];
        }

        $newFiles = [];
        if ($request->hasFile('attachments')) {
            $uploaded = uploadAttachment($request->file('attachments'), 'fwo-budget-actuals');
            $newFiles = $uploaded['files'] ?? [];
        }

        $allFiles = array_merge($kept, $newFiles);

        $before = $this->actualJson($id);

        DB::table('fwo_budget_actuals')->where('id_actual', $id)->update([
            'nominal_actual' => $request->nominal_actual,
            'keterangan'     => $request->keterangan,
            'attachments'    => $allFiles ? json_encode($allFiles) : null,
            'updated_at'     => now(),
        ]);

        deleteAttachmentFiles($removed);

        $after = $this->actualJson($id);
        saveAudit('fwo_budget_actuals', $id, 'Update', $before, $after, $this->parentFwo($row->id_budget_item));

        return response()->json(['success' => true]);
    }

    public function bulkVerify(Request $request)
    {
        if (!userCan('fwo-budget-verify', 'can_update')) {
            return response()->json(['message' => 'Anda tidak memiliki akses untuk verifikasi budget FWO'], 403);
        }

        $request->validate([
            'items'                    => 'required|array|min:1',
            'items.*.id_actual'        => 'required|integer',
            'items.*.status_verifikasi'=> 'required|in:menunggu,disetujui,ditolak',
            'items.*.catatan_verifikasi'=> 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->items as $item) {
                // Item yang status & catatannya tidak berubah dilewati (tidak diverifikasi ulang, tidak dicatat di History)
                $sekarang = DB::table('fwo_budget_actuals')->where('id_actual', $item['id_actual'])->first(['status_verifikasi', 'catatan_verifikasi']);
                if ($sekarang
                    && ($sekarang->status_verifikasi ?? 'menunggu') === $item['status_verifikasi']
                    && ($sekarang->catatan_verifikasi ?: null) === ($item['catatan_verifikasi'] ?: null)) {
                    continue;
                }

                $before = $this->actualJson($item['id_actual']);
                DB::table('fwo_budget_actuals')->where('id_actual', $item['id_actual'])->update([
                    'status_verifikasi'  => $item['status_verifikasi'],
                    'catatan_verifikasi' => $item['catatan_verifikasi'] ?: null,
                    'verified_by'        => auth()->id(),
                    'verified_at'        => now(),
                    'updated_at'         => now(),
                ]);
                $after = $this->actualJson($item['id_actual']);
                saveAudit('fwo_budget_actuals', $item['id_actual'], 'Verify', $before, $after, $this->parentFwo(DB::table('fwo_budget_actuals')->where('id_actual', $item['id_actual'])->value('id_budget_item')));
            }
        });

        return response()->json(['success' => true]);
    }

    public function verify(Request $request, $id)
    {
        if (!userCan('fwo-budget-verify', 'can_update')) {
            return response()->json(['message' => 'Anda tidak memiliki akses untuk verifikasi budget FWO'], 403);
        }

        $row = DB::table('fwo_budget_actuals')->where('id_actual', $id)->first();
        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);

        $request->validate([
            'status_verifikasi'  => 'required|in:disetujui,ditolak',
            'catatan_verifikasi' => 'nullable|string|max:500',
        ]);

        $before = $this->actualJson($id);

        DB::table('fwo_budget_actuals')->where('id_actual', $id)->update([
            'status_verifikasi'  => $request->status_verifikasi,
            'catatan_verifikasi' => $request->catatan_verifikasi ?: null,
            'verified_by'        => auth()->id(),
            'verified_at'        => now(),
            'updated_at'         => now(),
        ]);

        $after = $this->actualJson($id);
        saveAudit('fwo_budget_actuals', $id, 'Verify', $before, $after, $this->parentFwo($row->id_budget_item));

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $row = DB::table('fwo_budget_actuals')->where('id_actual', $id)->first();
        if ($row && $this->fwoIsCompleted($row->id_budget_item)) {
            return response()->json(['success' => false, 'message' => 'FWO sudah selesai, tidak dapat menghapus pengeluaran.'], 403);
        }

        $before = $this->actualJson($id);
        DB::table('fwo_budget_actuals')->where('id_actual', $id)->delete();
        saveAudit('fwo_budget_actuals', $id, 'Delete', $before, '', $row ? $this->parentFwo($row->id_budget_item) : null);

        return response()->json(['success' => true]);
    }
}
