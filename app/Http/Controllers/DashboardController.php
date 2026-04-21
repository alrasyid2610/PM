<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.index');
    }

    public function summary()
    {
        $kantorPusat = DB::table('business_relation_sites')
            ->where('is_kantor_pusat', 1)
            ->where('is_aktif', 1)
            ->count();

        $kantorCabang = DB::table('business_relation_sites')
            ->where('is_kantor_pusat', 0)
            ->where('is_aktif', 1)
            ->count();

        $totalSo = DB::table('sales_orders')->count();

        $soByStatus = DB::table('sales_orders')
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn($item) => [
                strtolower($item->status) => $item->total
            ]);

        $totalWo = DB::table('work_orders')->count();

        return response()->json([
            'kantor_pusat'  => $kantorPusat,
            'kantor_cabang' => $kantorCabang,
            'total_so'      => $totalSo,
            'total_wo'      => $totalWo,
            'so_by_status'  => $soByStatus,
        ]);
    }

    public function soPerMonth(Request $request)
    {
        $year = $request->year ?? now()->year;

        $data = DB::table('sales_orders')
            ->select(
                DB::raw('MONTH(tanggal_so) as bulan'),
                DB::raw('COUNT(*) as total')
            )
            ->whereYear('tanggal_so', $year)
            ->whereNotNull('tanggal_so')
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get()
            ->mapWithKeys(fn($item) => [
                (int) $item->bulan => $item->total
            ]);

        $result = [];
        for ($i = 1; $i <= 12; $i++) {
            $result[] = $data[$i] ?? 0;
        }

        return response()->json([
            'year' => (int) $year,
            'data' => $result,
        ]);
    }
}
