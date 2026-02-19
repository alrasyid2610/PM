<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SebastianBergmann\Environment\Console;

class SalesOrderController extends Controller
{
    //
    public function create()
    {
        return view('sales-order.create', [
            'title' => 'Create Sales Order',
        ]);
    }


    public function store(Request $request)
    {
        // ==========================
        // VALIDATION
        // ==========================
        $request->validate([
            'tanggal_so' => 'required|date',
            'id_pelanggan' => 'required|integer',
            'status' => 'required|string'
        ]);

        $soNumber = $this->generateSoNumber();

        // ==========================
        // INSERT
        // ==========================
        $id = DB::table('sales_orders')->insertGetId([
            'no_so' => $soNumber,
            'tanggal_so' => $request->tanggal_so,
            'judul_order' => $request->judul_order,
            'tidak_ada_po' => $request->tidak_ada_po ?? 0,
            'no_po' => $request->no_po,
            'tanggal_po' => $request->tanggal_po,

            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,

            'id_office' => $request->id_office,

            // CUSTOMER
            'id_pelanggan' => $request->id_pelanggan,
            'id_site_pelanggan' => $request->id_site_pelanggan,
            'id_pic_pelanggan' => $request->id_pic_pelanggan,

            // DELIVERY
            'id_pelanggan_delivery' => $request->id_pelanggan_delivery,
            'id_site_pelanggan_delivery' => $request->id_site_pelanggan_delivery,
            'id_pic_pelanggan_delivery' => $request->id_pic_pelanggan_delivery,

            // PAYMENT
            'id_pelanggan_payment' => $request->id_pelanggan_payment,
            'id_site_pelanggan_payment' => $request->id_site_pelanggan_payment,
            'id_pic_pelanggan_payment' => $request->id_pic_pelanggan_payment,

            // PIC
            'pic_input' => $request->pic_input,
            'pic_order' => $request->pic_order,
            'pic_marketing_internal' => $request->pic_marketing_internal,
            'pic_marketing_eksternal' => $request->pic_marketing_eksternal,

            // STATUS
            'status' => $request->status,
            'keterangan_status' => $request->keterangan_status,
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


    private function generateSoNumber()
    {
        $year = now()->format('Y');
        $month = now()->format('m');

        $prefix = "SO-{$year}-{$month}-";

        $lastSo = DB::table('sales_orders')
            ->where('no_so', 'like', $prefix . '%')
            ->orderByDesc('id_so')
            ->first();

        if ($lastSo) {
            $lastNumber = (int) substr($lastSo->no_so, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $running = str_pad($newNumber, 4, '0', STR_PAD_LEFT);

        return $prefix . $running;
    }



    public function show($id)
    {
        $so = DB::table('sales_orders as so')
            ->leftJoin('business_relations as pelanggan', 'so.id_pelanggan', '=', 'pelanggan.id_br')
            ->leftJoin('business_relation_sites as site_pelanggan', 'so.id_site_pelanggan', '=', 'site_pelanggan.id_site')
            ->select('so.*', 'pelanggan.nama as nama_pelanggan', 'site_pelanggan.nama_lokasi as nama_site_pelanggan')
            ->where('so.id_so', $id)
            ->first();


        if (!$so) {
            return response()->json([
                'message' => 'Sales Order tidak ditemukan'
            ], 404);
        }

        return response()->json($so);
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
