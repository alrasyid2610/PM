<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelPdf\Facades\Pdf;
use Yajra\DataTables\Facades\DataTables;

/**
 * Menu "Lab Sample Reg" (grup Lab) — pendaftaran sample ke lab per Work Order. Sejak 2026-10-31, dulu tab di detail WO.
 *
 * Controller ini hanya mengurus halaman, pencarian WO, riwayat pendaftaran, form cetak, dan data tab read-only di WO.
 * Operasi sample/registrasinya sendiri (daftar, buat slot, edit, hapus) tetap di WoSampleController (`/wo-samples/*`,
 * izin menunya `lab-sample-regs` lewat CheckMenuPermission::SLUG_MAP) — model data tidak berubah.
 */
class LabSampleRegController extends Controller
{
    public function index()
    {
        return view('lab-sample-reg.index', ['title' => 'Lab Sample Reg']);
    }

    /** Ringkasan WO untuk header halaman/form cetak (null bila WO tidak ada). */
    public static function woInfo(int $idWo): ?object
    {
        $wo = DB::table('work_orders as w')
            ->leftJoin('sales_orders as so', 'so.id_so', '=', 'w.id_so')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'so.id_pelanggan')
            ->leftJoin('entitas as ent', 'ent.id_entitas', '=', 'br.id_entitas')
            ->leftJoin('business_relation_sites as brs', 'brs.id_site', '=', 'w.id_site_pelanggan_pekerjaan')
            ->where('w.id_wo', $idWo)
            ->whereNull('w.deleted_at')
            ->first([
                'w.id_wo', 'w.no_wo', 'w.judul_pekerjaan', 'w.status', 'so.id_so', 'so.no_so', 'so.judul_order',
                'br.nama as nama_pelanggan', 'ent.nama as entitas_pelanggan',
                'brs.nama_lokasi as nama_site', 'brs.alamat_lengkap as alamat_site',
            ]);

        if ($wo) $wo->nama_pelanggan_display = brDisplayName($wo->entitas_pelanggan, $wo->nama_pelanggan);

