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

        $data = DB::table('business_relation_contacts as brc')
            ->leftJoin('business_relation_sites as s', 's.id_site', '=', 'brc.id_site')
            ->whereNull('brc.deleted_at')
            ->select([
                'brc.id_contact',
                'brc.nama_pic',
                'brc.nomor_telepon_pic',
                'brc.email_pic',
                's.nama_lokasi as site',
                'brc.lokasi_pic',
                'brc.is_aktif',
                'brc.created_at',
                'brc.updated_at',
            ])
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
            ->where('business_relation_contacts.id_contact', $id)
            ->whereNull('business_relation_contacts.deleted_at')
            ->first();

        if (!$testingUnit) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($testingUnit);
    }

    public function show($id)
    {
        $testingUnit = DB::table('business_relation_contacts as brc')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'brc.id_br')
            ->select(['brc.*', 'br.nama as nama_br'])
            ->where('brc.id_contact', $id)
            ->whereNull('brc.deleted_at')
            ->first();
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

    public function listBySite($id_site)
    {
        $site = DB::table('business_relation_sites')->where('id_site', $id_site)->first();
        if (!$site) {
            return response()->json(['message' => 'Site tidak ditemukan'], 404);
        }

        $rows = DB::table('business_relation_contacts')
            ->where('id_br', $site->id_br)
            ->where(function ($q) use ($id_site) {
                $q->where('id_site', $id_site)->orWhereNull('id_site');
            })
            ->whereNull('deleted_at')
            ->orderByRaw('id_site IS NULL') // kontak khusus site ini dulu, baru kontak umum
            ->orderBy('nama_pic')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function select2(Request $request)
    {
        $search   = $request->q;
        $idBrList = array_filter((array) $request->input('id_br', []), fn($v) => $v !== null && $v !== '');
        $withSite = $request->boolean('with_site');

        $query = DB::table('business_relation_contacts as brc')
            ->whereNull('brc.deleted_at')
            ->when(!empty($idBrList), fn($q) => $q->whereIn('brc.id_br', $idBrList))
            ->where(function ($q) use ($search) {
                $q->where('brc.nama_pic', 'like', "%{$search}%")
                  ->orWhere('brc.nomor_telepon_pic', 'like', "%{$search}%");
            });

        if ($withSite) {
            $query->leftJoin('business_relation_sites as s', 's.id_site', '=', 'brc.id_site')
                ->select(['brc.id_contact', 'brc.nama_pic', 'brc.nomor_telepon_pic', 's.nama_lokasi']);
        } else {
            $query->select(['brc.id_contact', 'brc.nama_pic', 'brc.nomor_telepon_pic']);
        }

        $data = $query->limit(50)->get();

        return response()->json(
            $data->map(function ($item) use ($withSite) {
                $text = $item->nama_pic . ' - ' . $item->nomor_telepon_pic;
                if ($withSite) {
                    $text .= ' - ' . ($item->nama_lokasi ? self::siteInitials($item->nama_lokasi) : '-');
                }
                return ['id' => $item->id_contact, 'text' => $text];
            })
        );
    }

    private static function siteInitials(string $name): string
    {
        $words   = preg_split('/\s+/', trim($name));
        $letters = array_filter(array_map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)), $words), fn($l) => $l !== '');
        return implode('', array_slice($letters, 0, 3));
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
            'id_br'             => 'required|integer',
            'id_site'           => 'nullable|integer',
            'nama_pic'          => 'required|string|max:50',
            'nomor_telepon_pic' => 'required|string|max:20|regex:/^[0-9]+$/',
            'email_pic'         => 'nullable|email|max:100',
        ], [
            'nomor_telepon_pic.regex' => 'No. Telp PIC hanya boleh berisi angka.',
            'email_pic.email'         => 'Format email tidak valid.',
        ]);

        $id = DB::table('business_relation_contacts')->insertGetId([
            'id_br' => $request['id_br'],
            'id_site' => $request['id_site'] ?: null,
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
            'success' => true,
            'id' => $id
        ]);
    }

    public function destroy($id)
    {
        $before = DB::table('business_relation_contacts')->where('id_contact', $id)->get()->toJson();
        DB::table('business_relation_contacts')->where('id_contact', $id)->update(['deleted_at' => now()]);
        $after = DB::table('business_relation_contacts')->where('id_contact', $id)->get()->toJson();
        saveAudit('business_relation_contacts', $id, 'delete', $before, $after);
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'id_br'             => 'required|integer',
            'id_site'           => 'nullable|integer',
            'nama_pic'          => 'required|string|max:255',
            'nomor_telepon_pic' => 'required|string|max:20|regex:/^[0-9]+$/',
            'email_pic'         => 'nullable|email|max:100',
            'lokasi_pic'        => 'nullable|string|max:255',
            'is_aktif'          => 'required|boolean',
        ], [
            'nomor_telepon_pic.regex' => 'No. Telp PIC hanya boleh berisi angka.',
            'email_pic.email'         => 'Format email tidak valid.',
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
