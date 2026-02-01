<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class BusinessRelationSiteController extends Controller
{
    public function select2(Request $request, $id)
    {
        $search = trim($request->q);

        $query = DB::table('business_relation_sites')
            ->select([
                'id_site',
                'id_br',
                'nama_lokasi',
                'alamat_lengkap',
                'provinsi',
                'kota_kabupaten',
                'kecamatan',
                'kelurahan',
                'kode_pos',
                'kawasan_bisnis',
                'gedung',
                'alamat',
                'npwp_cabang',
                'is_kantor_pusat',
                'is_aktif',
            ])
            ->where('id_br', $id);

        // search hanya jika ada keyword
        if (!empty($search)) {
            $query->where('nama_lokasi', 'like', "%{$search}%");
        }

        $sites = $query
            ->orderByDesc('is_kantor_pusat') // kantor pusat di atas
            ->orderBy('nama_lokasi')
            ->limit(10)
            ->get();

        return response()->json(
            $sites->map(function ($site) {
                return [
                    'id'              => $site->id_site,   // WAJIB utk Select2
                    'text'            => $site->nama_lokasi,

                    // auto-fill fields
                    'nama_lokasi'     => $site->nama_lokasi,
                    'alamat_lengkap'  => $site->alamat_lengkap,
                    'provinsi'        => $site->provinsi,
                    'kota_kabupaten'  => $site->kota_kabupaten,
                    'kecamatan'       => $site->kecamatan,
                    'kelurahan'       => $site->kelurahan,
                    'kode_pos'        => $site->kode_pos,
                    'kawasan_bisnis'  => $site->kawasan_bisnis,
                    'gedung'          => $site->gedung,
                    'alamat'          => $site->alamat,
                    'npwp_cabang'     => $site->npwp_cabang,
                    'is_aktif'        => $site->is_aktif,
                    'is_kantor_pusat' => $site->is_kantor_pusat,
                ];
            })
        );
    }

    public function index()
    {
        return view('business-relation-sites.index');
    }

    public function data(Request $request)
    {
        $query = DB::table('business_relation_sites as s')
            ->join('business_relations as br', 'br.id_br', '=', 's.id_br')
            ->select([
                's.id_site',
                'br.nama as nama_br',
                's.nama_lokasi',
                's.is_kantor_pusat',
                's.is_aktif',
                's.created_at',
            ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('is_kantor_pusat_label', function ($row) {
                return $row->is_kantor_pusat
                    ? '<span class="badge bg-primary">Pusat</span>'
                    : '<span class="badge bg-secondary">Cabang</span>';
            })
            ->addColumn('status_label', function ($row) {
                return $row->is_aktif
                    ? '<span class="badge bg-success">Aktif</span>'
                    : '<span class="badge bg-secondary">Non Aktif</span>';
            })
            ->addColumn('action', function ($row) {
                return '
                <div class="d-flex justify-content-center gap-1">
                    <button class="btn btn-sm btn-warning btn-edit"
                            data-id="' . $row->id_site . '"
                            title="Edit">
                        <i class="fa-solid fa-pen"></i>
                    </button>

                    <button class="btn btn-sm btn-danger btn-delete"
                            data-id="' . $row->id_site . '"
                            title="Delete">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            ';
            })
            ->rawColumns(['is_kantor_pusat_label', 'status_label', 'action'])
            ->make(true);
    }

    public function show($id)
    {
        $data = DB::table('business_relation_sites')
            ->where('id_site', $id)
            ->first();

        if (!$data) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        // =========================
        // VALIDASI
        // =========================
        $validated = $request->validate([
            'nama_lokasi'     => 'required|string|max:255',
            'alamat_lengkap'  => 'nullable|string',
            'provinsi'        => 'nullable|string|max:100',
            'kota_kabupaten'  => 'nullable|string|max:100',
            'kecamatan'       => 'nullable|string|max:100',
            'kelurahan'       => 'nullable|string|max:100',
            'kode_pos'        => 'nullable|string|max:10',
            'kawasan_bisnis'  => 'nullable|string|max:150',
            'gedung'          => 'nullable|string|max:150',
            'alamat'          => 'nullable|string|max:255',
            'npwp_cabang'     => 'nullable|string|max:50',
            'is_aktif'        => 'required|in:0,1',
            'is_kantor_pusat' => 'required|in:0,1',
        ]);

        // =========================
        // AMBIL DATA EXISTING
        // =========================
        $site = DB::table('business_relation_sites')
            ->where('id_site', $id)
            ->first();

        if (!$site) {
            return response()->json([
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        // =========================
        // RULE: KANTOR PUSAT
        // (tidak boleh dimatikan)
        // =========================
        if ($site->is_kantor_pusat == 1 && $request->is_aktif == 0) {
            return response()->json([
                'message' => 'Kantor pusat tidak boleh dinonaktifkan'
            ], 422);
        }

        // =========================
        // UPDATE DATA
        // =========================
        DB::table('business_relation_sites')
            ->where('id_site', $id)
            ->update([
                'nama_lokasi'     => $request->nama_lokasi,
                'alamat_lengkap'  => $request->alamat_lengkap,
                'provinsi'        => $request->provinsi,
                'kota_kabupaten'  => $request->kota_kabupaten,
                'kecamatan'       => $request->kecamatan,
                'kelurahan'       => $request->kelurahan,
                'kode_pos'        => $request->kode_pos,
                'kawasan_bisnis'  => $request->kawasan_bisnis,
                'gedung'          => $request->gedung,
                'alamat'          => $request->alamat,
                'npwp_cabang'     => $request->npwp_cabang,
                'is_kantor_pusat' => $request->is_kantor_pusat,
                'is_aktif'        => $request->is_aktif,
                'updated_at'      => now(),
            ]);

        return response()->json([
            'message' => 'BRS berhasil diperbarui'
        ]);
    }

    public function destroy($id)
    {
        // Catatan: rule kantor pusat bisa ditambahkan di sini
        DB::table('business_relation_sites')
            ->where('id_site', $id)
            ->delete();

        return response()->json(['message' => 'BRS berhasil dihapus']);
    }
}
