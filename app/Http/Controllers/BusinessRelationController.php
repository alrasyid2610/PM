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
        $brId   = session('editing_br_id');
        $siteId = session('editing_site_id');

        $br = $brId
            ? DB::table('business_relations')->where('id_br', $brId)->first()
            : null;

        $site = $siteId
            ? DB::table('business_relation_sites')->where('id_site', $siteId)->first()
            : null;

        $bestate = DB::table('business_estates')->get();
        $commercial_buildings = DB::table('commercial_buildings')->get();
        // dd($bestate);

        return view('business-relation.create', compact('br', 'site', 'bestate', 'commercial_buildings'));
    }

    public function edit()
    {
        $brId   = session('editing_br_id');
        $siteId = session('editing_site_id');

        $br = $brId
            ? DB::table('business_relations')->where('id_br', $brId)->first()
            : null;

        $site = $siteId
            ? DB::table('business_relation_sites')->where('id_site', $siteId)->first()
            : null;

        return view('business-relation.edit', compact('br', 'site'));
    }




    public function data(Request $request)
    {
        // =========================
        // QUERY DASAR (JOIN BR + BRS)
        // =========================
        $query = DB::table('business_relation_sites as s')
            ->join('business_relations as br', 'br.id_br', '=', 's.id_br')
            ->select([
                's.id_site',
                'br.id_br',
                'br.nama',
                'br.entitas',
                's.nama_lokasi',
                's.alamat_lengkap',
                's.is_kantor_pusat',
                's.is_aktif',
                's.created_at',
            ]);

        // =========================
        // FILTER DARI ADVANCE SEARCH (BR)
        // =========================
        if ($request->filter_type === 'id' && !empty($request->filter_value)) {
            $query->where('br.id_br', $request->filter_value);
        }

        if ($request->filter_type === 'text' && !empty($request->filter_value)) {
            $query->where('br.nama', 'like', '%' . $request->filter_value . '%');
        }

        // =========================
        // DATATABLE RESPONSE
        // =========================
        return DataTables::of($query)
            ->addIndexColumn()

            // Nama BR + prefix
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

            // Tipe lokasi
            ->editColumn('is_kantor_pusat', function ($row) {
                return $row->is_kantor_pusat
                    ? '<span class="badge bg-primary">Kantor Pusat</span>'
                    : '<span class="badge bg-secondary">Cabang</span>';
            })

            // Status
            ->editColumn('is_aktif', function ($row) {
                return $row->is_aktif
                    ? '<span class="badge bg-success">Aktif</span>'
                    : '<span class="badge bg-secondary">Non Aktif</span>';
            })

            // ->addColumn('action', function ($row) {
            //     return '
            //     <div class="d-flex justify-content-center gap-1">
            //         <button class="btn btn-sm btn-warning btn-edit-site"
            //                 data-id="' . $row->id_site . '"
            //                 title="Edit Site">
            //             <i class="fa-solid fa-pen"></i>
            //         </button>

            //         <button class="btn btn-sm btn-danger btn-delete-site"
            //                 data-id="' . $row->id_site . '"
            //                 title="Delete Site">
            //             <i class="fa-solid fa-trash"></i>
            //         </button>
            //     </div>
            // ';
            // })

            ->rawColumns(['is_kantor_pusat', 'is_aktif'])
            ->make(true);
    }



    public function store(Request $request)
    {
        // dd($request->all());

        // =========================
        // VALIDASI
        // =========================
        $validated = $request->validate([
            'id_br'   => 'nullable|exists:business_relations,id_br',
            // 'site_id' => 'nullable|exists:business_relation_sites,id_site',

            'nama_br' => 'required|string|max:255',

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

        // dd($request->all());


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
                    'nama' => $request->nama_br,
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
                // dd($request->nama);
                DB::table('business_relations')
                    ->where('id_br', $request->id_br)
                    ->update([
                        'nama'                => $request->nama_br,
                        'entitas'             => $request->entitas,
                        'kepemilikan'         => $request->kepemilikan,
                        'npwp'                => $request->npwp,
                        'npwp_alamat'         => $request->npwp_alamat,
                        'kategori_bisnis'     => $request->kategori_bisnis,
                        'sub_kategori_bisnis' => $request->sub_kategori_bisnis,
                        'website'             => $request->website,
                        'nomor_telepon'       => $request->nomor_telepon,
                        'is_aktif'            => $request->is_aktif ?? 1,
                        'updated_at'          => now(),
                    ]);

                $idBr = $request->id_br;
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
            } else {
                DB::table('business_relation_sites')
                    ->where('id_site', $request->site_id)
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
                        'is_aktif'        => 1,
                        'updated_at'      => now(),
                    ]);
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


    public function update(Request $request)
    {
        $validated = $request->validate([
            'id_br'   => 'nullable|exists:business_relations,id_br',
            'nama_br' => 'required|string|max:255',
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
            //code...
            DB::table('business_relations')
                ->where('id_br', $request->id_br)
                ->update([
                    'nama'                => $request->nama_br,
                    'entitas'             => $request->entitas,
                    'kepemilikan'         => $request->kepemilikan,
                    'npwp'                => $request->npwp,
                    'npwp_alamat'         => $request->npwp_alamat,
                    'kategori_bisnis'     => $request->kategori_bisnis,
                    'sub_kategori_bisnis' => $request->sub_kategori_bisnis,
                    'website'             => $request->website,
                    'nomor_telepon'       => $request->nomor_telepon,
                    'is_aktif'            => $request->is_aktif_br ?? 1,
                    'updated_at'          => now(),
                ]);


            DB::table('business_relation_sites')
                ->where('id_site', $request->site_id)
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
                    'is_aktif'        => $request->is_aktif_site ?? 1,
                    'updated_at'      => now(),
                ]);


            DB::commit();
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
        }
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

    public function summary(Request $request)
    {
        $query = DB::table('business_relation_sites as s')
            ->join('business_relations as br', 'br.id_br', '=', 's.id_br');

        // =========================
        // FILTER DARI ADVANCE SEARCH (SAMA DENGAN DATATABLE)
        // =========================
        if ($request->filter_type === 'id' && !empty($request->filter_value)) {
            $query->where('br.id_br', $request->filter_value);
        }

        if ($request->filter_type === 'text' && !empty($request->filter_value)) {
            $query->where('br.nama', 'like', '%' . $request->filter_value . '%');
        }

        $kantorPusat = (clone $query)
            ->where('s.is_kantor_pusat', 1)
            ->count();

        $kantorCabang = (clone $query)
            ->where('s.is_kantor_pusat', 0)
            ->count();

        return response()->json([
            'kantor_pusat'  => $kantorPusat,
            'kantor_cabang' => $kantorCabang,
        ]);
    }

    public function detail($id)
    {
        $data = DB::table('business_relation_sites as s')
            ->join('business_relations as br', 'br.id_br', '=', 's.id_br')
            ->where('s.id_site', $id)
            ->select([
                'br.id_br',
                'br.nama as nama_br',
                'br.entitas',
                'br.kepemilikan',
                'br.npwp',
                'br.npwp_alamat',
                'br.kategori_bisnis',
                'br.sub_kategori_bisnis',
                'br.website',
                'br.nomor_telepon',
                'br.is_aktif as br_is_aktif',
                'br.created_at as br_created_at',
                'br.updated_at as br_updated_at',
                's.id_site',
                's.nama_lokasi',
                's.is_kantor_pusat',
                's.alamat_lengkap',
                's.provinsi',
                's.kota_kabupaten',
                's.kecamatan',
                's.kelurahan',
                's.kode_pos',
                's.kawasan_bisnis',
                's.gedung',
                's.alamat',
                's.npwp_cabang',
                's.is_aktif as s_is_aktif',
                's.created_at as s_created_at',
                's.updated_at as s_updated_at',
            ])
            ->first();

        return response()->json($data);
    }

    public function setEditContext(Request $request)
    {
        session()->flash('editing_br_id', $request->id_br);
        session()->flash('editing_site_id', $request->id_site);
        session()->flash('mode', 'Edit');

        return response()->json([
            'redirect' => route('business-relations.edit')
        ]);
    }

    public function getDataBR(Request $request)
    {
        $id_br = $request->query('id_br');


        $data = DB::table('business_relations');

        if (!empty($id_br)) {
            $data->where('id_br', $id_br);
        }

        $br = $data->get();

        if ($br->isEmpty()) {
            return response()->json(['message' => 'Business Relation tidak ditemukan'], 404);
        }

        return response()->json(
            $br->map(function ($item) {
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
