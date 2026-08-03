<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasAuditHistory;
use Spatie\LaravelPdf\Facades\Pdf;

class FwoBudgetController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'fwo_budgets'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_budget']; }

    public function listByFwo($id_fwo)
    {
        $budgets = DB::table('fwo_budgets as b')
            ->where('b.id_fwo', $id_fwo)
            ->whereNull('b.deleted_at')
            ->orderBy('b.created_at')
            ->get(['b.id_budget', 'b.label', 'b.keterangan', 'b.tanggal_mulai', 'b.tanggal_selesai', 'b.status', 'b.dokumen_realisasi', 'b.created_at']);

        foreach ($budgets as $budget) {
            $items = DB::table('fwo_budget_items as bi')
                ->join('budget_accounts as ba', 'ba.id_account', '=', 'bi.id_account')
                ->leftJoin('budget_accounts as bp', 'bp.id_account', '=', 'ba.id_parent')
                ->where('bi.id_budget', $budget->id_budget)
                ->get([
                    'bi.id_budget_item',
                    'bi.id_account',
                    'ba.nama as nama_account',
                    'ba.kode as kode_account',
                    'bp.nama as nama_category',
                    'bi.nominal_budget',
                    'bi.keterangan',
                    'bi.is_cash_advance',
                ]);

            foreach ($items as $item) {
                $actuals = DB::table('fwo_budget_actuals')
                    ->where('id_budget_item', $item->id_budget_item)
                    ->get(['id_actual', 'nominal_actual', 'keterangan', 'attachments', 'created_at',
                           'status_verifikasi', 'catatan_verifikasi', 'verified_at']);

                $item->nominal_actual = $actuals->sum('nominal_actual');
                $item->actuals        = $actuals;
            }

            $budget->items         = $items;
            $budget->total_budget  = $items->sum('nominal_budget');
            $budget->total_actual  = $items->sum('nominal_actual');
        }

        $fwo = DB::table('fieldworks')->where('id_fwo', $id_fwo)->first(['status']);

        return response()->json([
            'data'       => $budgets,
            'fwo_status' => $fwo->status ?? null,
        ]);
    }

    private function fwoIsCompleted($id_fwo): bool
    {
        $fwo = DB::table('fieldworks')->where('id_fwo', $id_fwo)->first(['status']);
        return $fwo && $fwo->status === 'completed';
    }

    public function store(Request $request)
    {
        if ($this->fwoIsCompleted($request->id_fwo)) {
            return response()->json(['success' => false, 'message' => 'FWO sudah selesai, tidak dapat menambah budget.'], 403);
        }

        $request->validate([
            'id_fwo'         => 'required|integer',
            'label'          => 'required|string|max:255',
            'keterangan'     => 'nullable|string',
            'items'          => 'required|array|min:1',
            'items.*.id_account'      => 'required|integer',
            'items.*.nominal_budget'  => 'required|integer|min:0',
            'items.*.keterangan'      => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, &$id) {
            $id = DB::table('fwo_budgets')->insertGetId([
                'id_fwo'          => $request->id_fwo,
                'label'           => $request->label,
                'keterangan'      => $request->keterangan,
                'tanggal_mulai'   => $request->tanggal_mulai ?: null,
                'tanggal_selesai' => $request->tanggal_selesai ?: null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            foreach ($request->items as $item) {
                DB::table('fwo_budget_items')->insert([
                    'id_budget'       => $id,
                    'id_account'      => $item['id_account'],
                    'nominal_budget'  => $item['nominal_budget'],
                    'keterangan'      => $item['keterangan'] ?? null,
                    'is_cash_advance' => $item['is_cash_advance'] ?? 0,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        });

        $after = DB::table('fwo_budgets')->where('id_budget', $id)->get()->toJson();
        saveAudit('fwo_budgets', $id, 'Create', '', $after);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function show($id)
    {
        $budget = DB::table('fwo_budgets')->where('id_budget', $id)->first();
        if (!$budget) return response()->json(['message' => 'Tidak ditemukan'], 404);

        $budget->items = DB::table('fwo_budget_items as bi')
            ->join('budget_accounts as ba', 'ba.id_account', '=', 'bi.id_account')
            ->where('bi.id_budget', $id)
            ->get([
                'bi.id_budget_item',
                'bi.id_account',
                'ba.nama as nama_account',
                'bi.nominal_budget',
                'bi.keterangan',
            ]);

        return response()->json($budget);
    }

    public function update(Request $request, $id)
    {
        $budget = DB::table('fwo_budgets')->where('id_budget', $id)->first();
        if (!$budget) return response()->json(['message' => 'Tidak ditemukan'], 404);

        if ($this->fwoIsCompleted($budget->id_fwo)) {
            return response()->json(['success' => false, 'message' => 'FWO sudah selesai, tidak dapat mengubah budget.'], 403);
        }
        if (($budget->status ?? 'open') === 'completed') {
            return response()->json(['success' => false, 'message' => 'Budget plan sudah ditutup, tidak dapat diubah.'], 403);
        }

        $request->validate([
            'label'          => 'required|string|max:255',
            'keterangan'     => 'nullable|string',
            'items'          => 'required|array|min:1',
            'items.*.id_account'     => 'required|integer',
            'items.*.nominal_budget' => 'required|integer|min:0',
            'items.*.keterangan'     => 'nullable|string',
        ]);

        $before = DB::table('fwo_budgets')->where('id_budget', $id)->get()->toJson();

        DB::transaction(function () use ($request, $id) {
            DB::table('fwo_budgets')->where('id_budget', $id)->update([
                'label'           => $request->label,
                'keterangan'      => $request->keterangan,
                'tanggal_mulai'   => $request->tanggal_mulai ?: null,
                'tanggal_selesai' => $request->tanggal_selesai ?: null,
                'updated_at'      => now(),
            ]);

            // Hapus items lama yang tidak punya actual, update yang ada
            $existingItemIds = collect($request->items)->pluck('id_budget_item')->filter()->values();

            // Hapus item yang dihapus user (hanya jika tidak punya actual)
            $oldItems = DB::table('fwo_budget_items')->where('id_budget', $id)->get();
            foreach ($oldItems as $old) {
                if (!$existingItemIds->contains($old->id_budget_item)) {
                    $hasActual = DB::table('fwo_budget_actuals')
                        ->where('id_budget_item', $old->id_budget_item)
                        ->exists();
                    if (!$hasActual) {
                        DB::table('fwo_budget_items')->where('id_budget_item', $old->id_budget_item)->delete();
                    }
                }
            }

            foreach ($request->items as $item) {
                if (!empty($item['id_budget_item'])) {
                    DB::table('fwo_budget_items')->where('id_budget_item', $item['id_budget_item'])->update([
                        'id_account'      => $item['id_account'],
                        'nominal_budget'  => $item['nominal_budget'],
                        'keterangan'      => $item['keterangan'] ?? null,
                        'is_cash_advance' => $item['is_cash_advance'] ?? 0,
                        'updated_at'      => now(),
                    ]);
                } else {
                    DB::table('fwo_budget_items')->insert([
                        'id_budget'       => $id,
                        'id_account'      => $item['id_account'],
                        'nominal_budget'  => $item['nominal_budget'],
                        'keterangan'      => $item['keterangan'] ?? null,
                        'is_cash_advance' => $item['is_cash_advance'] ?? 0,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }
        });

        $after = DB::table('fwo_budgets')->where('id_budget', $id)->get()->toJson();
        saveAudit('fwo_budgets', $id, 'Update', $before, $after);

        return response()->json(['success' => true]);
    }

    public function closePlan(Request $request, $id)
    {
        $budget = DB::table('fwo_budgets')->where('id_budget', $id)->first();
        if (!$budget) return response()->json(['message' => 'Tidak ditemukan'], 404);
        if ($this->fwoIsCompleted($budget->id_fwo)) {
            return response()->json(['success' => false, 'message' => 'FWO sudah selesai.'], 403);
        }

        // Hitung surplus
        $items       = DB::table('fwo_budget_items')->where('id_budget', $id)->get(['id_budget_item', 'nominal_budget']);
        $totalBudget = $items->sum('nominal_budget');
        $totalActual = 0;
        foreach ($items as $item) {
            $totalActual += DB::table('fwo_budget_actuals')->where('id_budget_item', $item->id_budget_item)->sum('nominal_actual');
        }
        $surplus = $totalBudget - $totalActual;

        $dokumenPath = null;
        if ($surplus > 0) {
            $request->validate([
                'dokumen_realisasi' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ], [
                'dokumen_realisasi.required' => 'Dokumen Laporan Realisasi wajib diupload karena ada surplus anggaran.',
            ]);
            $uploaded    = uploadAttachment([$request->file('dokumen_realisasi')], 'fwo-budget-realisasi');
            $dokumenPath = $uploaded['files'][0] ?? null;
        }

        $before = DB::table('fwo_budgets')->where('id_budget', $id)->get()->toJson();
        DB::table('fwo_budgets')->where('id_budget', $id)->update(array_filter([
            'status'             => 'completed',
            'dokumen_realisasi'  => $dokumenPath,
            'updated_at'         => now(),
        ], fn($v) => $v !== null));
        $after = DB::table('fwo_budgets')->where('id_budget', $id)->get()->toJson();
        saveAudit('fwo_budgets', $id, 'Close', $before, $after);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $budget = DB::table('fwo_budgets')->where('id_budget', $id)->first();
        if ($budget && $this->fwoIsCompleted($budget->id_fwo)) {
            return response()->json(['success' => false, 'message' => 'FWO sudah selesai, tidak dapat menghapus budget.'], 403);
        }
        if ($budget && ($budget->status ?? 'open') === 'completed') {
            return response()->json(['success' => false, 'message' => 'Budget plan sudah ditutup, tidak dapat dihapus.'], 403);
        }

        $before = DB::table('fwo_budgets')->where('id_budget', $id)->get()->toJson();

        DB::table('fwo_budgets')->where('id_budget', $id)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        saveAudit('fwo_budgets', $id, 'Delete', $before, '');

        return response()->json(['success' => true]);
    }

    public function printRealisasiPdf($id)
    {
        $budget = DB::table('fwo_budgets as b')
            ->join('fieldworks as fw', 'fw.id_fwo', '=', 'b.id_fwo')
            ->join('work_orders as wo', 'wo.id_wo', '=', 'fw.id_wo')
            ->where('b.id_budget', $id)
            ->select(['b.*', 'fw.no_fwo', 'fw.judul_pekerjaan as fwo_judul', 'wo.no_wo'])
            ->first();

        if (!$budget) abort(404, 'Budget tidak ditemukan');

        $items = DB::table('fwo_budget_items as bi')
            ->join('budget_accounts as ba', 'ba.id_account', '=', 'bi.id_account')
            ->where('bi.id_budget', $id)
            ->get(['ba.nama as nama_account', 'ba.kode as kode_account', 'bi.nominal_budget', 'bi.keterangan', 'bi.is_cash_advance', 'bi.id_budget_item']);

        foreach ($items as $item) {
            $item->total_actual = DB::table('fwo_budget_actuals')
                ->where('id_budget_item', $item->id_budget_item)
                ->sum('nominal_actual');
        }

        $totalBudget = $items->sum('nominal_budget');
        $totalActual = $items->sum('total_actual');
        $selisih     = $totalBudget - $totalActual;

        return Pdf::view('pdf.fwo-budget.realisasi', compact('budget', 'items', 'totalBudget', 'totalActual', 'selisih'))
            ->format('a4')
            ->name("Realisasi-{$budget->no_fwo}-{$budget->label}.pdf");
    }

    public function printPdf($id)
    {
        $budget = DB::table('fwo_budgets as b')
            ->join('fieldworks as fw', 'fw.id_fwo', '=', 'b.id_fwo')
            ->join('work_orders as wo', 'wo.id_wo', '=', 'fw.id_wo')
            ->where('b.id_budget', $id)
            ->select([
                'b.*',
                'fw.no_fwo',
                'fw.judul_pekerjaan as fwo_judul',
                'fw.tanggal_mulai as fwo_tanggal_mulai',
                'wo.no_wo',
            ])
            ->first();

        if (!$budget) abort(404, 'Budget tidak ditemukan');

        $items = DB::table('fwo_budget_items as bi')
            ->join('budget_accounts as ba', 'ba.id_account', '=', 'bi.id_account')
            ->where('bi.id_budget', $id)
            ->get([
                'ba.nama as nama_account',
                'ba.kode as kode_account',
                'bi.nominal_budget',
                'bi.keterangan',
            ]);

        $total = $items->sum('nominal_budget');

        return Pdf::view('pdf.fwo-budget.print', compact('budget', 'items', 'total'))
            ->format('a4')
            ->name("Budget-{$budget->no_fwo}-{$budget->label}.pdf");
    }
}
