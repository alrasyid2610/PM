<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class BusinessRelationSiteController extends Controller
{
    public function select2(Request $request, $id = '')
    {
        $search = trim($request->q);

        $query = DB::table('business_relation_sites as s')
            ->leftJoin('business_estates as be', 'be.id_bestate', '=', 's.kawasan_bisnis')
            ->leftJoin('commercial_buildings as cb', 'cb.id_building', '=', 's.gedung')
            ->select([
                's.id_site',
                's.id_br',
                's.nama_lokasi',
                's.alamat_lengkap',
                's.provinsi',
                's.kota_kabupaten',
                's.kecamatan',
                's.kelurahan',
                's.kode_pos',
                's.kawasan_bisnis',
                'be.nama as nama_kawasan_bisnis',
                's.gedung',
                'cb.nama as nama_gedung',
                's.nama_jalan',
                's.keterangan_alamat',
                's.alamat',
                's.npwp_cabang',
                's.is_kantor_pusat',
                's.is_aktif',
            ]);

        if (!empty($id)) {
            $query->where('s.id_br', $id);
        }

        if (!empty($search)) {
            $query->where('s.nama_lokasi', 'like', "%{$search}%");
        }

        $sites = $query
            ->orderByDesc('s.is_kantor_pusat')
            ->orderBy('s.nama_lokasi')
            ->limit(10)
            ->get();

        return response()->json(
            $sites->map(function ($site) {
                return [
                    'id'                   => $site->id_site,
                    'text'                 => $site->nama_lokasi,
                    'nama_lokasi'          => $site->nama_lokasi,
                    'alamat_lengkap'       => $site->alamat_lengkap,
                    'provinsi'             => $site->provinsi,
                    'kota_kabupaten'       => $site->kota_kabupaten,
                    'kecamatan'            => $site->kecamatan,
                    'kelurahan'            => $site->kelurahan,
                    'kode_pos'             => $site->kode_pos,
                    'kawasan_bisnis'       => $site->kawasan_bisnis,
                    'nama_kawasan_bisnis'  => $site->nama_kawasan_bisnis,
                    'gedung'               => $site->gedung,
                    'nama_gedung'          => $site->nama_gedung,
                    'nama_jalan'           => $site->nama_jalan,
                    'keterangan_alamat'    => $site->keterangan_alamat,
                    'alamat'               => $site->alamat,
                    'npwp_cabang'          => $site->npwp_cabang,
                    'is_aktif'             => $site->is_aktif,
                    'is_kantor_pusat'      => $site->is_kantor_pusat,
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


    public function getDataSite(Request $request)
    {
        $id_site = $request->query('id_site');

        $query = DB::table('business_relation_sites');

        if (!empty($id_site)) {
            $query->where('id_site', $id_site);
        }

        $site = $query->get();

        if ($site->isEmpty()) {
            return response()->json(['message' => 'Site tidak ditemukan'], 404);
        }

        return response()->json($site);
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
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        // =========================
        // RULE: KANTOR PUSAT
        // =========================
        if ($site->is_kantor_pusat == 1 && $validated['is_aktif'] == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Kantor pusat tidak boleh dinonaktifkan'
            ], 422);
        }

        // =========================
        // UPDATE DATA
        // =========================
        try {
            DB::beginTransaction();

            DB::table('business_relation_sites')
                ->where('id_site', $id)
                ->update([...$validated, 'updated_at' => now()]);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'BRS berhasil diperbarui'
        ]);
    }

    public function destroy($id)
    {
        // Catatan: rule kantor pusat bisa ditambahkan di sini
        DB::table('business_relation_sites')
            ->where('id_site', $id)
            ->delete();

        return response()->json(['success' => true, 'message' => 'BRS berhasil dihapus']);
    }
}
