<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;
use App\Traits\HasAttachment;

class ContractController extends Controller
{
    use HasAuditHistory, HasAttachment;

    protected function auditTable(): string        { return 'contracts'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_contract']; }
    protected function attachmentTable(): string   { return 'contracts'; }
    protected function attachmentPrimaryKey(): string { return 'id_contract'; }

    public function index()
    {
        return view('contracts.index', ['title' => 'Contracts']);
    }

    public function data()
    {
        $query = DB::table('contracts as c')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'c.id_business_relation')
            ->leftJoin('business_relation_contacts as pic', 'pic.id_contact', '=', 'c.id_pic_pelanggan')
            ->leftJoin('users as u', 'u.id', '=', 'c.id_pic_pramatek')
            ->select([
                'c.id_contract',
                'c.no_kontrak',
                'br.nama as nama_pelanggan',
                'c.tanggal_kontrak',
                'c.tanggal_mulai',
                'c.tanggal_selesai',
                'c.durasi_bulan',
                'c.nilai_kontrak',
                'c.status',
                'pic.nama_pic as nama_pic_pelanggan',
                'u.name as nama_pic_pramatek',
                'c.created_at',
            ]);

        return DataTables::of($query)->addIndexColumn()->make(true);
    }

    public function create()
    {
        return view('contracts.create', ['title' => 'Tambah Contract']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'no_kontrak'           => 'required|string|max:100|unique:contracts,no_kontrak',
            'id_business_relation' => 'nullable|integer',
            'tanggal_kontrak'      => 'nullable|date',
            'tanggal_mulai'        => 'nullable|date',
            'tanggal_selesai'      => 'nullable|date',
            'durasi_bulan'         => 'nullable|integer',
            'nilai_kontrak'        => 'nullable|numeric',
            'status'               => 'nullable|string|max:50',
            'id_pic_pelanggan'     => 'nullable|integer',
            'id_pic_pramatek'      => 'nullable|integer',
            'catatan'              => 'nullable|string',
            'attachments.*'        => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:5120',
        ]);

        $upload = uploadAttachment($request->file('attachments'), 'contracts');
        $files  = $upload['files'];

        $id = DB::table('contracts')->insertGetId([
            'no_kontrak'           => $request->no_kontrak,
            'id_business_relation' => $request->id_business_relation,
            'tanggal_kontrak'      => $request->tanggal_kontrak,
            'tanggal_mulai'        => $request->tanggal_mulai,
            'tanggal_selesai'      => $request->tanggal_selesai,
            'durasi_bulan'         => $request->durasi_bulan,
            'nilai_kontrak'        => $request->nilai_kontrak,
            'status'               => $request->status ?? 'draft',
            'id_pic_pelanggan'     => $request->id_pic_pelanggan,
            'id_pic_pramatek'      => $request->id_pic_pramatek,
            'catatan'              => $request->catatan,
            'attachment'           => json_encode($files),
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        $after = DB::table('contracts')->where('id_contract', $id)->get()->toJson();
        saveAudit('contracts', $id, 'create', '', $after);

        return response()->json(['success' => true, 'message' => 'Contract berhasil dibuat', 'id' => $id]);
    }

    public function show($id)
    {
        $data = DB::table('contracts as c')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'c.id_business_relation')
            ->leftJoin('business_relation_contacts as pic', 'pic.id_contact', '=', 'c.id_pic_pelanggan')
            ->leftJoin('users as u', 'u.id', '=', 'c.id_pic_pramatek')
            ->select([
                'c.*',
                'br.nama as nama_pelanggan',
                'pic.nama_pic as nama_pic_pelanggan',
                'u.name as nama_pic_pramatek',
            ])
            ->where('c.id_contract', $id)
            ->first();

        if (!$data) {
            return response()->json(['message' => 'Contract tidak ditemukan'], 404);
        }

        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'no_kontrak'           => 'required|string|max:100|unique:contracts,no_kontrak,' . $id . ',id_contract',
            'id_business_relation' => 'nullable|integer',
            'tanggal_kontrak'      => 'nullable|date',
            'tanggal_mulai'        => 'nullable|date',
            'tanggal_selesai'      => 'nullable|date',
            'durasi_bulan'         => 'nullable|integer',
            'nilai_kontrak'        => 'nullable|numeric',
            'status'               => 'nullable|string|max:50',
            'id_pic_pelanggan'     => 'nullable|integer',
            'id_pic_pramatek'      => 'nullable|integer',
            'catatan'              => 'nullable|string',
            'attachments.*'        => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:5120',
        ]);

        try {
            $before = DB::table('contracts')->where('id_contract', $id)->get()->toJson();

            $existing = $request->existing_attachments ?? [];
            $newFiles = [];

            if ($request->hasFile('attachments')) {
                $upload   = uploadAttachment($request->file('attachments'), 'contracts');
                $newFiles = $upload['files'];
            }

            DB::table('contracts')->where('id_contract', $id)->update([
                'no_kontrak'           => $request->no_kontrak,
                'id_business_relation' => $request->id_business_relation,
                'tanggal_kontrak'      => $request->tanggal_kontrak,
                'tanggal_mulai'        => $request->tanggal_mulai,
                'tanggal_selesai'      => $request->tanggal_selesai,
                'durasi_bulan'         => $request->durasi_bulan,
                'nilai_kontrak'        => $request->nilai_kontrak,
                'status'               => $request->status ?? 'draft',
                'id_pic_pelanggan'     => $request->id_pic_pelanggan,
                'id_pic_pramatek'      => $request->id_pic_pramatek,
                'catatan'              => $request->catatan,
                'attachment'           => json_encode(array_merge($existing, $newFiles)),
                'updated_at'           => now(),
            ]);

            $after = DB::table('contracts')->where('id_contract', $id)->get()->toJson();
            saveAudit('contracts', $id, 'update', $before, $after);

            return response()->json(['success' => true, 'message' => 'Contract berhasil diperbarui']);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $contract = DB::table('contracts')->where('id_contract', $id)->first();

        if ($contract && $contract->attachment) {
            $files = json_decode($contract->attachment, true) ?? [];
            foreach ($files as $file) {
                Storage::disk('public')->delete($file);
            }
        }

        DB::table('contracts')->where('id_contract', $id)->delete();

        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function detail($id)
    {
        return $this->show($id);
    }

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('contracts as c')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'c.id_business_relation')
            ->where('c.no_kontrak', 'like', "%{$search}%")
            ->orWhere('br.nama', 'like', "%{$search}%")
            ->select('c.id_contract', 'c.no_kontrak', 'br.nama as nama_pelanggan')
            ->limit(10)
            ->get();

        return response()->json(
            $data->map(fn($item) => [
                'id'   => $item->id_contract,
                'text' => $item->no_kontrak . ' — ' . ($item->nama_pelanggan ?? '-'),
            ])
        );
    }

    public function select2byid(Request $request)
    {
        $search = $request->q;

        $data = DB::table('contracts as c')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'c.id_business_relation')
            ->where('c.id_contract', $search)
            ->select('c.id_contract', 'c.no_kontrak', 'br.nama as nama_pelanggan')
            ->limit(10)
            ->get();

        return response()->json(
            $data->map(fn($item) => [
                'id'   => $item->id_contract,
                'text' => $item->no_kontrak . ' — ' . ($item->nama_pelanggan ?? '-'),
            ])
        );
    }
}
