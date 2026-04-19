<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;

class BusinessRelationContactController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string
    {
        return 'business_relation_contacts';
    }

    protected function auditExcludeFields(): array
    {
        return ['updated_at', 'created_at', 'id_contact'];
    }
    public function index()
    {
        return view('business-relation-contact.index', [
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

        $data = DB::table('business_relation_contacts')
            ->get();

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

    public function create()
    {
        return view('business-relation-contact.create', [
            'title' => 'Create Testing Unit'
        ]);
    }

    public function detail($id)
    {
        $testingUnit = DB::table('business_relation_contacts')
            ->leftJoin('business_relation_sites', 'business_relation_contacts.id_br', '=', 'business_relation_sites.id_site')
            ->where('id_contact', $id)->first();

        if (!$testingUnit) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($testingUnit);
    }

    public function show($id)
    {
        $testingUnit = DB::table('business_relation_contacts')
            ->leftJoin('business_relation_sites', 'business_relation_contacts.id_br', '=', 'business_relation_sites.id_site')
            ->where('id_contact', $id)->first();
        if (!$testingUnit) {
            return response()->json(['message' => 'Testing unit tidak ditemukan'], 404);
        }

        return response()->json($testingUnit);
    }


    public function getDataContactSite(Request $request, $id)
    {
        $search = trim($request->q);

        $query = DB::table('business_relation_contacts')
            ->select([
                'id_contact',
                'id_br',
                'nama_pic',
                'nomor_telepon_pic',
                'email_pic',
                'lokasi_pic',
                'is_aktif',
            ])
            ->where('id_br', $id);

        // search hanya jika ada keyword
        if (!empty($search)) {
            $query->where('nama_pic', 'like', "%{$search}%");
        }

        $contacts = $query
            ->orderBy('nama_pic')
            ->get();

        return response()->json(
            $contacts->map(function ($contact) {
                return [
                    'id'          => $contact->id_contact,   // WAJIB utk Select2
                    'text'        => $contact->nama_pic,
                    // auto-fill fields
                    'nama_pic'    => $contact->nama_pic,
                    'email'       => $contact->email_pic,
                    'no_hp'       => $contact->nomor_telepon_pic,
                    'lokasi'      => $contact->lokasi_pic,
                ];
            })
        );
    }

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('business_relation_contacts')
            ->where('nama_pic', 'like', "%{$search}%")
            ->orWhere('nomor_telepon_pic', 'like', "%{$search}%")
            ->limit(10)
            ->get();

        return response()->json(
            $data->map(function ($item) {
                return [
                    'id' => $item->id_contact,
                    'text' => $item->nama_pic . ' - ' . $item->nomor_telepon_pic,
                ];
            })
        );
    }

    // public function select2(Request $request)
    // {
    //     $search = $request->q;

    //     $data = DB::table('business_relation_contacts')
    //         ->where('is_aktif', 1)
    //         ->when($search, function ($query) use ($search) {
    //             $query->where('nama_pic', 'like', "%{$search}%");
    //         })          // ← kalau $search kosong, ambil semua
    //         ->limit(200) // ← limit secukupnya
    //         ->get();

    //     return response()->json(
    //         $data->map(function ($item) {
    //             return [
    //                 'id'   => $item->id_contact,
    //                 'text' => $item->nama_pic,
    //             ];
    //         })
    //     );
    // }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_br' => 'required|integer',
            'nama_pic' => 'required|string|max:50',
            'nomor_telepon_pic' => 'required|string|max:255',
        ]);

        $id = DB::table('business_relation_contacts')->insertGetId([
            'id_br' => $request['id_br'],
            'nama_pic' => $request['nama_pic'],
            'nomor_telepon_pic' => $request['nomor_telepon_pic'],
            'email_pic' => $request['email_pic'],
            'lokasi_pic' => $request['lokasi_pic'],
            'is_aktif' => $request['is_aktif'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $after = DB::table('business_relation_contacts')->where('id_contact', $id)->get()->toJson();
        saveAudit('business_relation_contacts', $id, 'Create', '', $after);

        return response()->json([
            'status' => 'success',
            'id' => $id
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'id_br'             => 'required|integer',
            'nama_pic'          => 'required|string|max:255',
            'nomor_telepon_pic' => 'required|string|max:20',
            'email_pic'         => 'nullable|email|max:100',
            'lokasi_pic'        => 'nullable|string|max:255',
            'is_aktif'          => 'required|boolean',
        ]);

        $before = DB::table('business_relation_contacts')
            ->where('id_contact', $id)
            ->get()->toJson();

        DB::table('business_relation_contacts')
            ->where('id_contact', $id)
            ->update([
                ...$validated,
                'updated_at' => now()
            ]);

        $after = DB::table('business_relation_contacts')
            ->where('id_contact', $id)
            ->get()->toJson();

        saveAudit(
            'business_relation_contacts',
            $id,
            'update',
            $before,
            $after
        );

        return response()->json([
            'success' => true,
            'message' => 'Contact berhasil diperbarui'
        ]);
    }
}
