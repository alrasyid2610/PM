<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkOrderController extends Controller
{

    public function store(Request $request)
    {

        // ==========================
        // VALIDATION
        // ==========================
        $request->validate([
            'id_sales_order' => 'required|integer',
            'tanggal_so' => 'required|date',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date',
            'judul_order' => 'required|string',
            'id_pelanggan' => 'required|string',
            'id_site_pelanggan' => 'required|string',
        ]);

        // ==========================
        // INSERT
        // ==========================
        $id = DB::table('work_orders')->insertGetId([
            'id_so' => $request->id_sales_order,
            'id_pelanggan_pekerjaan' => $request->id_pelanggan,
            'id_site_pelanggan_pekerjaan' => $request->id_site_pelanggan,
            'id_pic_pelanggan_pekerjaan' => $request->pic_pekerjaan,
            'judul_pekerjaan' => $request->judul_order,
            'keterangan' => $request->keterangan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Sales Order berhasil dibuat',
            'id_so' => $id
        ]);
    }

    public function create()
    {
        return view('work-order.create', [
            'title' => 'Create Work Order',
        ]);
    }
}
