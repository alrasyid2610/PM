<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessRelationContactController extends Controller
{
    //

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
}
