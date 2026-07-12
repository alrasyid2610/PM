<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalendarController extends Controller
{
    public function index()
    {
        return view('calendar.index');
    }

    public function events(Request $request)
    {
        $start     = $request->input('start');
        $end       = $request->input('end');
        $types     = $request->input('types', 'fwo');
        $statuses  = $request->input('statuses', 'aktif,selesai');
        $searchBy  = $request->input('search_by');
        $searchQ   = trim($request->input('search_q', ''));
        if (is_string($types))    $types    = explode(',', $types);
        if (is_string($statuses)) $statuses = explode(',', $statuses);

        $showAktif   = in_array('aktif',   $statuses);
        $showSelesai = in_array('selesai', $statuses);

        $events = collect();

        // ── FWO ────────────────────────────────────────────────────────────────
        if (in_array('fwo', $types)) {
            $rows = DB::table('fieldworks as fw')
                ->leftJoin('work_orders as wo', 'fw.id_wo', '=', 'wo.id_wo')
                ->leftJoin('business_relation_sites as brs', 'fw.id_site_pelanggan_pekerjaan', '=', 'brs.id_site')
                ->whereNull('fw.deleted_at')
                ->whereNotNull('fw.tanggal_mulai')
                ->where(function ($q) use ($showAktif, $showSelesai) {
                    $q->when($showAktif,   fn($q) => $q->orWhere('fw.status', '!=', 'completed'))
                      ->when($showSelesai, fn($q) => $q->orWhere('fw.status', 'completed'));
                })
                ->when($start && $end, fn($q) => $q
                    ->where('fw.tanggal_mulai', '<=', $end)
                    ->where(fn($q2) => $q2
                        ->whereNull('fw.tanggal_selesai')
                        ->orWhere('fw.tanggal_selesai', '>=', $start)
                    )
                )
                ->when($searchBy && $searchQ, function ($q) use ($searchBy, $searchQ) {
                    $like = '%' . $searchQ . '%';
                    if ($searchBy === 'no')        $q->where('fw.no_fwo', 'like', $like);
                    elseif ($searchBy === 'judul')     $q->where('fw.judul_pekerjaan', 'like', $like);
                    elseif ($searchBy === 'pelanggan') $q->whereExists(fn($s) => $s->from('work_orders as _wo')
                        ->join('business_relations as _br', '_wo.id_pelanggan_pekerjaan', '=', '_br.id_br')
                        ->whereColumn('_wo.id_wo', 'fw.id_wo')->where('_br.nama', 'like', $like));
                    elseif ($searchBy === 'site')      $q->where('brs.nama_lokasi', 'like', $like);
                })
                ->select([
                    'fw.id_fwo as id', 'fw.no_fwo as no', 'fw.judul_pekerjaan',
                    'fw.tanggal_mulai', 'fw.tanggal_selesai', 'fw.status',
                    'wo.no_wo', 'brs.nama_lokasi as site_name',
                ])
                ->get();

            foreach ($rows as $r) {
                $color = $r->status === 'completed' ? '#10b981' : '#3b82f6';
                $events->push($this->makeEvent('fwo', $r->id, $r->no, $r->judul_pekerjaan, $r->tanggal_mulai, $r->tanggal_selesai, $color, $r->status, [
                    'no_wo'     => $r->no_wo,
                    'site_name' => $r->site_name,
                ]));
            }
        }

        // ── WO ─────────────────────────────────────────────────────────────────
        if (in_array('wo', $types)) {
            $rows = DB::table('work_orders as wo')
                ->leftJoin('business_relation_sites as brs', 'wo.id_site_pelanggan_pekerjaan', '=', 'brs.id_site')
                ->leftJoin('business_relations as br', 'wo.id_pelanggan_pekerjaan', '=', 'br.id_br')
                ->whereNull('wo.deleted_at')
                ->whereNotNull('wo.tanggal_mulai')
                ->where(function ($q) use ($showAktif, $showSelesai) {
                    $q->when($showAktif,   fn($q) => $q->orWhere('wo.status', '!=', 'selesai'))
                      ->when($showSelesai, fn($q) => $q->orWhere('wo.status', 'selesai'));
                })
                ->when($start && $end, fn($q) => $q
                    ->where('wo.tanggal_mulai', '<=', $end)
                    ->where(fn($q2) => $q2
                        ->whereNull('wo.tanggal_selesai')
                        ->orWhere('wo.tanggal_selesai', '>=', $start)
                    )
                )
                ->when($searchBy && $searchQ, function ($q) use ($searchBy, $searchQ) {
                    $like = '%' . $searchQ . '%';
                    if ($searchBy === 'no')            $q->where('wo.no_wo', 'like', $like);
                    elseif ($searchBy === 'judul')     $q->where('wo.judul_pekerjaan', 'like', $like);
                    elseif ($searchBy === 'pelanggan') $q->where('br.nama', 'like', $like);
                    elseif ($searchBy === 'site')      $q->where('brs.nama_lokasi', 'like', $like);
                })
                ->select([
                    'wo.id_wo as id', 'wo.no_wo as no', 'wo.judul_pekerjaan',
                    'wo.tanggal_mulai', 'wo.tanggal_selesai', 'wo.status',
                    'br.nama as pelanggan', 'brs.nama_lokasi as site_name',
                ])
                ->get();

            foreach ($rows as $r) {
                $color = $r->status === 'selesai' ? '#8b5cf6' : '#f59e0b';
                $events->push($this->makeEvent('wo', $r->id, $r->no, $r->judul_pekerjaan, $r->tanggal_mulai, $r->tanggal_selesai, $color, $r->status, [
                    'pelanggan' => $r->pelanggan,
                    'site_name' => $r->site_name,
                ]));
            }
        }

        // ── SO ─────────────────────────────────────────────────────────────────
        if (in_array('so', $types)) {
            $rows = DB::table('sales_orders as s')
                ->leftJoin('business_relations as br', 's.id_pelanggan', '=', 'br.id_br')
                ->leftJoin('business_relation_sites as brs', 's.id_site_pelanggan', '=', 'brs.id_site')
                ->whereNull('s.deleted_at')
                ->whereNotNull('s.tanggal_mulai')
                ->where(function ($q) use ($showAktif, $showSelesai) {
                    $q->when($showAktif,   fn($q) => $q->orWhere('s.status', '!=', 'selesai'))
                      ->when($showSelesai, fn($q) => $q->orWhere('s.status', 'selesai'));
                })
                ->when($start && $end, fn($q) => $q
                    ->where('s.tanggal_mulai', '<=', $end)
                    ->where(fn($q2) => $q2
                        ->whereNull('s.tanggal_selesai')
                        ->orWhere('s.tanggal_selesai', '>=', $start)
                    )
                )
                ->when($searchBy && $searchQ, function ($q) use ($searchBy, $searchQ) {
                    $like = '%' . $searchQ . '%';
                    if ($searchBy === 'no')            $q->where('s.no_so', 'like', $like);
                    elseif ($searchBy === 'judul')     $q->where('s.judul_order', 'like', $like);
                    elseif ($searchBy === 'pelanggan') $q->where('br.nama', 'like', $like);
                    elseif ($searchBy === 'site')      $q->where('brs.nama_lokasi', 'like', $like);
                })
                ->select([
                    's.id_so as id', 's.no_so as no', 's.judul_order as judul_pekerjaan',
                    's.tanggal_mulai', 's.tanggal_selesai', 's.status',
                    'br.nama as pelanggan', 'brs.nama_lokasi as site_name',
                ])
                ->get();

            foreach ($rows as $r) {
                $color = $r->status === 'selesai' ? '#6b7280' : '#ec4899';
                $events->push($this->makeEvent('so', $r->id, $r->no, $r->judul_pekerjaan, $r->tanggal_mulai, $r->tanggal_selesai, $color, $r->status, [
                    'pelanggan' => $r->pelanggan,
                    'site_name' => $r->site_name,
                ]));
            }
        }

        return response()->json($events->values());
    }

    private function makeEvent(string $type, $id, string $no, ?string $judul, ?string $mulai, ?string $selesai, string $color, ?string $status, array $extra = []): array
    {
        $start = $mulai ? substr($mulai, 0, 10) : null;
        $end   = $selesai
            ? date('Y-m-d', strtotime(substr($selesai, 0, 10) . ' +1 day'))
            : date('Y-m-d', strtotime($start . ' +1 day'));

        return [
            'id'       => $type . '-' . $id,
            'rawId'    => $id,
            'type'     => $type,
            'text'     => $no . ' — ' . ($judul ?? ''),
            'start'    => $start,
            'end'      => $end,
            'barColor' => $color,
            'data'     => array_merge([
                'no'     => $no,
                'judul'  => $judul,
                'status' => $status,
                'type'   => $type,
            ], $extra),
        ];
    }
}
