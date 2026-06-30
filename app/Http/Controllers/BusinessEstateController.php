<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasAuditHistory;


class BusinessEstateController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string
    {
        return 'business_estates';
    }

    protected function auditExcludeFields(): array
    {
        return ['updated_at', 'created_at', 'id_bestate'];
    }
    public function index()
    {
        return view('business-estates.index', [
            'title' => 'Business Estate'
        ]);

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

        $data = DB::table('business_estates')->whereNull('deleted_at')->get();

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

    public function detail($id)
    {
        $building = DB::table('business_estates')
            ->whereNull('deleted_at')
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
            'kode'           => 'required|string|max:50|unique:business_estates,kode',
            'alamat'         => 'nullable|string',
            'provinsi'       => 'nullable|string|max:100',
            'kota_kabupaten' => 'nullable|string|max:100',
            'website'        => 'nullable|string|max:255',
            'pemilik'        => 'nullable|string|max:255',
            'pengurus'       => 'nullable|string|max:255',
            'is_aktif'       => 'required|in:0,1',
        ], [
            'kode.required' => 'Kode wajib diisi.',
            'kode.unique'   => 'Kode sudah digunakan, gunakan kode lain.',
        ]);

        // =========================
        // INSERT DATA
        // =========================
        $id = DB::table('business_estates')->insertGetId([
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

        $after = DB::table('business_estates')->where('id_bestate', $id)->get()->toJson();

        saveAudit('business_estates', $id, 'Create', '', $after);

        return response()->json([
            'success' => true,
            'message' => 'Business Estate berhasil disimpan',
            'id'      => $id,
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

    public function destroy($id)
    {
        $before = DB::table('business_estates')->where('id_bestate', $id)->get()->toJson();
        DB::table('business_estates')->where('id_bestate', $id)->update(['deleted_at' => now()]);
        $after = DB::table('business_estates')->where('id_bestate', $id)->get()->toJson();
        saveAudit('business_estates', $id, 'delete', $before, $after);
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function update(Request $request, $id)
    {


        $validated = $request->validate([
            'nama'           => 'required|string|max:255',
            'kode'           => ['required', 'string', 'max:50', Rule::unique('business_estates', 'kode')->ignore((int)$id, 'id_bestate')],
            'alamat'         => 'nullable|string',
            'provinsi'       => 'nullable|string|max:100',
            'kota_kabupaten' => 'nullable|string|max:100',
            'website'        => 'nullable|string|max:255',
            'pemilik'        => 'nullable|string|max:255',
            'pengurus'       => 'nullable|string|max:255',
        ], [
            'kode.required' => 'Kode wajib diisi.',
            'kode.unique'   => 'Kode sudah digunakan, gunakan kode lain.',
        ]);

        $validated['is_aktif'] = $request->is_aktif;



        $before = DB::table('business_estates')
            ->where('id_bestate', $id)
            ->get()->toJson();

        DB::table('business_estates')
            ->where('id_bestate', $id)
            ->update([
                ...$validated,
                'updated_at' => now()
            ]);

        $after = DB::table('business_estates')
            ->where('id_bestate', $id)
            ->get()->toJson();

        saveAudit(
            'business_estates',
            $id,
            'update',
            $before,
            $after
        );

        session()->forget(['editing_bestate_id', 'mode']);

        return response()->json([
            'success' => true,
            'message' => 'Business Estate berhasil diperbarui'
        ]);
    }


    public function show(Request $request)
    {

        $id = $request->id;

        $data = DB::table('business_estates')->where('id_bestate', $id)->first();
        if (!$data) {
            return response()->json(['message' => 'Testing parameter tidak ditemukan'], 404);
        }

        return response()->json($data);
    }

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('business_estates')
            ->where('kode', 'like', "%{$search}%")
            ->orWhere('nama', 'like', "%{$search}%")
            ->limit(10)
            ->get();

        return response()->json(
            $data->map(function ($item) {
                return [
                    'id' => $item->id_bestate,
                    'text' => $item->kode . ' - ' . $item->nama,
                ];
            })
        );
    }
}
