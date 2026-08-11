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

    public function store(Request $request)
    {
        if ($this->woIsCompleted($request->id_budget_item)) {
            return response()->json(['success' => false, 'message' => 'WO sudah selesai, tidak dapat menambah pengeluaran.'], 403);
        }

        $request->validate([
            'id_budget_item'  => 'required|integer',
            'nominal_actual'  => 'required|integer|min:0',
            'keterangan'      => 'nullable|string',
            'attachments.*'   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
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

        $after = DB::table('wo_budget_actuals')->where('id_actual', $id)->get()->toJson();
        saveAudit('wo_budget_actuals', $id, 'Create', '', $after);

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
            'attachments.*'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $existing = json_decode($row->attachments ?? '[]', true) ?: [];
        $kept     = json_decode($request->input('existing_attachments', '[]'), true) ?: [];
        $kept     = array_values(array_intersect($existing, $kept));

        $newFiles = [];
        if ($request->hasFile('attachments')) {
            $uploaded = uploadAttachment($request->file('attachments'), 'wo-budget-actuals');
            $newFiles = $uploaded['files'] ?? [];
        }

        $allFiles = array_merge($kept, $newFiles);

        $before = DB::table('wo_budget_actuals')->where('id_actual', $id)->get()->toJson();

        DB::table('wo_budget_actuals')->where('id_actual', $id)->update([
            'nominal_actual' => $request->nominal_actual,
            'keterangan'     => $request->keterangan,
            'attachments'    => $allFiles ? json_encode($allFiles) : null,
            'updated_at'     => now(),
        ]);

        $after = DB::table('wo_budget_actuals')->where('id_actual', $id)->get()->toJson();
        saveAudit('wo_budget_actuals', $id, 'Update', $before, $after);

        return response()->json(['success' => true]);
    }

    public function bulkVerify(Request $request)
    {
        $request->validate([
            'items'                     => 'required|array|min:1',
            'items.*.id_actual'         => 'required|integer',
            'items.*.status_verifikasi' => 'required|in:menunggu,disetujui,ditolak',
            'items.*.catatan_verifikasi'=> 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->items as $item) {
                $before = DB::table('wo_budget_actuals')->where('id_actual', $item['id_actual'])->get()->toJson();
                DB::table('wo_budget_actuals')->where('id_actual', $item['id_actual'])->update([
                    'status_verifikasi'  => $item['status_verifikasi'],
                    'catatan_verifikasi' => $item['catatan_verifikasi'] ?: null,
                    'verified_by'        => auth()->id(),
                    'verified_at'        => now(),
                    'updated_at'         => now(),
                ]);
                $after = DB::table('wo_budget_actuals')->where('id_actual', $item['id_actual'])->get()->toJson();
                saveAudit('wo_budget_actuals', $item['id_actual'], 'Verify', $before, $after);
            }
        });

        return response()->json(['success' => true]);
    }

    public function verify(Request $request, $id)
    {
        $row = DB::table('wo_budget_actuals')->where('id_actual', $id)->first();
        if (!$row) return response()->json(['message' => 'Tidak ditemukan'], 404);

        $request->validate([
            'status_verifikasi'  => 'required|in:disetujui,ditolak',
            'catatan_verifikasi' => 'nullable|string|max:500',
        ]);

        $before = DB::table('wo_budget_actuals')->where('id_actual', $id)->get()->toJson();

        DB::table('wo_budget_actuals')->where('id_actual', $id)->update([
            'status_verifikasi'  => $request->status_verifikasi,
            'catatan_verifikasi' => $request->catatan_verifikasi ?: null,
            'verified_by'        => auth()->id(),
            'verified_at'        => now(),
            'updated_at'         => now(),
        ]);

        $after = DB::table('wo_budget_actuals')->where('id_actual', $id)->get()->toJson();
        saveAudit('wo_budget_actuals', $id, 'Verify', $before, $after);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $row = DB::table('wo_budget_actuals')->where('id_actual', $id)->first();
        if ($row && $this->woIsCompleted($row->id_budget_item)) {
            return response()->json(['success' => false, 'message' => 'WO sudah selesai, tidak dapat menghapus pengeluaran.'], 403);
        }

        $before = DB::table('wo_budget_actuals')->where('id_actual', $id)->get()->toJson();
        DB::table('wo_budget_actuals')->where('id_actual', $id)->delete();
        saveAudit('wo_budget_actuals', $id, 'Delete', $before, '');

        return response()->json(['success' => true]);
    }
}
