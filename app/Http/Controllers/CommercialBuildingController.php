<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class CommercialBuildingController extends Controller
{
    //
    public function index()
    {
        return view('master.index', [
            'title' => 'Commercial Building',
            'routePrefix' => 'commercial-buildings',
            'columns' => [
                'nama_gedung' => 'Nama Gedung',
                'alamat'      => 'Alamat',
                'kota'        => 'Kota',
                'is_aktif'    => 'Status',
                'created_at'  => 'Created',
            ]
        ]);
    }

    public function data()
    {

        $data = DB::table('commercial_buildings')->get();

        $header = [];
        foreach ($data as $k => $v) {
            if ($k == 0) $header = array_keys((array)$v);
            break;
        }

        $dt = DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                return '<button>Edit</button>';
            })
            ->rawColumns(['action'])
            ->make(true)
            ->getData();

        return response()->json([
            'data' => $dt->data,
            'header' => $header,
        ]);
    }

    public function detail($id)
    {
        $building = DB::table('commercial_buildings')
            ->where('id_building', $id)
            ->first();

        if (!$building) {
            return response()->json(['message' => 'Building not found'], 404);
        }

        return response()->json($building);
    }


    public function create()
    {
        return view('business-building.create');
    }

    public function store(Request $request)
    {
        // =========================
        // VALIDASI
        // =========================
        $validated = $request->validate([
            'nama'            => 'required|string|max:255',
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
        DB::table('commercial_buildings')->insert([
            'nama'           => $validated['nama'],
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

        return view('business-building.edit', [
            'building' => $building
        ]);
    }

    public function update(Request $request, $id)
    {
        // =========================
        // VALIDASI
        // =========================
        $validated = $request->validate([
            'nama'            => 'required|string|max:255',
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
            DB::table('commercial_buildings')
                ->where('id_building', $id)
                ->update([
                    'nama'           => $validated['nama'],
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
}
