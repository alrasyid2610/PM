<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasAuditHistory;

class WoBudgetActualController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'wo_budget_actuals'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_actual']; }

    private function woIsCompleted($id_budget_item): bool
    {
        $wo = DB::table('wo_budget_items as bi')
            ->join('wo_budgets as b', 'b.id_budget', '=', 'bi.id_budget')
            ->join('work_orders as wo', 'wo.id_wo', '=', 'b.id_wo')
            ->where('bi.id_budget_item', $id_budget_item)
            ->value('wo.status');
        return $wo === 'completed';
    }

    /** Induk WO dari sebuah item budget (untuk audit log tab Budget di History WO). */
    private function parentWo($idBudgetItem): ?array
    {
        $idWo = DB::table('wo_budget_items as bi')
            ->join('wo_budgets as b', 'b.id_budget', '=', 'bi.id_budget')
            ->where('bi.id_budget_item', $idBudgetItem)
            ->value('b.id_wo');

        return $idWo ? ['work_orders', $idWo] : null;
    }

    /** Snapshot realisasi + nama akun & plan-nya supaya History mudah dibaca. */
    private function actualJson($id): string
    {
        $row = DB::table('wo_budget_actuals as a')
            ->leftJoin('wo_budget_items as bi', 'bi.id_budget_item', '=', 'a.id_budget_item')
            ->leftJoin('budget_accounts as ac', 'ac.id_account', '=', 'bi.id_account')
            ->leftJoin('wo_budgets as b', 'b.id_budget', '=', 'bi.id_budget')
            ->where('a.id_actual', $id)
            ->select(['a.*', 'ac.nama as akun', 'b.label as plan'])
            ->first();

        return $row ? json_encode([$row]) : '';
    }

    public function store(Request $request)
    {
        if ($this->woIsCompleted($request->id_budget_item)) {
            return response()->json(['success' => false, 'message' => 'WO sudah selesai, tidak dapat menambah pengeluaran.'], 403);
        }

        $request->validate([
            'id_budget_item'  => 'required|integer',
            'nominal_actual'  => 'required|integer|min:0',
            'keterangan'      => 'nullable|string',
            'attachments.*'   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:153600',
        ]);

        $files = [];
        if ($request->hasFile('attachments')) {
            $uploaded = uploadAttachment($request->file('attachments'), 'wo-budget-actuals');
            $files    = $uploaded['files'] ?? [];
        }

        $id = DB::table('wo_budget_actuals')->insertGetId([
            'id_budget_item' => $request->id_budget_item,
            'nominal_actual' => $request->nominal_actual,
            'keterangan'     => $request->keterangan,
            'attachments'    => $files ? json_encode($files) : null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $after = $this->actualJson($id);
        saveAudit('wo_budget_actuals', $id, 'Create', '', $after, $this->parentWo($request->id_budget_item));

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function show($id)
    {
        $row = DB::table('wo_budget_actuals')->where('id_actual', $id)->first();
        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);
        return response()->json($row);
    }

    public function update(Request $request, $id)
    {
        $row = DB::table('wo_budget_actuals')->where('id_actual', $id)->first();
        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);

        if ($this->woIsCompleted($row->id_budget_item)) {
            return response()->json(['success' => false, 'message' => 'WO sudah selesai, tidak dapat mengubah pengeluaran.'], 403);
        }

        $request->validate([
            'nominal_actual' => 'required|integer|min:0',
            'keterangan'     => 'nullable|string',
            'attachments.*'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:153600',
        ]);

        // Lihat FwoBudgetActualController::update() — pola sama.
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
            $uploaded = uploadAttachment($request->file('attachments'), 'wo-budget-actuals');
            $newFiles = $uploaded['files'] ?? [];
        }

        $allFiles = array_merge($kept, $newFiles);

        $before = $this->actualJson($id);

        DB::table('wo_budget_actuals')->where('id_actual', $id)->update([
            'nominal_actual' => $request->nominal_actual,
            'keterangan'     => $request->keterangan,
            'attachments'    => $allFiles ? json_encode($allFiles) : null,
            'updated_at'     => now(),
        ]);

        deleteAttachmentFiles($removed);

        $after = $this->actualJson($id);
        saveAudit('wo_budget_actuals', $id, 'Update', $before, $after, $this->parentWo(DB::table('wo_budget_actuals')->where('id_actual', $id)->value('id_budget_item')));

        return response()->json(['success' => true]);
    }

    public function bulkVerify(Request $request)
    {
        if (!userCan('wo-budget-verify', 'can_update')) {
            return response()->json(['message' => 'Anda tidak memiliki akses untuk verifikasi budget WO'], 403);
        }

        $request->validate([
            'items'                     => 'required|array|min:1',
            'items.*.id_actual'         => 'required|integer',
            'items.*.status_verifikasi' => 'required|in:menunggu,disetujui,ditolak',
            'items.*.catatan_verifikasi'=> 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->items as $item) {
                // Item yang status & catatannya tidak berubah dilewati (tidak diverifikasi ulang, tidak dicatat di History)
                $sekarang = DB::table('wo_budget_actuals')->where('id_actual', $item['id_actual'])->first(['status_verifikasi', 'catatan_verifikasi']);
                if ($sekarang
                    && ($sekarang->status_verifikasi ?? 'menunggu') === $item['status_verifikasi']
                    && ($sekarang->catatan_verifikasi ?: null) === ($item['catatan_verifikasi'] ?: null)) {
                    continue;
                }

                $before = $this->actualJson($item['id_actual']);
                DB::table('wo_budget_actuals')->where('id_actual', $item['id_actual'])->update([
                    'status_verifikasi'  => $item['status_verifikasi'],
                    'catatan_verifikasi' => $item['catatan_verifikasi'] ?: null,
                    'verified_by'        => auth()->id(),
                    'verified_at'        => now(),
                    'updated_at'         => now(),
                ]);
                $after = $this->actualJson($item['id_actual']);
                saveAudit('wo_budget_actuals', $item['id_actual'], 'Verify', $before, $after, $this->parentWo(DB::table('wo_budget_actuals')->where('id_actual', $item['id_actual'])->value('id_budget_item')));
            }
        });

        return response()->json(['success' => true]);
    }

    public function verify(Request $request, $id)
    {
        if (!userCan('wo-budget-verify', 'can_update')) {
            return response()->json(['message' => 'Anda tidak memiliki akses untuk verifikasi budget WO'], 403);
        }

        $row = DB::table('wo_budget_actuals')->where('id_actual', $id)->first();
        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);

        $request->validate([
            'status_verifikasi'  => 'required|in:disetujui,ditolak',
            'catatan_verifikasi' => 'nullable|string|max:500',
        ]);

        $before = $this->actualJson($id);

        DB::table('wo_budget_actuals')->where('id_actual', $id)->update([
            'status_verifikasi'  => $request->status_verifikasi,
            'catatan_verifikasi' => $request->catatan_verifikasi ?: null,
            'verified_by'        => auth()->id(),
            'verified_at'        => now(),
            'updated_at'         => now(),
        ]);

        $after = $this->actualJson($id);
        saveAudit('wo_budget_actuals', $id, 'Verify', $before, $after, $this->parentWo($row->id_budget_item));

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $row = DB::table('wo_budget_actuals')->where('id_actual', $id)->first();
        if ($row && $this->woIsCompleted($row->id_budget_item)) {
            return response()->json(['success' => false, 'message' => 'WO sudah selesai, tidak dapat menghapus pengeluaran.'], 403);
        }

        $before = $this->actualJson($id);
        DB::table('wo_budget_actuals')->where('id_actual', $id)->delete();
        saveAudit('wo_budget_actuals', $id, 'Delete', $before, '', $row ? $this->parentWo($row->id_budget_item) : null);

        return response()->json(['success' => true]);
    }
}
