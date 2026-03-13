<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BoqController extends Controller
{
    public function create()
    {
        return view('boq.create', [
            'title' => 'Create BOQ',
        ]);
    }


    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('sales_orders')
            ->where('no_so', 'like', "%{$search}%")
            ->limit(10)
            ->get();

        return response()->json(
            $data->map(function ($item) {
                return [
                    'id'    => $item->id_so,         // HARUS 'id'
                    'text'  => $item->no_so . " - " . $item->judul_order,         // HARUS 'text' agar muncul di dropdown
                    'judul' => $item->judul_order,   // Data tambahan (opsional)
                ];
            })
        );
    }
}
