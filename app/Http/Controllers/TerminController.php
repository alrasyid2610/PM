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

    public function create()
    {
        return view('termin.create', [
            'title' => 'Create Termin'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nomor'       => 'required|string|max:50|unique:termin,nomor',
            'nama'        => 'required|string|max:255',
            'persentase'  => 'required|numeric|min:0|max:100',
            'nilai'       => 'required|numeric|min:0',
            'tanggal'     => 'required|date',
            'status'      => 'required|in:pending,proses,selesai',
            'keterangan'  => 'nullable|string',
            'attachments.*' => 'nullable|file|max:5120',
        ]);

        $upload = uploadAttachment($request->file('attachments'), 'termin');
        $files  = $upload['files'];

        $id = DB::table('termin')->insertGetId([
            'nomor'      => $request->nomor,
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

        $after = DB::table('termin')->where('id_termin', $id)->get()->toJson();
        saveAudit('termin', $id, 'Create', '', $after);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function show($id)
    {
        $data = DB::table('termin')->where('id_termin', $id)->first();

        if (!$data) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($data);
    }

    public function detail($id)
    {
        $data = DB::table('termin')->where('id_termin', $id)->first();

        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nomor'      => 'required|string|max:50',
            'nama'       => 'required|string|max:255',
            'persentase' => 'required|numeric|min:0|max:100',
            'nilai'      => 'required|numeric|min:0',
            'tanggal'    => 'required|date',
            'status'     => 'required|in:pending,proses,selesai',
            'keterangan' => 'nullable|string',
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

        $after = DB::table('termin')->where('id_termin', $id)->get()->toJson();
        saveAudit('termin', $id, 'update', $before, $after);

        return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui']);
    }

    public function destroy($id)
    {
        DB::table('termin')->where('id_termin', $id)->delete();

        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
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

    public function history($id)
    {
        return $this->auditHistory($id);
    }

    public function deleteAttachment(Request $request)
    {
        return $this->removeAttachment($request);
    }
}
