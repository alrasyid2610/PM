<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;

class CommercialBuildingController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string
    {
        return 'commercial_buildings';
    }

    protected function auditExcludeFields(): array
    {
        return ['updated_at', 'created_at', 'id_building'];
    }
    public function index()
    {
        // return view('master.index', [
        //     'title' => 'Commercial Building',
        //     'routePrefix' => 'commercial-buildings',
        //     'columns' => [
        //         'nama_gedung' => 'Nama Gedung',
        //         'alamat'      => 'Alamat',
        //         'kota'        => 'Kota',
        //         'is_aktif'    => 'Status',
        //         'created_at'  => 'Created',
        //     ]
        // ]);

        return view('commercial-building.index', [
            'titile' => 'Comercial Building'
        ]);
    }

    public function data()
    {

        $data = DB::table('commercial_buildings')->whereNull('deleted_at')->get();

        $header = [];
        foreach ($data as $k => $v) {
            if ($k == 0) $header = array_keys((array)$v);
            break;
        }

        $dt = DataTables::of($data)
            ->addIndexColumn()
            ->make(true)
            ->getData();

        return response()->json([
            'data' => $dt->data,
            'header' => $header,
        ]);
    }


    public function show($id)
    {
        $building = DB::table('commercial_buildings')
            ->where('id_building', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$building) {
            return response()->json(['message' => 'Building not found'], 404);
        }

        return response()->json($building);
    }


    public function create()
    {
        return view('commercial-building.create');
    }

    public function store(Request $request)
    {
        // =========================
        // VALIDASI
        // =========================
        $validated = $request->validate([
            'nama'            => 'required|string|max:255',
            'kode'            => 'nullable|string|max:50',
            'alamat'          => 'required|string',
            'provinsi'        => 'nullable|string|max:100',
            'kota_kabupaten'  => 'nullable|string|max:100',
            'kode_pos'        => 'nullable|string|max:20',
            'website'         => 'nullable|string|max:255',
            'pemilik'         => 'nullable|string|max:255',
            'pengurus'        => 'nullable|string|max:255',
            'is_aktif'        => 'required|in:0,1',
        ]);

        // =========================
        // INSERT DATA
        // =========================
        $id = DB::table('commercial_buildings')->insertGetId([
            'nama'           => $validated['nama'],
            'kode'           => $validated['kode'] ?? null,
            'alamat'         => $validated['alamat'],
            'provinsi'       => $validated['provinsi'] ?? null,
            'kota_kabupaten' => $validated['kota_kabupaten'] ?? null,
            'kode_pos'       => $validated['kode_pos'] ?? null,
            'website'        => $validated['website'] ?? null,
            'pemilik'        => $validated['pemilik'] ?? null,
            'pengurus'       => $validated['pengurus'] ?? null,
            'is_aktif'       => $validated['is_aktif'],
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $after = DB::table('commercial_buildings')->where('id_building', $id)->get()->toJson();
        saveAudit('commercial_buildings', $id, 'Create', '', $after);

        return response()->json([
            'success' => true,
            'message' => 'Commercial Building berhasil disimpan',
        ]);
    }


    public function setEditContext(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:commercial_buildings,id_building'
        ]);

        session([
            'editing_commercial_building_id' => $request->id,
            'mode' => 'Edit'
        ]);

        return response()->json([
            'redirect' => route('commercial-buildings.edit')
        ]);
    }

    public function edit()
    {
        $buildingId = session('editing_commercial_building_id');

        if (!$buildingId) {
            return redirect()
                ->route('commercial-buildings.index')
                ->with('error', 'Konteks edit tidak ditemukan');
        }

        $building = DB::table('commercial_buildings')
            ->where('id_building', $buildingId)
            ->first();

        if (!$building) {
            session()->forget(['editing_commercial_building_id', 'mode']);

            return redirect()
                ->route('commercial-buildings.index')
                ->with('error', 'Data Commercial Building tidak ditemukan');
        }

        return view('commercial-building.edit', [
            'building' => $building
        ]);
    }

    public function destroy($id)
    {
        $before = DB::table('commercial_buildings')->where('id_building', $id)->get()->toJson();
        DB::table('commercial_buildings')->where('id_building', $id)->update(['deleted_at' => now()]);
        $after = DB::table('commercial_buildings')->where('id_building', $id)->get()->toJson();
        saveAudit('commercial_buildings', $id, 'delete', $before, $after);
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function update(Request $request, $id)
    {
        // =========================
        // VALIDASI
        // =========================
        $validated = $request->validate([
            'nama'            => 'required|string|max:255',
            'kode'            => 'nullable|string|max:50',
            'alamat'          => 'nullable|string',
            'provinsi'        => 'nullable|string|max:150',
            'kota_kabupaten'  => 'nullable|string|max:150',
            'kode_pos'        => 'nullable|string|max:20',
            'website'         => 'nullable|string|max:255',
            'pemilik'         => 'nullable|string|max:255',
            'pengurus'        => 'nullable|string|max:255',
            'is_aktif'        => 'required|in:0,1',
        ]);

        DB::beginTransaction();

        try {
            // =========================
            // CEK DATA EXIST
            // =========================
            $exists = DB::table('commercial_buildings')
                ->where('id_building', $id)
                ->exists();

            if (!$exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data commercial building tidak ditemukan'
                ], 404);
            }

            // =========================
            // UPDATE DATA
            // =========================
            $before = DB::table('commercial_buildings')
                ->where('id_building', $id)
                ->get()
                ->toJson();

            DB::table('commercial_buildings')
                ->where('id_building', $id)
                ->update([
                    'nama'           => $validated['nama'],
                    'kode'           => $validated['kode'] ?? null,
                    'alamat'         => $validated['alamat'] ?? null,
                    'provinsi'       => $validated['provinsi'] ?? null,
                    'kota_kabupaten' => $validated['kota_kabupaten'] ?? null,
                    'kode_pos'       => $validated['kode_pos'] ?? null,
                    'website'        => $validated['website'] ?? null,
                    'pemilik'        => $validated['pemilik'] ?? null,
                    'pengurus'       => $validated['pengurus'] ?? null,
                    'is_aktif'       => $validated['is_aktif'],
                    'updated_at'     => now(),
                ]);

            $after = DB::table('commercial_buildings')
                ->where('id_building', $id)
                ->get()
                ->toJson();

            saveAudit(
                'commercial_buildings',
                $id,
                'update',
                $before,
                $after
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Commercial building berhasil diperbarui'
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            // ⚠️ saat development boleh di-log
            logger()->error($e);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem'
            ], 500);
        }
    }

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('commercial_buildings')
            ->where('is_aktif', 1)
            ->when($search, function ($query) use ($search) {
                $query->where('nama', 'like', "%{$search}%");
            })
            ->limit(50)
            ->get();

        return response()->json(
            $data->map(function ($item) {
                return [
                    'id'   => $item->id_building,
                    'text' => $item->nama,
                ];
            })
        );
    }
}
