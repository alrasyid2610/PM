<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

/**
 * Halaman read-only "Site" — daftar semua Business Relation Site se-sistem
 * lintas Perusahaan, supaya tidak perlu buka BR satu-satu buat cari site
 * tertentu. Ditambahkan 2026-09-07 (BR & BRS tetap 1 workspace gabungan,
 * lihat BR.md — ini cuma jalan pintas navigasi, bukan modul CRUD baru).
 * Klik baris → lompat ke /business-relations?open={id_br}&tab=tabBrsSite&site={id_site}
 */
class SiteDirectoryController extends Controller
{
    public function index()
    {
        return view('site-directory.index');
    }

    public function data(Request $request)
    {
        $query = DB::table('business_relation_sites as s')
            ->join('business_relations as br', 'br.id_br', '=', 's.id_br')
            ->leftJoin('business_estates as be', 'be.id_bestate', '=', 's.kawasan_bisnis')
            ->leftJoin('commercial_buildings as cb', 'cb.id_building', '=', 's.gedung')
            ->whereNull('s.deleted_at')
            ->select([
                's.id_site',
                's.id_br',
                'br.nama as nama_br',
                's.nama_lokasi',
                's.kota_kabupaten',
                'be.nama as nama_kawasan_bisnis',
                'cb.nama as nama_gedung',
                's.is_kantor_pusat',
                's.is_aktif',
            ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('lokasi_gedung', fn ($row) => $row->nama_gedung ?: ($row->nama_kawasan_bisnis ?: '—'))
            ->addColumn('tipe_label', fn ($row) => $row->is_kantor_pusat
                ? '<span class="pm-badge pm-badge--blue">Pusat</span>'
                : '<span class="pm-badge">Cabang</span>')
            ->addColumn('status_label', fn ($row) => $row->is_aktif
                ? '<span class="pm-badge pm-badge--green">Aktif</span>'
                : '<span class="pm-badge pm-badge--red">Non Aktif</span>')
            ->addColumn('action', fn ($row) => '
                <a href="/business-relations?open=' . $row->id_br . '&tab=tabBrsSite&site=' . $row->id_site . '"
                    class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:11px;">
                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Buka
                </a>
            ')
            ->rawColumns(['tipe_label', 'status_label', 'action'])
            ->make(true);
    }
}
