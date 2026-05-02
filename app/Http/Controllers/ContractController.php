<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class ContractController extends Controller
{
    public function index()
    {
        return view('contracts.index', [
            'title' => 'Contracts'
        ]);
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

        return DataTables::of($query)
            ->addIndexColumn()
            ->make(true);
    }

    public function create()
    {
        return view('contracts.create', [
            'title' => 'Tambah Contract'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
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
            'attachment'           => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:5120',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')
                ->store('contracts', 'public');
        }

        $id = DB::table('contracts')->insertGetId([
            'no_kontrak'           => $validated['no_kontrak'],
            'id_business_relation' => $validated['id_business_relation'] ?? null,
            'tanggal_kontrak'      => $validated['tanggal_kontrak'] ?? null,
            'tanggal_mulai'        => $validated['tanggal_mulai'] ?? null,
            'tanggal_selesai'      => $validated['tanggal_selesai'] ?? null,
            'durasi_bulan'         => $validated['durasi_bulan'] ?? null,
            'nilai_kontrak'        => $validated['nilai_kontrak'] ?? null,
            'status'               => $validated['status'] ?? 'draft',
            'id_pic_pelanggan'     => $validated['id_pic_pelanggan'] ?? null,
            'id_pic_pramatek'      => $validated['id_pic_pramatek'] ?? null,
            'catatan'              => $validated['catatan'] ?? null,
            'attachment'           => $attachmentPath,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Contract berhasil dibuat',
            'id'      => $id
        ]);
    }

    public function show($id)
    {
        $contract = DB::table('contracts')->where('id_contract', $id)->first();

        if (!$contract) {
            return response()->json(['message' => 'Contract tidak ditemukan'], 404);
        }

        return response()->json($contract);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
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
            'attachment'           => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:5120',
        ]);

        try {
            $before = DB::table('contracts')
                ->where('id_contract', $id)
                ->get()->toJson();

            $updateData = [
                'no_kontrak'           => $validated['no_kontrak'],
                'id_business_relation' => $validated['id_business_relation'] ?? null,
                'tanggal_kontrak'      => $validated['tanggal_kontrak'] ?? null,
                'tanggal_mulai'        => $validated['tanggal_mulai'] ?? null,
                'tanggal_selesai'      => $validated['tanggal_selesai'] ?? null,
                'durasi_bulan'         => $validated['durasi_bulan'] ?? null,
                'nilai_kontrak'        => $validated['nilai_kontrak'] ?? null,
                'status'               => $validated['status'] ?? 'draft',
                'id_pic_pelanggan'     => $validated['id_pic_pelanggan'] ?? null,
                'id_pic_pramatek'      => $validated['id_pic_pramatek'] ?? null,
                'catatan'              => $validated['catatan'] ?? null,
                'updated_at'           => now(),
            ];

            // Handle file upload baru
            if ($request->hasFile('attachment')) {
                // Hapus file lama
                $old = DB::table('contracts')->where('id_contract', $id)->value('attachment');
                if ($old) Storage::disk('public')->delete($old);

                $updateData['attachment'] = $request->file('attachment')
                    ->store('contracts', 'public');
            }

            DB::table('contracts')->where('id_contract', $id)->update($updateData);

            $after = DB::table('contracts')
                ->where('id_contract', $id)
                ->get()->toJson();

            saveAudit('contracts', $id, 'update', $before, $after);

            return response()->json([
                'success' => true,
                'message' => 'Contract berhasil diperbarui'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $contract = DB::table('contracts')->where('id_contract', $id)->first();

        // Hapus file attachment jika ada
        if ($contract && $contract->attachment) {
            Storage::disk('public')->delete($contract->attachment);
        }

        DB::table('contracts')->where('id_contract', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus'
        ]);
    }

    public function detail($id)
    {
        $data = DB::table('contracts as c')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'c.id_business_relation')
            ->leftJoin('business_relation_contacts as pic', 'pic.id_contact', '=', 'c.id_pic_pelanggan')
            ->leftJoin('users as u', 'u.id', '=', 'c.id_pic_pramatek')
            ->select([
                'c.*',
                'br.nama as nama_pelanggan',
                'br.npwp as npwp_pelanggan',
                'br.nomor_telepon as telepon_pelanggan',
                'pic.nama_pic as nama_pic_pelanggan',
                'pic.email_pic as email_pic_pelanggan',
                'u.name as nama_pic_pramatek',
                'u.email as email_pic_pramatek',
            ])
            ->where('c.id_contract', $id)
            ->first();

        if (!$data) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Contract tidak ditemukan'
            ], 404);
        }

        return response()->json($data);
    }

    public function history($id)
    {
        $logs = DB::table('audit_logs')
            ->where('nama_table', 'contracts')
            ->where('row_id', $id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log) {
                $log->action = strtolower($log->action);
                return $log;
            });

        return response()->json($logs);
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
            $data->map(function ($item) {
                return [
                    'id'   => $item->id_contract,
                    'text' => $item->no_kontrak . ' — ' . ($item->nama_pelanggan ?? '-'),
                ];
            })
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
            $data->map(function ($item) {
                return [
                    'id'   => $item->id_contract,
                    'text' => $item->no_kontrak . ' — ' . ($item->nama_pelanggan ?? '-'),
                ];
            })
        );
    }
}
