<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class BusinessRelationController extends Controller
{
    public function index()
    {
        return view('business-relation.index');
    }

    public function create()
    {
        return view('business-relation.create');
    }


    public function data(Request $request)
    {
        // =========================
        // QUERY DASAR
        // =========================
        $query = DB::table('business_relations');

        // =========================
        // FILTER DARI ADVANCE SEARCH
        // =========================
        if ($request->filter_type === 'id' && !empty($request->filter_value)) {
            // Filter BR existing (berdasarkan ID)
            $query->where('id_br', $request->filter_value);
        }

        if ($request->filter_type === 'text' && !empty($request->filter_value)) {
            // Filter BR berdasarkan teks ketikan
            $query->where('nama', 'like', '%' . $request->filter_value . '%');
        }

        // =========================
        // DATATABLE RESPONSE
        // =========================
        return DataTables::of($query)
            ->addIndexColumn()

            ->editColumn('nama', function ($row) {
                $prefix = match ($row->entitas) {
                    'Perseroan Terbatas' => 'PT.',
                    'Commanditaire Vennootschap' => 'CV.',
                    'Firma' => 'FA.',
                    'Koperasi' => 'KOP.',
                    default => ''
                };

                return trim($prefix . ' ' . $row->nama);
            })

            ->addColumn('action', function ($row) {
                return '
                <div class="d-flex justify-content-center gap-1">
                    <button class="btn btn-sm btn-warning btn-edit"
                            data-id="' . $row->id_br . '"
                            title="Edit">
                        <i class="fa-solid fa-pen"></i>
                    </button>

                    <button class="btn btn-sm btn-danger btn-delete"
                            data-id="' . $row->id_br . '"
                            title="Delete">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            ';
            })

            ->rawColumns(['action'])
            ->make(true);
    }


    public function store(Request $request)
    {
        // =========================
        // VALIDASI
        // =========================
        $validated = $request->validate([
            'id_br'   => 'nullable|exists:business_relations,id_br',
            // 'site_id' => 'nullable|exists:business_relation_sites,id_site',

            'nama' => 'required|string|max:255',

            'entitas' => 'nullable|string|max:100',
            'kepemilikan' => 'nullable|string|max:100',
            'npwp' => 'nullable|string|max:50',
            'npwp_alamat' => 'nullable|string',

            'kategori_bisnis' => 'nullable|string|max:150',
            'sub_kategori_bisnis' => 'nullable|string|max:150',
            'website' => 'nullable|string|max:255',
            'nomor_telepon' => 'nullable|string|max:50',

            'nama_lokasi' => 'required_if:site_id,null|string|max:255',
            'alamat_lengkap' => 'required_if:site_id,null|string',
        ]);


        DB::beginTransaction();

        try {
            // =========================
            // BUSINESS RELATION
            // =========================
            $brExists = !empty($request->id_br) &&
                DB::table('business_relations')
                ->where('id_br', $request->id_br)
                ->exists();

            $isNewBr = !$brExists;

            if ($isNewBr) {
                $idBr = DB::table('business_relations')->insertGetId([
                    'nama' => $request->nama,
                    'entitas' => $request->entitas,
                    'kepemilikan' => $request->kepemilikan,
                    'npwp' => $request->npwp,
                    'npwp_alamat' => $request->npwp_alamat,
                    'kategori_bisnis' => $request->kategori_bisnis,
                    'sub_kategori_bisnis' => $request->sub_kategori_bisnis,
                    'website' => $request->website,
                    'nomor_telepon' => $request->nomor_telepon,
                    'is_aktif' => $request->is_aktif ?? 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $idBr = $request->id_br; // 🔥 WAJIB
            }


            // =========================
            // SITE
            // =========================
            $siteExists = !empty($request->site_id) &&
                DB::table('business_relation_sites')
                ->where('id_site', $request->site_id)
                ->exists();

            $isNewBrSite = !$siteExists;


            if ($isNewBrSite) {
                $data = [
                    'id_br' => $idBr,
                    'nama_lokasi' => $request->nama_lokasi,
                    'is_kantor_pusat' => $isNewBr ? 1 : 0,
                    'alamat_lengkap' => $request->alamat_lengkap,
                    'provinsi' => $request->provinsi,
                    'kota_kabupaten' => $request->kota_kabupaten,
                    'kecamatan' => $request->kecamatan,
                    'kelurahan' => $request->kelurahan,
                    'kode_pos' => $request->kode_pos,
                    'kawasan_bisnis' => $request->kawasan_bisnis,
                    'gedung' => $request->gedung,
                    'alamat' => $request->alamat,
                    'npwp_cabang' => $request->npwp_cabang,
                    'is_aktif' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                DB::table('business_relation_sites')->insert($data);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data Business Relation berhasil disimpan'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem'
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        // ✅ VALIDASI SESUAI KOLOM
        $validator = Validator::make($request->all(), [
            'nama'                => 'required|string|max:255',
            'entitas'             => 'nullable|string|max:100',
            'kepemilikan'         => 'nullable|string|max:100',
            'npwp'                => 'nullable|string|max:50',
            'npwp_alamat'         => 'nullable|string',
            'kategori_bisnis'     => 'nullable|string|max:150',
            'sub_kategori_bisnis' => 'nullable|string|max:150',
            'website'             => 'nullable|string|max:255',
            'nomor_telepon'       => 'nullable|string|max:50',
            'is_aktif'            => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        // ✅ UPDATE DATA (created_at TIDAK diubah)
        $updated = DB::table('business_relations')
            ->where('id_br', $id)
            ->update([
                'nama'                => $request->nama,
                'entitas'             => $request->entitas,
                'kepemilikan'         => $request->kepemilikan,
                'npwp'                => $request->npwp,
                'npwp_alamat'         => $request->npwp_alamat,
                'kategori_bisnis'     => $request->kategori_bisnis,
                'sub_kategori_bisnis' => $request->sub_kategori_bisnis,
                'website'             => $request->website,
                'nomor_telepon'       => $request->nomor_telepon,
                'is_aktif'            => $request->is_aktif,
                'updated_at'          => now(), // ✅ WAJIB update
            ]);

        if (!$updated) {
            return response()->json([
                'message' => 'Data tidak ditemukan atau tidak ada perubahan'
            ], 404);
        }

        return response()->json([
            'message' => 'Business Relation berhasil diperbarui'
        ]);
    }


    public function show($id)
    {
        $data = DB::table('business_relations')->where('id_br', $id)->first();

        if (!$data) {
            return response()->json([
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json($data);
    }

    public function destroy($id)
    {
        $deleted = DB::table('business_relations')
            ->where('id_br', $id)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'message' => 'Data tidak ditemukan atau sudah dihapus'
            ], 404);
        }

        return response()->json([
            'message' => 'Data berhasil dihapus'
        ]);
    }


    public function select2(Request $request)
    {

        $search = $request->q;

        $data = DB::table('business_relations')
            ->where('nama', 'like', "%{$search}%")
            ->limit(10)
            ->get();

        return response()->json(
            $data->map(function ($item) {
                return [
                    'id'                   => $item->id_br,
                    'text'                 => $item->nama,
                    'entitas'              => $item->entitas,
                    'kepemilikan'          => $item->kepemilikan,
                    'npwp'                 => $item->npwp,
                    'npwp_alamat'          => $item->npwp_alamat,
                    'kategori_bisnis'      => $item->kategori_bisnis,
                    'sub_kategori_bisnis'  => $item->sub_kategori_bisnis,
                    'website'              => $item->website,
                    'nomor_telepon'        => $item->nomor_telepon,
                    'is_aktif'             => $item->is_aktif,
                ];
            })
        );
    }
}
