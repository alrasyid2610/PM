<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;

/**
 * Master "Bank Account" — daftar rekening bank (no. rekening, atas nama, bank, keterangan).
 */
class BankAccountController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'bank_accounts'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_bank_account']; }

    private function rules(): array
    {
        return [
            'no_rekening' => 'required|string|max:50',
            'atas_nama'   => 'required|string|max:150',
            'bank'        => 'required|string|max:100',
            'keterangan'  => 'nullable|string',
        ];
    }

    public function index()
    {
        return view('bank-accounts.index', ['title' => 'Bank Account']);
    }

    public function data()
    {
        $query = DB::table('bank_accounts')
            ->whereNull('deleted_at')
            ->orderBy('bank')
            ->orderBy('atas_nama')
            ->select(['id_bank_account', 'bank', 'no_rekening', 'atas_nama', 'keterangan', 'is_utama', 'is_ppn', 'is_aktif']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('is_utama', fn ($row) => $row->is_utama
                ? '<span class="pm-badge pm-badge--blue">Utama</span>'
                : '<span class="text-muted">—</span>')
            ->editColumn('is_ppn', fn ($row) => $row->is_ppn
                ? '<span class="pm-badge pm-badge--blue">PPN</span>'
                : '<span class="text-muted">—</span>')
            ->editColumn('is_aktif', fn ($row) => $row->is_aktif
                ? '<span class="pm-badge pm-badge--green">Aktif</span>'
                : '<span class="pm-badge pm-badge--gray">Nonaktif</span>')
            ->rawColumns(['is_utama', 'is_ppn', 'is_aktif'])
            ->make(true);
    }

    /** Rekening utama maksimal 1: menjadikan satu rekening utama melepas penanda di rekening lain. */
    private function setUtama(int $id, bool $utama): void
    {
        if ($utama) {
            DB::table('bank_accounts')->where('id_bank_account', '!=', $id)->where('is_utama', 1)->update(['is_utama' => 0, 'updated_at' => now()]);
        }
        DB::table('bank_accounts')->where('id_bank_account', $id)->update(['is_utama' => $utama ? 1 : 0]);
    }

    public function create()
    {
        return view('bank-accounts.create', ['title' => 'Tambah Bank Account']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $id = DB::table('bank_accounts')->insertGetId([
            'no_rekening' => $validated['no_rekening'],
            'atas_nama'   => $validated['atas_nama'],
            'bank'        => $validated['bank'],
            'keterangan'  => $validated['keterangan'] ?? null,
            'is_ppn'      => $request->boolean('is_ppn') ? 1 : 0,
            'is_aktif'    => $request->boolean('is_aktif') ? 1 : 0,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
        $this->setUtama($id, $request->boolean('is_utama') && $request->boolean('is_aktif'));

        $after = DB::table('bank_accounts')->where('id_bank_account', $id)->get()->toJson();
        saveAudit('bank_accounts', $id, 'Create', '', $after);

        return response()->json(['success' => true, 'message' => 'Bank Account berhasil dibuat', 'id' => $id]);
    }

    public function show($id)
    {
        $data = DB::table('bank_accounts')->where('id_bank_account', $id)->whereNull('deleted_at')->first();
        if (!$data) return response()->json(['message' => 'Bank Account tidak ditemukan'], 404);

        return response()->json($data);
    }

    public function detail($id)
    {
        return $this->show($id);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate($this->rules());

        $before = DB::table('bank_accounts')->where('id_bank_account', $id)->get()->toJson();

        DB::table('bank_accounts')->where('id_bank_account', $id)->update([
            'no_rekening' => $validated['no_rekening'],
            'atas_nama'   => $validated['atas_nama'],
            'bank'        => $validated['bank'],
            'keterangan'  => $validated['keterangan'] ?? null,
            'is_ppn'      => $request->boolean('is_ppn') ? 1 : 0,
            'is_aktif'    => $request->boolean('is_aktif') ? 1 : 0,
            'updated_at'  => now(),
        ]);
        $this->setUtama((int) $id, $request->boolean('is_utama') && $request->boolean('is_aktif'));

        $after = DB::table('bank_accounts')->where('id_bank_account', $id)->get()->toJson();
        saveAudit('bank_accounts', $id, 'update', $before, $after);

        return response()->json(['success' => true, 'message' => 'Bank Account berhasil diperbarui']);
    }

    public function destroy($id)
    {
        // Rekening yang sudah dipakai SQ (status apa pun) tidak boleh dihapus; nonaktifkan saja
        $sqDraft = \App\Support\SqKeterangan::sqPemakaiRekening((int) $id);
        if ($sqDraft) {
            return response()->json([
                'message' => 'Rekening sudah dipakai di SQ: ' . implode(', ', array_slice($sqDraft, 0, 5))
                    . (count($sqDraft) > 5 ? ' dan ' . (count($sqDraft) - 5) . ' lainnya' : '')
                    . '. Rekening yang sudah dipakai tidak bisa dihapus, nonaktifkan saja bila tidak dipakai lagi.',
            ], 422);
        }

        $before = DB::table('bank_accounts')->where('id_bank_account', $id)->get()->toJson();
        DB::table('bank_accounts')->where('id_bank_account', $id)->update(['deleted_at' => now()]);
        $after = DB::table('bank_accounts')->where('id_bank_account', $id)->get()->toJson();
        saveAudit('bank_accounts', $id, 'delete', $before, $after);

        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    // Untuk dipakai modul lain nanti (load-all-upfront)
    public function select2(Request $request)
    {
        $data = DB::table('bank_accounts')
            ->whereNull('deleted_at')
            ->where('is_aktif', 1)
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('bank', 'like', "%{$s}%")
                ->orWhere('no_rekening', 'like', "%{$s}%")
                ->orWhere('atas_nama', 'like', "%{$s}%")))
            ->orderBy('bank')
            ->get();

        return response()->json(
            $data->map(fn ($item) => [
                'id'   => $item->id_bank_account,
                'text' => "{$item->bank} — {$item->no_rekening} a.n. {$item->atas_nama}",
            ])
        );
    }

    public function history($id)
    {
        return $this->auditHistory($id);
    }
}
