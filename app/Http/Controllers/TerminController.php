<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasAuditHistory;
use App\Traits\HasAttachment;

class TerminController extends Controller
{
    use HasAuditHistory, HasAttachment;

    protected function attachmentTable(): string      { return 'termin'; }
    protected function attachmentPrimaryKey(): string { return 'id_termin'; }

    protected function auditTable(): string
    {
        return 'termin';
    }

    protected function auditExcludeFields(): array
    {
        return ['updated_at', 'created_at', 'id_termin'];
    }

    public function index()
    {
        return view('termin.index', [
            'title' => 'Termin'
        ]);
    }

    public function data(Request $request)
    {
        $filters     = $request->input('filters', []);
        $isSearching = !empty($filters);

        $query = DB::table('termin as t')
            ->select([
                'id_termin',
                'status',
                'no_termin',
                'nama',
                'persentase',
                'nilai',
                'tanggal',
            ])
            ->when(!$isSearching, fn($q) => $q->where('t.status', '!=', 'selesai'))
            ->when($isSearching, function ($q) use ($filters) {
                foreach ($filters as $f) {
                    $by   = $f['by'] ?? '';
                    $term = $f['q']  ?? '';
                    if (!$by || !$term) continue;
                    match ($by) {
                        'no_termin' => $q->where('t.no_termin', 'like', "%{$term}%"),
                        'nama'      => $q->where('t.nama', 'like', "%{$term}%"),
                        'status'    => match ($term) {
                            'all'    => null,
                            default  => $q->where('t.status', $term),
                        },
                        default => null,
                    };
                }
            })
            ->orderBy('id_termin', 'desc')
            ->get()
            ->map(function ($row) {
                $row->tanggal    = $row->tanggal ? date('d/m/Y', strtotime($row->tanggal)) : '-';
                $row->nilai      = number_format($row->nilai, 0, ',', '.');
                $row->persentase = $row->persentase . '%';
                return $row;
            });

        return response()->json(['data' => $query->values()]);
    }

