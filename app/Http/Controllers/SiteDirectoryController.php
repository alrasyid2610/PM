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
 * Klik baris (atau tombol Buka) → lompat ke /business-relations?open={id_br}&tab=tabBrsSite&site={id_site}
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
            ->leftJoin('entitas as ent', 'ent.id_entitas', '=', 'br.id_entitas')
            ->leftJoin('business_estates as be', 'be.id_bestate', '=', 's.kawasan_bisnis')
            ->leftJoin('commercial_buildings as cb', 'cb.id_building', '=', 's.gedung')
            ->whereNull('s.deleted_at')
            ->orderBy('br.nama')
            ->orderBy('s.nama_lokasi')
            ->select([
                's.id_site',
                's.id_br',
                's.nama_lokasi',
                'br.nama as nama_br',
                's.kota_kabupaten',
                'ent.nama as entitas_br',
                'be.nama as nama_kawasan_bisnis',
                'cb.nama as nama_gedung',
                's.is_kantor_pusat',
                's.is_aktif',
            ]);

        // Pola list standar (sama seperti modul lain): kolom ditampilkan otomatis dari
        // key JSON (`data-datatable-auto-columns`), key `id_*` disembunyikan tapi tetap
        // ada di data baris (dipakai untuk klik baris). Kolom bantu dibuang di akhir.
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('nama_br', fn ($row) => brDisplayName($row->entitas_br, $row->nama_br))
            ->addColumn('lokasi_gedung', fn ($row) => $row->nama_gedung ?: ($row->nama_kawasan_bisnis ?: '—'))
            ->addColumn('tipe', fn ($row) => $row->is_kantor_pusat
                ? '<span class="pm-badge pm-badge--blue">Pusat</span>'
                : '<span class="pm-badge">Cabang</span>')
            ->addColumn('status', fn ($row) => $row->is_aktif
                ? '<span class="pm-badge pm-badge--green">Aktif</span>'
                : '<span class="pm-badge pm-badge--red">Non Aktif</span>')
            ->removeColumn('entitas_br', 'nama_kawasan_bisnis', 'nama_gedung', 'is_kantor_pusat', 'is_aktif')
            ->rawColumns(['tipe', 'status'])
            ->make(true);
    }
}
