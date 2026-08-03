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
        $today  = now()->toDateString();
        $date7  = now()->addDays(7)->toDateString();

        $soOutstanding = DB::table('sales_orders')
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['completed', 'cancel'])
            ->count();

        $woOutstanding = DB::table('work_orders')
            ->whereNull('deleted_at')
            ->where('status', 'onprogress')
            ->count();

        $terminOutstanding = DB::table('termin')
            ->where('status', '!=', 'selesai')
            ->count();

        $fwoOutstanding = DB::table('fieldworks')
            ->whereNull('deleted_at')
            ->where('status', 'planned')
            ->count();

        $overdueWo = DB::table('work_orders')
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['completed'])
            ->whereNotNull('tanggal_selesai')
            ->whereDate('tanggal_selesai', '<', $today)
            ->count();

        $overdueFwo = DB::table('fieldworks')
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['selesai'])
            ->whereNotNull('tanggal_selesai')
            ->whereDate('tanggal_selesai', '<', $today)
            ->count();

        $due7Wo = DB::table('work_orders')
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['completed'])
            ->whereNotNull('tanggal_selesai')
            ->whereDate('tanggal_selesai', '>=', $today)
            ->whereDate('tanggal_selesai', '<=', $date7)
            ->count();

        $due7Fwo = DB::table('fieldworks')
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['selesai'])
            ->whereNotNull('tanggal_selesai')
            ->whereDate('tanggal_selesai', '>=', $today)
            ->whereDate('tanggal_selesai', '<=', $date7)
            ->count();

        return response()->json([
            'termin_outstanding' => $terminOutstanding,
            'so_outstanding'  => $soOutstanding,
            'wo_outstanding'  => $woOutstanding,
            'fwo_outstanding' => $fwoOutstanding,
            'overdue'         => $overdueWo + $overdueFwo,
            'due_7'           => $due7Wo + $due7Fwo,
        ]);
    }

    public function list(Request $request)
    {
        $type = $request->input('type', 'wo');

        if ($type === 'wo') {
            $rows = DB::table('work_orders as wo')
                ->leftJoin('sales_orders as s', 's.id_so', '=', 'wo.id_so')
                ->leftJoin('business_relations as br', 'br.id_br', '=', 'wo.id_pelanggan_pekerjaan')
                ->leftJoin('business_relation_contacts as brc', 'brc.id_contact', '=', 'wo.id_pic_pelanggan_pekerjaan')
                ->whereNull('wo.deleted_at')
                ->where('wo.status', 'onprogress')
                ->select([
                    'wo.id_wo as id_rec',
                    'wo.no_wo as no',
                    'wo.judul_pekerjaan as judul',
                    'br.nama as pelanggan',
                    'brc.nama_pic as pic',
                    'wo.tanggal_selesai as deadline',
                    'wo.status',
                ])
                ->orderByDesc('wo.created_at')
                ->get();

        } elseif ($type === 'fwo') {
            $rows = DB::table('fieldworks as fw')
                ->leftJoin('work_orders as wo', 'wo.id_wo', '=', 'fw.id_wo')
                ->leftJoin('business_relations as br', 'br.id_br', '=', 'wo.id_pelanggan_pekerjaan')
                ->leftJoin('business_relation_contacts as brc', 'brc.id_contact', '=', 'fw.id_pic_pelanggan_pekerjaan')
                ->whereNull('fw.deleted_at')
                ->where('fw.status', 'planned')
                ->select([
                    'fw.id_fwo as id_rec',
                    'fw.no_fwo as no',
                    'fw.judul_pekerjaan as judul',
                    'br.nama as pelanggan',
                    'brc.nama_pic as pic',
                    'fw.tanggal_selesai as deadline',
                    'fw.status',
                ])
                ->orderByDesc('fw.created_at')
                ->get();

        } elseif ($type === 'so') {
            $rows = DB::table('sales_orders as s')
                ->leftJoin('business_relations as br', 'br.id_br', '=', 's.id_pelanggan')
                ->leftJoin('business_relation_contacts as brc', 'brc.id_contact', '=', 's.id_pic_pelanggan')
                ->whereNull('s.deleted_at')
                ->whereNotIn('s.status', ['selesai', 'cancel'])
                ->select([
                    's.id_so as id_rec',
                    's.no_so as no',
                    's.judul_order as judul',
                    'br.nama as pelanggan',
                    'brc.nama_pic as pic',
                    DB::raw('NULL as deadline'),
                    's.status',
                ])
                ->orderByDesc('s.created_at')
                ->get();

        } else {
            $rows = DB::table('termin as t')
                ->leftJoin('sales_orders as s', 's.id_so', '=', 't.id_so')
                ->leftJoin('business_relations as br', 'br.id_br', '=', 's.id_pelanggan')
                ->where('t.status', '!=', 'selesai')
                ->select([
                    't.id_termin as id_rec',
                    't.no_termin as no',
                    't.nama as judul',
                    'br.nama as pelanggan',
                    DB::raw('NULL as pic'),
                    't.tanggal as deadline',
                    't.status',
                ])
                ->orderByDesc('t.created_at')
                ->get();
        }

        return response()->json(['data' => $rows]);
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
