<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
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

    public function data()
    {
        $query = DB::table('termin')->select([
            'id_termin',
            'no_termin',
            'nomor',
            'nama',
            'persentase',
            'nilai',
            'tanggal',
            'status',
            'attachment',
            'created_at',
        ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('tanggal', fn($row) => $row->tanggal ? date('d/m/Y', strtotime($row->tanggal)) : '-')
            ->editColumn('nilai', fn($row) => number_format($row->nilai, 0, ',', '.'))
            ->editColumn('persentase', fn($row) => $row->persentase . '%')
            ->editColumn('status', function ($row) {
                $badge = match ($row->status) {
                    'selesai' => 'success',
                    'proses'  => 'warning',
                    default   => 'secondary',
                };
                return '<span class="badge bg-' . $badge . '">' . ucfirst($row->status) . '</span>';
            })
            ->rawColumns(['status'])
            ->make(true);
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
            'status'        => 'required|in:pending,proses,selesai',
            'keterangan'    => 'nullable|string',
            'id_so'         => 'nullable|integer',
            'attachments.*' => 'nullable|file|max:5120',
        ]);

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
            ->select('op.id_output', 'op.judul_output', 'op.judul_dokumen', 'wo.no_wo', 'wo.id_wo')
            ->orderBy('wo.no_wo')
            ->orderBy('op.id_output')
            ->get();

        return response()->json($outputs);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nomor'      => 'required|string|max:50',
            'nama'       => 'required|string|max:255',
            'persentase' => 'nullable|numeric|min:0|max:100',
            'nilai'      => 'required|numeric|min:0',
            'tanggal'    => 'required|date',
            'status'     => 'required|in:pending,proses,selesai',
            'keterangan' => 'nullable|string',
            'id_so'      => 'nullable|integer',
        ]);

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
            'nomor'      => $request->nomor,
            'nama'       => $request->nama,
            'persentase' => $request->persentase,
            'nilai'      => $request->nilai,
            'tanggal'    => $request->tanggal,
            'status'     => $request->status,
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
