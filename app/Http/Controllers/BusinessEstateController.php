<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Auth;


class BusinessEstateController extends Controller
{
    //
    public function index()
    {
        return view('master.index', [
            'title' => 'Business Estate',
            'routePrefix' => 'business-estates',
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

        $data = DB::table('business_estates')->get();

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
        $building = DB::table('business_estates')
            ->where('id_bestate', $id)
            ->first();

        if (!$building) {
            return response()->json(['message' => 'Building not found'], 404);
        }

        return response()->json($building);
    }

    public function create()
    {
        return view('business-estates.create');
    }

    public function store(Request $request)
    {
        // =========================
        // VALIDASI
        // =========================
        $validated = $request->validate([
            'nama'           => 'required|string|max:255',
            'kode'           => 'nullable|string|max:50',
            'alamat'         => 'required|string',
            'provinsi'       => 'nullable|string|max:100',
            'kota_kabupaten' => 'nullable|string|max:100',
            'website'        => 'nullable|string|max:255',
            'pemilik'        => 'nullable|string|max:255',
            'pengurus'       => 'nullable|string|max:255',
            'is_aktif'       => 'required|in:0,1',
        ]);

        // =========================
        // INSERT DATA
        // =========================
        DB::table('business_estates')->insert([
            'nama'           => $validated['nama'],
            'kode'           => $validated['kode'] ?? null,
            'alamat'         => $validated['alamat'],
            'provinsi'       => $validated['provinsi'] ?? null,
            'kota_kabupaten' => $validated['kota_kabupaten'] ?? null,
            'website'        => $validated['website'] ?? null,
            'pemilik'        => $validated['pemilik'] ?? null,
            'pengurus'       => $validated['pengurus'] ?? null,
            'is_aktif'       => $validated['is_aktif'],
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Business Estate berhasil disimpan',
        ]);
    }


    public function setEditContext(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:business_estates,id_bestate'
        ]);

        session([
            'editing_bestate_id' => $request->id,
            'mode' => 'Edit'
        ]);

        return response()->json([
            'redirect' => route('business-estates.edit')
        ]);
    }


    public function edit()
    {
        $bestateId = session('editing_bestate_id');

        if (!$bestateId) {
            return redirect()
                ->route('business-estates.index')
                ->with('error', 'Konteks edit tidak ditemukan');
        }

        $bestate = DB::table('business_estates')
            ->where('id_bestate', $bestateId)
            ->first();

        if (!$bestate) {
            session()->forget(['editing_bestate_id', 'mode']);

            return redirect()
                ->route('business-estates.index')
                ->with('error', 'Data Business Estate tidak ditemukan');
        }

        return view('business-estates.edit', [
            'bestate' => $bestate
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'nama'           => 'required|string|max:255',
            'kode'           => 'nullable|string|max:50',
            'alamat'         => 'nullable|string',
            'provinsi'       => 'nullable|string|max:100',
            'kota_kabupaten' => 'nullable|string|max:100',
            'website'        => 'nullable|string|max:255',
            'pemilik'        => 'nullable|string|max:255',
            'pengurus'       => 'nullable|string|max:255',
            'is_aktif'       => 'required|in:0,1',
        ]);

        $before = DB::table('business_estates')
            ->where('id_bestate', $request->input('id_bestate'))
            ->get()->toJson();

        // DB::table('audit_logs')
        // ->insert();

        DB::table('business_estates')
            ->where('id_bestate', $request->input('id_bestate'))
            ->update([
                ...$validated,
                'updated_at' => now()
            ]);

        $after = DB::table('business_estates')
            ->where('id_bestate', $request->input('id_bestate'))
            ->get()->toJson();

        DB::table('audit_logs')
            ->insert(
                [
                    'nama_table' => 'business_estates',
                    'row_id' => $request->input('id_bestate'),
                    'action' => 'update',
                    'old_value' => $before,
                    'new_value' => $after,
                    'created_by' => Auth::user()->id,
                    'created_at' => now()
                ]
            );


        session()->forget(['editing_bestate_id', 'mode']);

        return response()->json([
            'success' => true,
            'message' => 'Business Estate berhasil diperbarui'
        ]);
    }
}
