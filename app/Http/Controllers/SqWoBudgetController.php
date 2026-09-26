<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasAuditHistory;

/**
 * SQ Work Order — tab Budget (estimasi). Sama pola dengan WoBudgetController
 * (1 request = 1 Plan + seluruh Item-nya, item di-diff saat update), TAPI
 * TANPA Realisasi/Actual, status, print, atau closePlan — SQ tidak pernah
 * punya realisasi (keputusan Fase 0). Tabel: sq_wo_budgets, sq_wo_budget_items
 * (sudah dibuat migration Fase 1). Lihat Obsidian Modules/Sales Quotation.md.
 */
class SqWoBudgetController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'sq_wo_budgets'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_sq_budget']; }

    public function listByWo($id_sq_wo)
    {
        $budgets = DB::table('sq_wo_budgets as b')
            ->where('b.id_sq_wo', $id_sq_wo)
            ->orderBy('b.created_at')
            ->get(['b.id_sq_budget', 'b.label', 'b.keterangan', 'b.hari_mulai', 'b.hari_selesai']);

        foreach ($budgets as $budget) {
            $items = DB::table('sq_wo_budget_items as bi')
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
        $request->validate([
            'id_sq_wo' => 'required|integer|exists:sq_work_orders,id_sq_wo',
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
            $id = DB::table('sq_wo_budgets')->insertGetId([
                'id_sq_wo' => $request->id_sq_wo,
                'label' => $request->label,
                'keterangan' => $request->keterangan,
                'hari_mulai' => $request->hari_mulai ?: null,
                'hari_selesai' => $request->hari_selesai ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($request->items as $item) {
                DB::table('sq_wo_budget_items')->insert([
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

        $after = DB::table('sq_wo_budgets')->where('id_sq_budget', $id)->get()->toJson();
        saveAudit('sq_wo_budgets', $id, 'Create', '', $after);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function show($id)
    {
        $budget = DB::table('sq_wo_budgets')->where('id_sq_budget', $id)->first();
        if (!$budget) return response()->json(['message' => 'Tidak ditemukan'], 404);

        $budget->items = DB::table('sq_wo_budget_items as bi')
            ->join('budget_accounts as ba', 'ba.id_account', '=', 'bi.id_account')
            ->where('bi.id_sq_budget', $id)
            ->get(['bi.id_sq_budget_item', 'bi.id_account', 'ba.nama as nama_account', 'bi.nominal_budget', 'bi.keterangan', 'bi.is_cash_advance']);

        return response()->json($budget);
    }

    public function update(Request $request, $id)
    {
        $budget = DB::table('sq_wo_budgets')->where('id_sq_budget', $id)->first();
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

        $before = DB::table('sq_wo_budgets')->where('id_sq_budget', $id)->get()->toJson();

        DB::transaction(function () use ($request, $id) {
            DB::table('sq_wo_budgets')->where('id_sq_budget', $id)->update([
                'label' => $request->label,
                'keterangan' => $request->keterangan,
                'hari_mulai' => $request->hari_mulai ?: null,
                'hari_selesai' => $request->hari_selesai ?: null,
                'updated_at' => now(),
            ]);

            $keepIds = collect($request->items)->pluck('id_sq_budget_item')->filter()->values();

            DB::table('sq_wo_budget_items')
                ->where('id_sq_budget', $id)
                ->whereNotIn('id_sq_budget_item', $keepIds->isNotEmpty() ? $keepIds->toArray() : [0])
                ->delete();

            foreach ($request->items as $item) {
                if (!empty($item['id_sq_budget_item'])) {
                    DB::table('sq_wo_budget_items')->where('id_sq_budget_item', $item['id_sq_budget_item'])->update([
                        'id_account' => $item['id_account'],
                        'nominal_budget' => $item['nominal_budget'],
                        'keterangan' => $item['keterangan'] ?? null,
                        'is_cash_advance' => $item['is_cash_advance'] ?? 0,
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('sq_wo_budget_items')->insert([
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

        $after = DB::table('sq_wo_budgets')->where('id_sq_budget', $id)->get()->toJson();
        saveAudit('sq_wo_budgets', $id, 'Update', $before, $after);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $budget = DB::table('sq_wo_budgets')->where('id_sq_budget', $id)->first();
        if (!$budget) return response()->json(['message' => 'Tidak ditemukan'], 404);

        $before = DB::table('sq_wo_budgets')->where('id_sq_budget', $id)->get()->toJson();
        DB::table('sq_wo_budget_items')->where('id_sq_budget', $id)->delete();
        DB::table('sq_wo_budgets')->where('id_sq_budget', $id)->delete();
        saveAudit('sq_wo_budgets', $id, 'Delete', $before, '');

        return response()->json(['success' => true]);
    }
}