    public function create(Request $request)
    {
        if (!$request->filled('id_so')) {
            return redirect()->route('termin.index');
        }

        return view('termin.create', [
            'title' => 'Create Termin'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama'          => 'required|string|max:255',
            'persentase'    => 'nullable|numeric|min:0|max:100',
            'nilai'         => 'required|numeric|min:0',
            'tanggal'       => 'required|date',
            'status'        => 'required|in:pending,siap_kirim,selesai',
            'keterangan'    => 'nullable|string',
            'id_so'         => 'nullable|integer',
            'attachments.*' => 'nullable|file|max:5120',
        ]);

        if ($request->boolean('is_dp') && !empty($request->input('selected_outputs', []))) {
            return response()->json([
                'message' => 'Termin Down Payment (DP) tidak dapat memilih Output Pekerjaan.',
            ], 422);
        }

        $upload = uploadAttachment($request->file('attachments'), 'termin');
        $files  = $upload['files'];

        $id = DB::table('termin')->insertGetId([
            'id_so'      => $request->id_so ?: null,
            'no_termin'  => $this->generateNoTermin(),
            'nama'       => $request->nama,
            'persentase' => $request->persentase,
            'nilai'      => $request->nilai,
            'tanggal'    => $request->tanggal,
            'status'     => $request->status,
            'is_dp'      => $request->boolean('is_dp') ? 1 : 0,
            'keterangan' => $request->keterangan,
            'attachment' => json_encode($files),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $selectedOutputs = $request->input('selected_outputs', []);
        $judulTagihan    = $request->input('judul_tagihan', []);
        foreach ($selectedOutputs as $idOutput) {
            DB::table('output_pekerjaan')->where('id_output', $idOutput)->update([
                'id_termin'     => $id,
                'judul_tagihan' => $judulTagihan[$idOutput] ?? null,
                'updated_at'    => now(),
            ]);
        }

        $after = DB::table('termin')->where('id_termin', $id)->get()->toJson();
        saveAudit('termin', $id, 'Create', '', $after);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function show($id)
    {
        $data = DB::table('termin as t')
            ->leftJoin('sales_orders as so', 'so.id_so', '=', 't.id_so')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'so.id_pelanggan')
            ->where('t.id_termin', $id)
            ->select('t.*', 'so.no_so', 'so.judul_order', 'br.nama as nama_pelanggan_billing')
            ->first();

        if (!$data) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $data->assigned_outputs = DB::table('output_pekerjaan as op')
            ->leftJoin('work_orders as wo', 'wo.id_wo', '=', 'op.id_wo')
            ->where('op.id_termin', $id)
            ->select('op.id_output', 'op.id_wo', 'op.judul_output', 'op.judul_tagihan', 'op.status', 'wo.no_wo')
            ->orderBy('wo.no_wo')
            ->orderBy('op.id_output')
            ->get();

        return response()->json($data);
    }

    public function detail($id)
    {
        $data = DB::table('termin as t')
            ->leftJoin('sales_orders as so', 'so.id_so', '=', 't.id_so')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'so.id_pelanggan')
            ->where('t.id_termin', $id)
            ->select('t.*', 'so.no_so', 'so.judul_order', 'br.nama as nama_pelanggan_billing')
            ->first();

        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        $data->assigned_outputs = DB::table('output_pekerjaan as op')
            ->leftJoin('work_orders as wo', 'wo.id_wo', '=', 'op.id_wo')
            ->where('op.id_termin', $id)
            ->select('op.id_output', 'op.id_wo', 'op.judul_output', 'op.judul_tagihan', 'op.status', 'wo.no_wo')
            ->orderBy('wo.no_wo')
            ->orderBy('op.id_output')
            ->get();

        return response()->json($data);
    }

    public function addOutput(Request $request, $id)
    {
        $termin = DB::table('termin')->where('id_termin', $id)->first();
        if ($termin && $termin->is_dp) {
            return response()->json([
                'message' => 'Termin Down Payment (DP) tidak dapat memilih Output Pekerjaan.',
            ], 422);
        }

        $ids = array_map('intval', $request->input('ids', []));
        foreach ($ids as $idOutput) {
            DB::table('output_pekerjaan')
                ->where('id_output', $idOutput)
                ->update(['id_termin' => $id, 'updated_at' => now()]);
        }
        return response()->json(['success' => true]);
    }

    public function removeOutput(Request $request, $id)
    {
        $idOutput = (int) $request->input('id_output');
        DB::table('output_pekerjaan')
            ->where('id_output', $idOutput)
            ->where('id_termin', $id)
            ->update(['id_termin' => null, 'judul_tagihan' => null, 'updated_at' => now()]);
        return response()->json(['success' => true]);
    }

    public function siapKirim($id)
    {
        DB::table('termin')->where('id_termin', $id)->update([
            'status'     => 'siap_kirim',
            'updated_at' => now(),
        ]);
        return response()->json(['success' => true]);
    }

    public function selesai($id)
    {
        DB::table('termin')->where('id_termin', $id)->update([
            'status'     => 'selesai',
            'updated_at' => now(),
        ]);
        return response()->json(['success' => true]);
    }

    public function checkDpBySo($id_so, Request $request)
    {
        $query = DB::table('termin')->where('id_so', $id_so)->where('is_dp', 1);
        if ($request->filled('exclude_id')) {
            $query->where('id_termin', '!=', $request->exclude_id);
        }
        $exists = $query->exists();
        return response()->json(['has_dp' => $exists]);
    }

    public function outputsBySo($id_so)
    {
        $outputs = DB::table('output_pekerjaan as op')
            ->join('work_orders as wo', 'wo.id_wo', '=', 'op.id_wo')
            ->where('wo.id_so', $id_so)
            ->whereNull('wo.deleted_at')
            ->where(function ($q) {
                // Tampilkan output yang belum ditugaskan ATAU yang terminnya sudah dihapus
                $q->whereNull('op.id_termin')
                  ->orWhereNotExists(function ($sub) {
                      $sub->select(DB::raw(1))
                          ->from('termin')
                          ->whereColumn('termin.id_termin', 'op.id_termin');
                  });
            })
            ->select('op.id_output', 'op.judul_output', 'op.judul_dokumen', 'op.status', 'op.tanggal_selesai', 'wo.no_wo', 'wo.id_wo')
            ->orderBy('wo.no_wo')
            ->orderBy('op.id_output')
            ->get();

        return response()->json($outputs);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama'       => 'required|string|max:255',
            'persentase' => 'nullable|numeric|min:0|max:100',
            'nilai'      => 'required|numeric|min:0',
            'tanggal'    => 'required|date',
            'status'     => 'required|in:pending,siap_kirim,selesai',
            'keterangan' => 'nullable|string',
            'id_so'      => 'nullable|integer',
        ]);