        return $wo;
    }

    /**
     * Pencarian untuk kolom "Pilih Work Order" — hasilnya dikelompokkan jelas per jenis:
     *   - "Work Order": cocok no. WO, judul WO, no. SO, judul order, atau pelanggan
     *   - "Fieldwork Order": cocok no. FWO atau judul FWO (ditampilkan sebagai FWO, bukan WO induknya)
     * Memilih WO = seluruh sample WO itu; memilih FWO = cakupan dipersempit ke FWO itu (WO induknya tetap menjadi konteks
     * pendaftaran, karena 1 pendaftaran = 1 WO). Id hasil: `wo:{id}` atau `fwo:{id}`, plus `id_wo`/`id_fwo`.
     */
    public function woOptions(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $like = '%' . $q . '%';

        $woRows = DB::table('work_orders as w')
            ->leftJoin('sales_orders as so', 'so.id_so', '=', 'w.id_so')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'so.id_pelanggan')
            ->leftJoin('entitas as ent', 'ent.id_entitas', '=', 'br.id_entitas')
            ->whereNull('w.deleted_at')
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($like) {
                $w->where('w.no_wo', 'like', $like)
                    ->orWhere('w.judul_pekerjaan', 'like', $like)
                    ->orWhere('so.no_so', 'like', $like)
                    ->orWhere('so.judul_order', 'like', $like)
                    ->orWhere('br.nama', 'like', $like);
            }))
            ->orderByDesc('w.id_wo')
            ->limit(20)
            ->get(['w.id_wo', 'w.no_wo', 'w.judul_pekerjaan', 'w.status', 'so.no_so', 'br.nama as nama_pelanggan', 'ent.nama as entitas_pelanggan']);

        $groups = [];

        if ($woRows->isNotEmpty()) {
            $groups[] = [
                'text' => 'Work Order',
                'children' => $woRows->map(fn ($r) => [
                    'id'     => 'wo:' . $r->id_wo,
                    'id_wo'  => $r->id_wo,
                    'id_fwo' => null,
                    'text'   => trim(($r->no_wo ?? '-') . ' — ' . ($r->judul_pekerjaan ?: '-')
                        . ($r->no_so ? ' · ' . $r->no_so : '')
                        . ($r->nama_pelanggan ? ' · ' . brDisplayName($r->entitas_pelanggan, $r->nama_pelanggan) : '')
                        . ($r->status === 'completed' ? ' (selesai)' : '')),
                ])->values(),
            ];
        }

        // FWO hanya dicari bila user mengetik sesuatu (daftar awal cukup berisi WO terbaru)
        if ($q !== '') {
            $fwoRows = DB::table('fieldworks as f')
                ->join('work_orders as w', 'w.id_wo', '=', 'f.id_wo')
                ->leftJoin('sales_orders as so', 'so.id_so', '=', 'w.id_so')
                ->leftJoin('business_relations as br', 'br.id_br', '=', 'so.id_pelanggan')
                ->leftJoin('entitas as ent', 'ent.id_entitas', '=', 'br.id_entitas')
                ->whereNull('f.deleted_at')->whereNull('w.deleted_at')
                ->where(fn ($x) => $x->where('f.no_fwo', 'like', $like)->orWhere('f.judul_pekerjaan', 'like', $like))
                ->orderByDesc('f.id_fwo')
                ->limit(20)
                ->get(['f.id_fwo', 'f.no_fwo', 'f.judul_pekerjaan as judul_fwo', 'w.id_wo', 'w.no_wo', 'w.status', 'so.no_so', 'br.nama as nama_pelanggan', 'ent.nama as entitas_pelanggan']);

            if ($fwoRows->isNotEmpty()) {
                $groups[] = [
                    'text' => 'Fieldwork Order',
                    'children' => $fwoRows->map(fn ($r) => [
                        'id'     => 'fwo:' . $r->id_fwo,
                        'id_wo'  => $r->id_wo,
                        'id_fwo' => $r->id_fwo,
                        'text'   => trim(($r->no_fwo ?? '-') . ' — ' . ($r->judul_fwo ?: '-')
                            . ' · ' . ($r->no_wo ?? '-')
                            . ($r->no_so ? ' · ' . $r->no_so : '')
                            . ($r->nama_pelanggan ? ' · ' . brDisplayName($r->entitas_pelanggan, $r->nama_pelanggan) : '')
                            . ($r->status === 'completed' ? ' (WO selesai)' : '')),
                    ])->values(),
                ];
            }
        }

        return response()->json($groups);
    }

    /** Riwayat semua pendaftaran (DataTable) — tampil di halaman saat belum ada WO yang dipilih. */
    public function riwayat(Request $request)
    {
        $query = DB::table('wo_lab_sample_regs as r')
            ->join('work_orders as w', 'w.id_wo', '=', 'r.id_wo')
            ->leftJoin('sales_orders as so', 'so.id_so', '=', 'w.id_so')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'so.id_pelanggan')
            ->leftJoin('entitas as ent', 'ent.id_entitas', '=', 'br.id_entitas')
            ->leftJoin('personnel as p', 'p.id_personnel', '=', 'r.id_personnel_penerima')
            ->whereNull('w.deleted_at')
            ->select([
                'r.id_wo_lab_sample_reg', 'r.id_wo', 'r.no_registrasi', 'r.tanggal_registrasi',
                'w.no_wo', 'so.no_so', 'p.nama as nama_penerima',
                'br.nama as nama_pelanggan', 'ent.nama as entitas_pelanggan',
                DB::raw('(SELECT COUNT(*) FROM lab_samples ls WHERE ls.id_wo_lab_sample_reg = r.id_wo_lab_sample_reg) as jumlah_sample'),
            ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('nama_pelanggan', fn ($r) => $r->nama_pelanggan ? brDisplayName($r->entitas_pelanggan, $r->nama_pelanggan) : null)
            ->filterColumn('no_registrasi', fn ($q, $k) => $q->where('r.no_registrasi', 'like', "%{$k}%"))
            ->filterColumn('no_wo', fn ($q, $k) => $q->where('w.no_wo', 'like', "%{$k}%"))
            ->filterColumn('no_so', fn ($q, $k) => $q->where('so.no_so', 'like', "%{$k}%"))
            ->filterColumn('nama_pelanggan', fn ($q, $k) => $q->where('br.nama', 'like', "%{$k}%"))
            ->filterColumn('nama_penerima', fn ($q, $k) => $q->where('p.nama', 'like', "%{$k}%"))
            ->orderColumn('tanggal_registrasi', 'r.tanggal_registrasi $1, r.id_wo_lab_sample_reg $1')
            ->order(fn ($q) => $q->orderByDesc('r.tanggal_registrasi')->orderByDesc('r.id_wo_lab_sample_reg'))
            ->make(true);
    }

    /** Data tab read-only "Lab Sample Reg" di detail WO — daftar pendaftaran milik WO itu. */
    public function woRegs($idWo)
    {
        abort_unless(userCanTab('wo-tab-lab-sample-reg'), 403, 'Anda tidak memiliki akses ke tab ini');

        $wo = DB::table('work_orders')->where('id_wo', $idWo)->whereNull('deleted_at')->first(['id_wo']);
        if (!$wo) return response()->json(['message' => 'WO tidak ditemukan'], 404);

        return response()->json(['regs' => app(WoSampleController::class)->regGroupsByWo($idWo)]);
    }

    /** Form Registrasi Sample (PDF) untuk 1 pendaftaran. */
    public function print($id)
    {
        $reg = DB::table('wo_lab_sample_regs as r')
            ->leftJoin('personnel as p', 'p.id_personnel', '=', 'r.id_personnel_penerima')
            ->where('r.id_wo_lab_sample_reg', $id)
            ->first(['r.*', 'p.nama as nama_penerima']);
        abort_if(!$reg, 404, 'Pendaftaran tidak ditemukan');

        $wo = self::woInfo((int) $reg->id_wo);
        abort_if(!$wo, 404, 'Work Order tidak ditemukan');

        $samples = collect(app(WoSampleController::class)->regGroupsByWo($reg->id_wo))
            ->firstWhere('id_wo_lab_sample_reg', (int) $id)->samples ?? collect();

        // No. FWO per sample (sample asal FWO tidak punya id_wo, induknya lewat FWO)
        $fwoNo = DB::table('fieldworks')->whereIn('id_fwo', collect($samples)->pluck('id_fwo')->filter()->unique())->pluck('no_fwo', 'id_fwo');

        return Pdf::view('pdf.lab-sample-reg.form', [
            'reg'     => $reg,
            'wo'      => $wo,
            'samples' => collect($samples),
            'fwoNo'   => $fwoNo,
        ])
            ->headerView('pdf.layouts.sections.header')
            ->footerView('pdf.layouts.sections.footer')
            ->margins(top: 30, right: 0, bottom: 20, left: 0)
            ->format('a4')
            ->name('Form-Registrasi-Sample-' . ($reg->no_registrasi ?: $reg->id_wo_lab_sample_reg) . '.pdf');
    }
}
