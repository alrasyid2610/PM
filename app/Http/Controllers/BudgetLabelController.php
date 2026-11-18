<?php

namespace App\Http\Controllers;

use App\Support\BudgetLabel;
use App\Support\ListFilter\Sets\BudgetLabelFilters;
use App\Traits\HasAuditHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

/**
 * Master Label Budget (sejak 2026-11-19): daftar label yang boleh dipakai di Budget Plan (WO, FWO, SQ WO, SQ FWO).
 * Label tidak lagi diketik bebas — dipilih dari sini. Plan menyimpan `id_budget_label` + salinan teks di kolom `label`;
 * mengubah nama di sini ikut memperbarui salinan teks itu. Pola sama dengan Master Output.
 */
class BudgetLabelController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'budget_label'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_budget_label']; }

    public function index()
    {
        return view('budget-label.index', ['title' => 'Master Label Budget']);
    }

    public function data(Request $request)
    {
        $set     = new BudgetLabelFilters();
        $filters = $set->normalize($request->input('filters'));

        $dipakai = [];
        foreach (array_keys(BudgetLabel::PLAN_TABLES) as $tbl) {
            $soft = in_array($tbl, ['wo_budgets', 'fwo_budgets'], true) ? ' AND p.deleted_at IS NULL' : '';
            $dipakai[] = "(SELECT COUNT(*) FROM {$tbl} p WHERE p.id_budget_label = m.id_budget_label{$soft})";
        }

        $query = DB::table('budget_label as m')
            ->select([
                'm.id_budget_label',
                'm.nama',
                'm.urutan',
                'm.is_aktif',
                DB::raw(implode(' + ', $dipakai) . ' AS dipakai'),
                'm.created_at',
            ])
            ->tap(fn ($q) => $set->apply($q, $filters))
            ->orderBy('m.urutan')
            ->orderBy('m.nama');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('is_aktif', fn ($r) => $r->is_aktif ? 'Aktif' : 'Tidak Aktif')
            ->make(true);
    }

    /** Label aktif untuk select di modal Budget Plan (dimuat sekali oleh BudgetSections.initLabelSelect). */
    public function select2()
    {
        return response()->json(BudgetLabel::daftar());
    }

    public function create()
    {
        return view('budget-label.create', ['title' => 'Tambah Master Label Budget']);
    }

    /** Rapikan spasi sebelum divalidasi / disimpan. */
    private function normalisasi(Request $request): void
    {
        $request->merge(['nama' => trim(preg_replace('/\s+/u', ' ', (string) $request->nama))]);
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'nama'     => ['required', 'string', 'max:255', Rule::unique('budget_label', 'nama')->ignore($ignoreId, 'id_budget_label')],
            'urutan'   => 'nullable|integer|min:0|max:9999',
            'is_aktif' => 'nullable|boolean',
        ];
    }

    private const MESSAGES = [
        'nama.unique' => 'Label ini sudah ada di Master Label Budget.',
    ];

    public function store(Request $request)
    {
        $this->normalisasi($request);
        $v = $request->validate($this->rules(), self::MESSAGES);

        $id = DB::table('budget_label')->insertGetId([
            'nama'       => $v['nama'],
            'urutan'     => (int) ($v['urutan'] ?? 0),
            'is_aktif'   => $request->boolean('is_aktif', true) ? 1 : 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_budget_label');

        saveAudit('budget_label', $id, 'Create', '', DB::table('budget_label')->where('id_budget_label', $id)->get()->toJson());

        return response()->json(['success' => true, 'message' => 'Label budget berhasil ditambahkan', 'id' => $id]);
    }

    public function show($id)
    {
        $row = DB::table('budget_label')->where('id_budget_label', $id)->first();
        if (!$row) {
            return response()->json(['message' => 'Label budget tidak ditemukan'], 404);
        }

        $row->dipakai = BudgetLabel::pemakaian((int) $id);

        return response()->json($row);
    }

    public function detail($id)
    {
        return $this->show($id);
    }

    public function update(Request $request, $id)
    {
        $row = DB::table('budget_label')->where('id_budget_label', $id)->first();
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Label budget tidak ditemukan'], 404);
        }

        $this->normalisasi($request);
        $v = $request->validate($this->rules((int) $id), self::MESSAGES);

        $before = DB::table('budget_label')->where('id_budget_label', $id)->get()->toJson();

        DB::transaction(function () use ($id, $row, $v, $request) {
            DB::table('budget_label')->where('id_budget_label', $id)->update([
                'nama'       => $v['nama'],
                'urutan'     => (int) ($v['urutan'] ?? 0),
                'is_aktif'   => $request->boolean('is_aktif') ? 1 : 0,
                'updated_at' => now(),
            ]);

            // Ubah nama → salinan teks di semua plan yang memakainya ikut diperbarui (notifikasi, nama file PDF, dll)
            if ($v['nama'] !== $row->nama) {
                foreach (array_keys(BudgetLabel::PLAN_TABLES) as $tbl) {
                    DB::table($tbl)->where('id_budget_label', $id)->update(['label' => $v['nama']]);
                }
            }
        });

        saveAudit('budget_label', $id, 'update', $before, DB::table('budget_label')->where('id_budget_label', $id)->get()->toJson());

        return response()->json(['success' => true, 'message' => 'Label budget berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $row = DB::table('budget_label')->where('id_budget_label', $id)->first();
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Label budget tidak ditemukan'], 404);
        }

        $dipakai = BudgetLabel::pemakaian((int) $id);
        if ($dipakai > 0) {
            return response()->json([
                'success' => false,
                'message' => "Label ini dipakai {$dipakai} budget plan sehingga tidak bisa dihapus. Nonaktifkan saja (plan lama tetap valid).",
            ], 422);
        }

        $before = DB::table('budget_label')->where('id_budget_label', $id)->get()->toJson();
        DB::table('budget_label')->where('id_budget_label', $id)->delete();
        saveAudit('budget_label', $id, 'delete', $before, '');

        return response()->json(['success' => true, 'message' => 'Label budget berhasil dihapus']);
    }

    public function history($id)
    {
        return $this->auditHistory($id);
    }
}
