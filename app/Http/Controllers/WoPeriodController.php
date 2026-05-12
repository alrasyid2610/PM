<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WoPeriodController extends Controller
{
    public function bySo($id_so)
    {
        $periods = DB::table('wo_periods as p')
            ->leftJoin('business_relation_sites as brs', 'brs.id_site', '=', 'p.id_site')
            ->where('p.id_so', $id_so)
            ->select([
                'p.id_period',
                'p.id_so',
                'p.id_site',
                'brs.nama_lokasi as nama_site',
                'p.tanggal_mulai',
                'p.tanggal_selesai',
                'p.interval_bulan',
                'p.keterangan',
            ])
            ->orderBy('p.id_period')
            ->get();

        $periodIds = $periods->pluck('id_period');

        $wos = $periodIds->isNotEmpty()
            ? DB::table('work_orders')
                ->whereIn('id_period', $periodIds)
                ->select(['id_wo', 'no_wo', 'judul_pekerjaan', 'id_period'])
                ->get()
                ->groupBy('id_period')
            : collect();

        return response()->json($periods->map(function ($p) use ($wos) {
            return [
                'id_period'       => $p->id_period,
                'id_so'           => $p->id_so,
                'id_site'         => $p->id_site,
                'nama_site'       => $p->nama_site,
                'tanggal_mulai'   => $p->tanggal_mulai,
                'tanggal_selesai' => $p->tanggal_selesai,
                'interval_bulan'  => $p->interval_bulan,
                'keterangan'      => $p->keterangan,
                'wos'             => ($wos[$p->id_period] ?? collect())->values(),
            ];
        })->values());
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_so'           => 'required|integer',
            'id_site'         => 'required|integer',
            'tanggal_mulai'   => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
            'interval_bulan'  => 'nullable|integer|min:1',
        ]);

        $id = DB::table('wo_periods')->insertGetId([
            'id_so'           => $request->id_so,
            'id_site'         => $request->id_site,
            'tanggal_mulai'   => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'interval_bulan'  => $request->interval_bulan,
            'keterangan'      => $request->keterangan,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $period = DB::table('wo_periods as p')
            ->leftJoin('business_relation_sites as brs', 'brs.id_site', '=', 'p.id_site')
            ->where('p.id_period', $id)
            ->select(['p.*', 'brs.nama_lokasi as nama_site'])
            ->first();

        return response()->json([
            'status'  => 'success',
            'message' => 'Period berhasil dibuat',
            'data'    => $period,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'id_site'         => 'required|integer',
            'tanggal_mulai'   => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
            'interval_bulan'  => 'nullable|integer|min:1',
        ]);

        DB::table('wo_periods')->where('id_period', $id)->update([
            'id_site'         => $request->id_site,
            'tanggal_mulai'   => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'interval_bulan'  => $request->interval_bulan,
            'keterangan'      => $request->keterangan,
            'updated_at'      => now(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Period berhasil diperbarui']);
    }

    public function destroy($id)
    {
        DB::table('work_orders')->where('id_period', $id)->update(['id_period' => null]);
        DB::table('wo_periods')->where('id_period', $id)->delete();

        return response()->json(['status' => 'success', 'message' => 'Period berhasil dihapus']);
    }

    public function select2(Request $request)
    {
        $id_so = $request->id_so;
        $q     = $request->q;

        $query = DB::table('wo_periods as p')
            ->leftJoin('business_relation_sites as brs', 'brs.id_site', '=', 'p.id_site')
            ->where('p.id_so', $id_so);

        if ($q) {
            $query->where('brs.nama_lokasi', 'like', "%{$q}%");
        }

        $data = $query->select([
            'p.id_period', 'brs.nama_lokasi', 'p.tanggal_mulai', 'p.tanggal_selesai', 'p.interval_bulan',
        ])->get();

        return response()->json($data->map(function ($item) {
            $label = $item->nama_lokasi ?? 'Lokasi ?';
            if ($item->tanggal_mulai)   $label .= ' · ' . substr($item->tanggal_mulai, 0, 7);
            if ($item->interval_bulan)  $label .= ' · tiap ' . $item->interval_bulan . ' bln';
            return ['id' => $item->id_period, 'text' => $label];
        }));
    }
}