        if ($request->boolean('is_dp') && !empty($request->input('selected_outputs', []))) {
            return response()->json([
                'message' => 'Termin Down Payment (DP) tidak dapat memilih Output Pekerjaan.',
            ], 422);
        }

        $before = DB::table('termin')->where('id_termin', $id)->get()->toJson();

        $existing = $request->existing_attachments ?? [];
        $newFiles = [];

        if ($request->hasFile('attachments')) {
            $upload   = uploadAttachment($request->file('attachments'), 'termin');
            $newFiles = $upload['files'];
        }

        $attachments = array_merge($existing, $newFiles);

        DB::table('termin')->where('id_termin', $id)->update([
            'id_so'      => $request->id_so ?: null,
            'nama'       => $request->nama,
            'persentase' => $request->persentase,
            'nilai'      => $request->nilai,
            'tanggal'    => $request->tanggal,
            'status'     => $request->status,
            'is_dp'      => $request->boolean('is_dp') ? 1 : 0,
            'keterangan' => $request->keterangan,
            'attachment' => json_encode($attachments),
            'updated_at' => now(),
        ]);

        // Sinkronisasi output pekerjaan
        $selectedOutputs = array_map('intval', $request->input('selected_outputs', []));
        $judulTagihan    = $request->input('judul_tagihan', []);

        $currentOutputIds = DB::table('output_pekerjaan')
            ->where('id_termin', $id)
            ->pluck('id_output')
            ->toArray();

        $toRemove = array_diff($currentOutputIds, $selectedOutputs);
        if ($toRemove) {
            DB::table('output_pekerjaan')
                ->whereIn('id_output', $toRemove)
                ->update(['id_termin' => null, 'judul_tagihan' => null, 'updated_at' => now()]);
        }

        foreach ($selectedOutputs as $idOutput) {
            DB::table('output_pekerjaan')->where('id_output', $idOutput)->update([
                'id_termin'     => $id,
                'judul_tagihan' => $judulTagihan[$idOutput] ?? null,
                'updated_at'    => now(),
            ]);
        }

        $after = DB::table('termin')->where('id_termin', $id)->get()->toJson();
        saveAudit('termin', $id, 'update', $before, $after);

        return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui']);
    }

    public function destroy($id)
    {
        // Lepaskan output yang ditugaskan agar bisa ditagihkan kembali
        DB::table('output_pekerjaan')
            ->where('id_termin', $id)
            ->update(['id_termin' => null, 'judul_tagihan' => null, 'updated_at' => now()]);

        DB::table('termin')->where('id_termin', $id)->delete();

        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function bySo(int $id_so)
    {
        $rows = DB::table('termin')
            ->where('id_so', $id_so)
            ->orderBy('id_termin')
            ->select(['id_termin', 'no_termin', 'nama', 'persentase', 'nilai', 'tanggal', 'status', 'created_at'])
            ->get();

        return response()->json($rows->values());
    }

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('termin')
            ->where('nomor', 'like', "%{$search}%")
            ->orWhere('nama', 'like', "%{$search}%")
            ->limit(10)
            ->get();

        return response()->json(
            $data->map(fn($item) => [
                'id'   => $item->id_termin,
                'text' => $item->nomor . ' - ' . $item->nama,
            ])
        );
    }

    private function generateNoTermin(): string
    {
        $year   = now()->format('y');
        $prefix = "ST-{$year}-";

        $latest = DB::table('termin')
            ->whereNotNull('no_termin')
            ->orderByDesc('id_termin')
            ->value('no_termin');

        if (!$latest) {
            return $prefix . '00001';
        }

        $parts  = explode('-', $latest);
        $number = (int) end($parts) + 1;
        return $prefix . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    public function history($id)
    {
        return $this->auditHistory($id);
    }

    public function deleteAttachment(Request $request)
    {
        return $this->removeAttachment($request);
    }
}
