<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Support\SqLock;
use Illuminate\Support\Facades\DB;
use App\Traits\HasAuditHistory;

/**
 * SQ Fieldwork — tab Budget (estimasi). Salinan persis SqWoBudgetController,
 * retarget ke level SQ FWO (tabel sq_fwo_budgets/sq_fwo_budget_items) —
 * TANPA Realisasi/Actual, sama seperti SQ WO Budget (SQ tidak pernah punya
 * realisasi). Lihat Obsidian Modules/Sales Quotation.md.
 */
class SqFwoBudgetController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'sq_fwo_budgets'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_sq_budget']; }

    public function listByFwo($id_sq_fwo)
    {
        $budgets = DB::table('sq_fwo_budgets as b')
            ->where('b.id_sq_fwo', $id_sq_fwo)
            ->orderBy('b.created_at')
            ->get(['b.id_sq_budget', 'b.label', 'b.keterangan', 'b.hari_mulai', 'b.hari_selesai']);

        foreach ($budgets as $budget) {
            $items = DB::table('sq_fwo_budget_items as bi')
                ->join('budget_accounts as ba', 'ba.id_account', '=', 'bi.id_account')
                ->leftJoin('budget_accounts as bp', 'bp.id_account', '=', 'ba.id_parent')
                ->where('bi.id_sq_budget', $budget->id_sq_budget)
                ->get([
                    'bi.id_sq_budget_item', 'bi.id_account', 'ba.nama as nama_account',
                    'ba.kode as kode_account', 'bp.nama as nama_category',
                    'bi.nominal_budget', 'bi.keterangan', 'bi.is_cash_advance',
                ]);

            $budget->items = $items;
            $budget->total_budget = $items->sum('nominal_budget');
        }

        return response()->json($budgets);
    }

    public function store(Request $request)
    {
        if ($lock = SqLock::byFwo($request->input('id_sq_fwo'))) return $lock;
        $request->validate([
            'id_sq_fwo' => 'required|integer|exists:sq_fieldworks,id_sq_fwo',
            'label' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'hari_mulai' => 'nullable|integer|min:1',
            'hari_selesai' => 'nullable|integer|min:1',
            'items' => 'required|array|min:1',
            'items.*.id_account' => 'required|integer',
            'items.*.nominal_budget' => 'required|integer|min:0',
            'items.*.keterangan' => 'nullable|string',
        ]);

        $id = DB::transaction(function () use ($request) {
            $id = DB::table('sq_fwo_budgets')->insertGetId([
                'id_sq_fwo' => $request->id_sq_fwo,
                'label' => $request->label,
                'keterangan' => $request->keterangan,
                'hari_mulai' => $request->hari_mulai ?: null,
                'hari_selesai' => $request->hari_selesai ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($request->items as $item) {
                DB::table('sq_fwo_budget_items')->insert([
                    'id_sq_budget' => $id,
                    'id_account' => $item['id_account'],
                    'nominal_budget' => $item['nominal_budget'],
                    'keterangan' => $item['keterangan'] ?? null,
                    'is_cash_advance' => $item['is_cash_advance'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $id;
        });

        $after = DB::table('sq_fwo_budgets')->where('id_sq_budget', $id)->get()->toJson();
        saveAudit('sq_fwo_budgets', $id, 'Create', '', $after);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function show($id)
    {
        $budget = DB::table('sq_fwo_budgets')->where('id_sq_budget', $id)->first();
        if (!$budget) return response()->json(['message' => 'Tidak ditemukan'], 404);

        $budget->items = DB::table('sq_fwo_budget_items as bi')
            ->join('budget_accounts as ba', 'ba.id_account', '=', 'bi.id_account')
            ->where('bi.id_sq_budget', $id)
            ->get(['bi.id_sq_budget_item', 'bi.id_account', 'ba.nama as nama_account', 'bi.nominal_budget', 'bi.keterangan', 'bi.is_cash_advance']);

        return response()->json($budget);
    }

    public function update(Request $request, $id)
    {
        if ($lock = SqLock::byFwoBudget($id)) return $lock;
        $budget = DB::table('sq_fwo_budgets')->where('id_sq_budget', $id)->first();
        if (!$budget) return response()->json(['message' => 'Tidak ditemukan'], 404);

        $request->validate([
            'label' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'hari_mulai' => 'nullable|integer|min:1',
            'hari_selesai' => 'nullable|integer|min:1',
            'items' => 'required|array|min:1',
            'items.*.id_account' => 'required|integer',
            'items.*.nominal_budget' => 'required|integer|min:0',
            'items.*.keterangan' => 'nullable|string',
        ]);

        $before = DB::table('sq_fwo_budgets')->where('id_sq_budget', $id)->get()->toJson();

        DB::transaction(function () use ($request, $id) {
            DB::table('sq_fwo_budgets')->where('id_sq_budget', $id)->update([
                'label' => $request->label,
                'keterangan' => $request->keterangan,
                'hari_mulai' => $request->hari_mulai ?: null,
                'hari_selesai' => $request->hari_selesai ?: null,
                'updated_at' => now(),
            ]);

            $keepIds = collect($request->items)->pluck('id_sq_budget_item')->filter()->values();

            DB::table('sq_fwo_budget_items')
                ->where('id_sq_budget', $id)
                ->whereNotIn('id_sq_budget_item', $keepIds->isNotEmpty() ? $keepIds->toArray() : [0])
                ->delete();

            foreach ($request->items as $item) {
                if (!empty($item['id_sq_budget_item'])) {
                    DB::table('sq_fwo_budget_items')->where('id_sq_budget_item', $item['id_sq_budget_item'])->update([
                        'id_account' => $item['id_account'],
                        'nominal_budget' => $item['nominal_budget'],
                        'keterangan' => $item['keterangan'] ?? null,
                        'is_cash_advance' => $item['is_cash_advance'] ?? 0,
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('sq_fwo_budget_items')->insert([
                        'id_sq_budget' => $id,
                        'id_account' => $item['id_account'],
                        'nominal_budget' => $item['nominal_budget'],
                        'keterangan' => $item['keterangan'] ?? null,
                        'is_cash_advance' => $item['is_cash_advance'] ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        $after = DB::table('sq_fwo_budgets')->where('id_sq_budget', $id)->get()->toJson();
        saveAudit('sq_fwo_budgets', $id, 'Update', $before, $after);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        if ($lock = SqLock::byFwoBudget($id)) return $lock;
        $budget = DB::table('sq_fwo_budgets')->where('id_sq_budget', $id)->first();
        if (!$budget) return response()->json(['message' => 'Tidak ditemukan'], 404);

        $before = DB::table('sq_fwo_budgets')->where('id_sq_budget', $id)->get()->toJson();
        DB::table('sq_fwo_budget_items')->where('id_sq_budget', $id)->delete();
        DB::table('sq_fwo_budgets')->where('id_sq_budget', $id)->delete();
        saveAudit('sq_fwo_budgets', $id, 'Delete', $before, '');

        return response()->json(['success' => true]);
    }
}
