<?php

namespace App\Http\Controllers;

use App\Support\DataScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard berbasis widget. Katalog widget: config/dashboard_widgets.php.
 * Akses per widget = slug permission (mode 'action', can_update) yang diatur di
 * Grup Menu / User Management. Penjagaan dilakukan DI SINI (server), bukan cuma
 * menyembunyikan tampilan — route /dashboard sendiri terbuka untuk semua user login.
 */
class DashboardController extends Controller
{
    /** Widget yang boleh dilihat user saat ini, urut sesuai katalog. */
    private function allowedWidgets(): array
    {
        return array_values(array_filter(
            config('dashboard_widgets', []),
            fn ($w) => userCan($w['slug'], 'can_update')
        ));
    }

    public function index()
    {
        $allowed = $this->allowedWidgets();
        $byType  = fn (string $t) => array_values(array_filter($allowed, fn ($w) => $w['type'] === $t));

        $tables = $byType('table');

        return view('dashboard.index', [
            'kpis'   => $byType('kpi'),
            'charts' => $byType('chart'),
            'tables' => $tables,
            // Kartu KPI hanya bisa diklik ke tab tabel yang juga diizinkan
            'tableKeys' => array_column($tables, 'key'),
            'adaWidget' => !empty($allowed),
        ]);
    }

    /** Data 1 widget (kpi / chart). 403 bila user tidak punya akses widget tersebut. */
    public function widget(Request $request, string $slug)
    {
        $widget = collect(config('dashboard_widgets', []))->firstWhere('slug', $slug);
        if (!$widget || $widget['type'] === 'table') {
            return response()->json(['message' => 'Widget tidak ditemukan'], 404);
        }
        if (!userCan($slug, 'can_update')) {
            return response()->json(['message' => 'Anda tidak memiliki akses ke widget ini'], 403);
        }

        return response()->json(match ($slug) {
            'dash-so-outstanding'      => $this->kpiSoOutstanding(),
            'dash-wo-outstanding'      => $this->kpiWoOutstanding(),
            'dash-fwo-outstanding'     => $this->kpiFwoOutstanding(),
            'dash-fwo-belum-jadwal'    => $this->kpiFwoBelumJadwal(),
            'dash-termin-outstanding'  => $this->kpiTerminOutstanding(),
            'dash-overdue'             => $this->kpiOverdue(),
            'dash-so-per-bulan'        => $this->chartSoPerBulan($request),
            'dash-peta-customer'       => $this->petaCustomer($request),
            'dash-so-nilai-per-bulan'  => $this->chartSoNilaiPerBulan($request),
            'dash-sq-final'            => $this->kpiSqFinal(),
            'dash-sq-pending'          => $this->kpiSqPending(),
            'dash-sq-belum-budget-fwo' => $this->kpiSqBelumBudgetFwo(),
            'dash-sq-kedaluwarsa'      => $this->kpiSqKedaluwarsa(),
            'dash-sq-per-status'       => $this->chartSqPerStatus(),
            default                    => ['message' => 'Widget belum punya data'],
        });
    }

    // ── KPI ──────────────────────────────────────────────────────────────

    private function kpiSoOutstanding(): array
    {
        $n = DB::table('sales_orders')
            ->tap(fn ($q) => DataScope::so($q))
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['completed', 'cancel'])
            ->count();

        return ['value' => $n, 'sub' => 'SO aktif'];
    }

    private function kpiWoOutstanding(): array
    {
        $n = DB::table('work_orders')
            ->tap(fn ($q) => DataScope::wo($q))
            ->whereNull('deleted_at')
            ->where('status', 'onprogress')
            ->count();

        return ['value' => $n, 'sub' => 'WO on progress'];
    }

    /** FWO belum dijadwalkan: belum selesai dan tanggal mulainya belum diisi (mis. FWO hasil Terbitkan SO). */
    private function fwoBelumJadwalQuery($base = null)
    {
        return ($base ?? DB::table('fieldworks as fw')->tap(fn ($q) => DataScope::fwo($q, 'fw')))
            ->whereNull('fw.deleted_at')
            ->whereNull('fw.tanggal_mulai')
            ->where('fw.status', '!=', 'completed');
    }

    private function kpiFwoBelumJadwal(): array
    {
        return ['value' => $this->fwoBelumJadwalQuery()->count(), 'sub' => 'menunggu jadwal dari tim sampling'];
    }

    private function kpiFwoOutstanding(): array
    {
        // FWO belum selesai = Planned + On Progress (On Progress = planned yang tanggal mulainya tiba, atau dimulai manual)
        $aktif = DB::table('fieldworks as fw')
            ->tap(fn ($q) => DataScope::fwo($q, 'fw'))
            ->whereNull('fw.deleted_at')
            ->where('fw.status', '!=', 'completed');
        $n          = (clone $aktif)->count();
        $onProgress = (clone $aktif)->whereRaw('(' . fwoStatusSql('fw') . ") = 'on_progress'")->count();

        return ['value' => $n, 'sub' => 'FWO belum selesai' . ($onProgress ? " ({$onProgress} on progress)" : '')];
    }

    private function kpiTerminOutstanding(): array
    {
        $n = DB::table('termin')->where('status', '!=', 'selesai')->count();

        return ['value' => $n, 'sub' => 'belum selesai'];
    }

    private function kpiOverdue(): array
    {
        $today = now()->toDateString();

        $wo = DB::table('work_orders')
            ->tap(fn ($q) => DataScope::wo($q))
            ->whereNull('deleted_at')
            ->where('status', '!=', 'completed')
            ->whereNotNull('tanggal_selesai')
            ->whereDate('tanggal_selesai', '<', $today)
            ->count();

        // Status FWO yang benar `completed` (sebelumnya salah dibandingkan dengan 'selesai'
        // sehingga FWO yang sudah selesai ikut terhitung overdue)
        $fwo = DB::table('fieldworks')
            ->tap(fn ($q) => DataScope::fwo($q))
            ->whereNull('deleted_at')
            ->where('status', '!=', 'completed')
            ->whereNotNull('tanggal_selesai')
            ->whereDate('tanggal_selesai', '<', $today)
            ->count();

        return ['value' => $wo + $fwo, 'sub' => 'lewat tenggat'];
    }

    // ── Peta customer ────────────────────────────────────────────────────

    /** Kunci pencocokan nama wilayah: huruf kecil, hanya a-z (abaikan titik/spasi/tanda baca). */
    private function wilayahKey(?string $s): string
    {
        $k = preg_replace('/[^a-z]/', '', strtolower((string) $s));
        // Penulisan singkat yang umum → nama resmi di tabel `wilayah`
        return ['dkijakarta' => 'daerahkhususibukotajakarta', 'diy' => 'daerahistimewayogyakarta'][$k] ?? $k;
    }

    /**
     * Peta customer per provinsi.
     *
     * Parameter:
     *  - metrik   : customer (default) | site | nilai (nilai SO, Rp)
     *  - cakupan  : semua (default) = semua Site customer · so = hanya customer yang punya SO (non-cancel),
     *               lokasi diambil dari Site Pemesan pada SO. metrik=nilai otomatis cakupan so.
     *  - tahun    : filter tahun SO (hanya berlaku untuk cakupan so / metrik nilai)
     *  - kategori : id_kategori_bisnis customer
     *  - mode     : kotor | bersih (hanya metrik nilai; default bersih)
     *  - kode     : rincian 1 provinsi (kota + daftar customer)
     *  - belum=1  : daftar site/SO yang provinsinya kosong / tidak dikenali
     *
     * Customer dengan lokasi di beberapa provinsi dihitung di tiap provinsinya (jumlah di peta bisa
     * lebih besar dari customer unik). Provinsi dicocokkan ke tabel `wilayah` lewat nama.
     */
    private function petaCustomer(Request $request): array
    {
        $metrik  = in_array($request->metrik, ['site', 'nilai'], true) ? $request->metrik : 'customer';
        $cakupan = ($metrik === 'nilai' || $request->cakupan === 'so') ? 'so' : 'semua';
        $tahun   = $cakupan === 'so' && $request->filled('tahun') ? (int) $request->tahun : null;
        $kategori = $request->filled('kategori') ? (int) $request->kategori : null;
        $mode    = $request->mode === 'kotor' ? 'kotor' : 'bersih';

        $provinsi = [];                 // kode => nama (tabel wilayah, 38 provinsi)
        $kodeByKey = [];                // kunci nama → kode
        foreach (DB::table('wilayah')->whereRaw("kode NOT LIKE '%.%'")->orderBy('kode')->get(['kode', 'nama']) as $w) {
            $provinsi[(string) $w->kode] = $w->nama;
            $kodeByKey[$this->wilayahKey($w->nama)] = (string) $w->kode;
        }

        // ── Rekaman dasar: 1 baris per Site (cakupan semua) atau per SO (cakupan so) ──
        if ($cakupan === 'semua') {
            $rek = DB::table('business_relation_sites as s')
                ->join('business_relations as br', 'br.id_br', '=', 's.id_br')
                ->leftJoin('entitas as ent', 'ent.id_entitas', '=', 'br.id_entitas')
                ->whereNull('s.deleted_at')
                ->when($kategori, fn ($q) => $q->where('br.id_kategori_bisnis', $kategori))
                ->get(['s.id_site', 's.id_br', 's.nama_lokasi', 's.provinsi', 's.kota_kabupaten', 'br.nama as nama_br', 'ent.nama as entitas'])
                ->map(fn ($r) => (object) array_merge((array) $r, ['nilai' => 0]));
        } else {
            $sos = DB::table('sales_orders as so')
                ->tap(fn ($q) => DataScope::so($q, 'so'))
                ->join('business_relations as br', 'br.id_br', '=', 'so.id_pelanggan')
                ->leftJoin('entitas as ent', 'ent.id_entitas', '=', 'br.id_entitas')
                ->leftJoin('business_relation_sites as s', 's.id_site', '=', 'so.id_site_pelanggan')
                ->whereNull('so.deleted_at')
                ->where('so.status', '!=', 'cancel')
                ->when($tahun, fn ($q) => $q->whereYear('so.tanggal_so', $tahun))
                ->when($kategori, fn ($q) => $q->where('br.id_kategori_bisnis', $kategori))
                ->get([
                    'so.id_so', 'so.discount', 'so.discount_persen', 'so.id_pelanggan as id_br', 'so.id_site_pelanggan as id_site',
                    's.nama_lokasi', 's.provinsi', 's.kota_kabupaten', 'br.nama as nama_br', 'ent.nama as entitas',
                ]);
            $nilaiSo = $metrik === 'nilai' ? $this->soNilai($sos) : [];
            $rek = $sos->map(fn ($r) => (object) array_merge((array) $r, ['nilai' => (int) ($nilaiSo[$r->id_so][$mode] ?? 0)]));
        }

        $kodeProv = fn ($r) => $kodeByKey[$this->wilayahKey($r->provinsi)] ?? null;

        // ── Daftar yang belum terpetakan ──
        if ($request->boolean('belum')) {
            $list = $rek->filter(fn ($r) => !$kodeProv($r))
                ->unique(fn ($r) => $r->id_br . '-' . ($r->id_site ?? 'x'))
                ->map(fn ($r) => [
                    'id_br' => $r->id_br, 'id_site' => $r->id_site,
                    'customer' => brDisplayName($r->entitas, $r->nama_br),
                    'site' => $r->nama_lokasi, 'provinsi' => $r->provinsi, 'kota' => $r->kota_kabupaten,
                ])->sortBy('customer')->values()->take(300)->all();

            return ['belum' => $list];
        }

        // ── Rincian 1 provinsi ──
        if ($request->filled('kode')) {
            $kode = (string) $request->kode;
            abort_unless(isset($provinsi[$kode]), 404, 'Provinsi tidak ditemukan');

            $kota = [];
            foreach ($rek as $r) {
                if ($kodeProv($r) !== $kode) continue;
                $namaKota = trim((string) $r->kota_kabupaten) ?: '(kota belum diisi)';
                $key = $this->wilayahKey($namaKota) ?: $namaKota;
                $kota[$key]['kota'] = $kota[$key]['kota'] ?? $namaKota;
                $kota[$key]['sites'][$r->id_site ?? 'x' . $r->id_br] = true;
                $kota[$key]['nilai'] = ($kota[$key]['nilai'] ?? 0) + $r->nilai;
                $kota[$key]['customers'][$r->id_br]['nama'] = brDisplayName($r->entitas, $r->nama_br);
                $kota[$key]['customers'][$r->id_br]['nilai'] = ($kota[$key]['customers'][$r->id_br]['nilai'] ?? 0) + $r->nilai;
            }

            $rows = collect($kota)->map(fn ($k) => [
                'kota'     => $k['kota'],
                'site'     => count($k['sites']),
                'customer' => count($k['customers']),
                'nilai'    => $k['nilai'],
                'daftar'   => collect($k['customers'])
                    ->map(fn ($c, $id) => ['id_br' => $id, 'nama' => $c['nama'], 'nilai' => $c['nilai']])
                    ->sortBy($metrik === 'nilai' ? fn ($c) => -$c['nilai'] : 'nama')->values()->all(),
            ])->sortByDesc($metrik === 'nilai' ? 'nilai' : ($metrik === 'site' ? 'site' : 'customer'))->values()->all();

            return ['kode' => $kode, 'provinsi' => $provinsi[$kode], 'metrik' => $metrik, 'kota' => $rows];
        }

        // ── Ringkasan semua provinsi ──
        $per = [];
        $belumSite = [];
        $belumCustomer = [];
        $semuaCustomer = [];
        $semuaSite = [];

        foreach ($rek as $r) {
            $kode = $kodeProv($r);
            $siteKey = $r->id_site ?? 'x' . $r->id_br;
            if (!$kode) {
                $belumSite[$siteKey] = true;
                $belumCustomer[$r->id_br] = true;
                continue;
            }
            $per[$kode]['customers'][$r->id_br] = true;
            $per[$kode]['sites'][$siteKey] = true;
            $per[$kode]['nilai'] = ($per[$kode]['nilai'] ?? 0) + $r->nilai;
            $semuaCustomer[$r->id_br] = true;
            $semuaSite[$siteKey] = true;
        }

        $data = [];
        foreach ($provinsi as $kode => $nama) {
            $data[] = [
                'kode'     => $kode,
                'provinsi' => $nama,
                'customer' => count($per[$kode]['customers'] ?? []),
                'site'     => count($per[$kode]['sites'] ?? []),
                'nilai'    => (int) ($per[$kode]['nilai'] ?? 0),
            ];
        }

        return [
            'kind'           => 'peta',
            'metrik'         => $metrik,
            'cakupan'        => $cakupan,
            'tahun'          => $tahun,
            'kategori'       => $kategori,
            'mode'           => $mode,
            'provinsi'       => $data,
            'max'            => max(array_column($data, $metrik) ?: [0]),
            'total_customer' => count($semuaCustomer),   // customer unik yang punya ≥1 lokasi terpetakan
            'total_site'     => count($semuaSite),
            'total_nilai'    => array_sum(array_column($data, 'nilai')),
            'belum_site'     => count($belumSite),
            'belum_customer' => count($belumCustomer),
            // pilihan filter di toolbar
            'years'          => $this->soYears(),
            'kategori_opsi'  => DB::table('kategori_bisnis')->whereNull('deleted_at')->orderBy('nama')->get(['id_kategori_bisnis as id', 'nama']),
        ];
    }

    // ── SQ ───────────────────────────────────────────────────────────────
    // Hanya revisi terbaru (is_latest) yang dihitung — revisi lama berstatus Void "digantikan".
    // Final = terkunci, menunggu keputusan customer (Win/Lose). Win = disetujui; "belum jadi SO" = Win tanpa SO.

    private function sqFinalQuery()
    {
        return DB::table('sales_quotations')
            ->tap(fn ($q) => DataScope::sq($q))
            ->whereNull('deleted_at')
            ->where('is_latest', true)
            ->where('status', 'final');
    }

    private function sqWinBelumSoQuery()
    {
        return DB::table('sales_quotations as sq')
            ->tap(fn ($q) => DataScope::sq($q, 'sq'))
            ->whereNull('sq.deleted_at')
            ->where('sq.is_latest', true)
            ->where('sq.status', 'win')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('sales_orders as so')
                    ->whereColumn('so.id_sq', 'sq.id_sq')->whereNull('so.deleted_at');
            });
    }

    private function kpiSqFinal(): array
    {
        return ['value' => $this->sqWinBelumSoQuery()->count(), 'sub' => 'menunggu diterbitkan SO'];
    }

    /** SQ terbaru yang sudah diajukan dan menunggu keputusan manager (status pending). */
    private function sqPendingQuery()
    {
        return DB::table('sales_quotations as sq')
            ->tap(fn ($q) => DataScope::sq($q, 'sq'))
            ->whereNull('sq.deleted_at')
            ->where('sq.is_latest', true)
            ->where('sq.status', 'pending');
    }

    private function kpiSqPending(): array
    {
        return ['value' => $this->sqPendingQuery()->count(), 'sub' => 'menunggu keputusan manager'];
    }

    /**
     * Satuan = SQ FWO (1 baris = 1 SQ FWO): SQ FWO milik SQ terbaru aktif (Draft / Menunggu Approval / Final / Win;
     * Lose & Void tidak dihitung) yang belum punya Budget Plan (sq_fwo_budgets).
     * $q harus memakai alias tabel `s` untuk sales_quotations; alias `swo` & `sf` ditambahkan di sini.
     */
    private function sqBelumBudgetFwoFilter($q)
    {
        return $q->join('sq_work_orders as swo', 'swo.id_sq', '=', 's.id_sq')
            ->join('sq_fieldworks as sf', 'sf.id_sq_wo', '=', 'swo.id_sq_wo')
            ->whereNull('s.deleted_at')
            ->where('s.is_latest', true)
            ->whereIn('s.status', ['draft', 'pending', 'final', 'win'])
            ->whereNotExists(function ($b) {
                $b->select(DB::raw(1))->from('sq_fwo_budgets as sb')
                    ->whereColumn('sb.id_sq_fwo', 'sf.id_sq_fwo');
            });
    }

    private function kpiSqBelumBudgetFwo(): array
    {
        $n = $this->sqBelumBudgetFwoFilter(
            DB::table('sales_quotations as s')->tap(fn ($q) => DataScope::sq($q, 's'))
        )->count();

        return ['value' => $n, 'sub' => 'SQ FWO belum punya Budget Plan'];
    }

    private function kpiSqKedaluwarsa(): array
    {
        $today = now()->toDateString();
        $batas = now()->addDays(14)->toDateString();

        $segera = $this->sqFinalQuery()
            ->whereNotNull('berlaku_sampai')
            ->whereDate('berlaku_sampai', '>=', $today)
            ->whereDate('berlaku_sampai', '<=', $batas)
            ->count();

        $lewat = $this->sqFinalQuery()
            ->whereNotNull('berlaku_sampai')
            ->whereDate('berlaku_sampai', '<', $today)
            ->count();

        return ['value' => $segera, 'sub' => "dalam 14 hari · {$lewat} sudah lewat"];
    }

    private function chartSqPerStatus(): array
    {
        $counts = DB::table('sales_quotations')
            ->tap(fn ($q) => DataScope::sq($q))
            ->whereNull('deleted_at')
            ->where('is_latest', true)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Urutan & warna tetap supaya konsisten dengan badge status SQ
        $def = [
            'draft'     => ['Draft', '#94a3b8'],
            'pending'   => ['Menunggu Approval', '#d97706'],
            'final'     => ['Final', '#1d4ed8'],
            'win'       => ['Win', '#15803d'],
            'lose'      => ['Lose', '#dc2626'],
            'void'      => ['Void', '#c2410c'],
        ];

        $labels = $data = $colors = [];
        foreach ($def as $status => [$label, $color]) {
            $labels[] = $label;
            $data[]   = (int) ($counts[$status] ?? 0);
            $colors[] = $color;
        }

        return ['kind' => 'donut', 'labels' => $labels, 'data' => $data, 'colors' => $colors, 'total' => array_sum($data)];
    }

    // ── Chart ────────────────────────────────────────────────────────────

    private function chartSoPerBulan(Request $request): array
    {
        $year = (int) ($request->year ?: now()->year);

        $data = DB::table('sales_orders')
            ->tap(fn ($q) => DataScope::so($q))
            ->whereNull('deleted_at')
            ->whereNotNull('tanggal_so')
            ->whereYear('tanggal_so', $year)
            ->selectRaw('MONTH(tanggal_so) as bulan, COUNT(*) as total')
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $series = [];
        for ($i = 1; $i <= 12; $i++) {
            $series[] = (int) ($data[$i] ?? 0);
        }

        return ['year' => $year, 'years' => $this->soYears(), 'data' => $series, 'total' => array_sum($series)];
    }

    /** Tahun yang punya SO (untuk pilihan di widget), selalu termasuk tahun berjalan. */
    private function soYears()
    {
        return DB::table('sales_orders')
            ->tap(fn ($q) => DataScope::so($q))
            ->whereNull('deleted_at')
            ->whereNotNull('tanggal_so')
            ->selectRaw('YEAR(tanggal_so) as y')
            ->distinct()
            ->pluck('y')
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();
    }

    /**
     * Nilai SO per bulan (Rp), filter mode:
     *  - kotor : sebelum discount  = Σ(qty × harga) BOQ + BOQ Other/Sampling
     *  - bersih: sesudah discount  = (BOQ − discount per item) + BOQ Other/Sampling − discount SO
     * Rumus bersih sama dengan "Subtotal" & discount SO di printout/detail SO
     * (SalesOrderController::soSubtotal / soEffectiveDiscount). SO berstatus cancel tidak dihitung.
     */
    private function chartSoNilaiPerBulan(Request $request): array
    {
        $year = (int) ($request->year ?: now()->year);
        $mode = in_array($request->mode, ['kotor', 'semua'], true) ? $request->mode : 'bersih';

        $sos = DB::table('sales_orders')
            ->tap(fn ($q) => DataScope::so($q))
            ->whereNull('deleted_at')
            ->where('status', '!=', 'cancel')
            ->whereNotNull('tanggal_so')
            ->whereYear('tanggal_so', $year)
            ->get(['id_so', 'tanggal_so', 'discount', 'discount_persen']);

        // Kedua nilai selalu dihitung (murah); mode hanya menentukan mana yang ditampilkan
        $kotorBulan  = array_fill(1, 12, 0);
        $bersihBulan = array_fill(1, 12, 0);

        $nilai = $this->soNilai($sos);
        foreach ($sos as $so) {
            $bulan = (int) date('n', strtotime($so->tanggal_so));
            $kotorBulan[$bulan]  += $nilai[$so->id_so]['kotor'];
            $bersihBulan[$bulan] += $nilai[$so->id_so]['bersih'];
        }

        $kotor  = array_values($kotorBulan);
        $bersih = array_values($bersihBulan);

        return [
            'kind'         => 'bar_tahun_nilai',
            'year'         => $year,
            'years'        => $this->soYears(),
            'mode'         => $mode,
            'kotor'        => $kotor,
            'bersih'       => $bersih,
            'total_kotor'  => array_sum($kotor),
            'total_bersih' => array_sum($bersih),
        ];
    }

    /**
     * Nilai tiap SO (Rp): ['id_so' => ['kotor' => …, 'bersih' => …]]. Dipakai grafik nilai per bulan & peta.
     * Kotor = Σ(qty × harga) BOQ + BOQ Other/Sampling; bersih = (BOQ − discount item) + BOQ Other/Sampling
     * − discount SO (persen × subtotal bila persen tersimpan, kalau tidak Rupiah tersimpan).
     * $sos = koleksi objek dengan id_so, discount, discount_persen. WO/BOQ terhapus tidak dihitung.
     */
    private function soNilai($sos): array
    {
        $out = [];
        if ($sos->isEmpty()) return $out;
        $ids = $sos->pluck('id_so');

        $boq = DB::table('boq as b')
            ->join('work_orders as wo', 'wo.id_wo', '=', 'b.id_wo')
            ->whereIn('wo.id_so', $ids)
            ->whereNull('wo.deleted_at')
            ->whereNull('b.deleted_at')
            ->groupBy('wo.id_so')
            ->selectRaw('wo.id_so, SUM(b.qty * b.harga) as gross, SUM(GREATEST(0, b.qty * b.harga - b.discount)) as net')
            ->get()->keyBy('id_so');

        // BOQ Other + BOQ Sampling (tabel boq_tambahan) — tidak punya discount
        $tambahan = DB::table('boq_tambahan as bt')
            ->join('work_orders as wo', 'wo.id_wo', '=', 'bt.id_wo')
            ->whereIn('wo.id_so', $ids)
            ->whereNull('wo.deleted_at')
            ->whereNull('bt.deleted_at')
            ->groupBy('wo.id_so')
            ->selectRaw('wo.id_so, SUM(bt.qty * bt.harga) as total')
            ->pluck('total', 'id_so');

        foreach ($sos as $so) {
            $tam   = (int) ($tambahan[$so->id_so] ?? 0);
            $gross = (int) ($boq[$so->id_so]->gross ?? 0) + $tam;
            $sub   = (int) ($boq[$so->id_so]->net ?? 0) + $tam;

            $discSo = $so->discount_persen !== null
                ? (int) round($sub * (float) $so->discount_persen / 100)
                : (int) ($so->discount ?? 0);

            $out[$so->id_so] = ['kotor' => $gross, 'bersih' => max(0, $sub - $discSo)];
        }

        return $out;
    }

    // ── Tabel ────────────────────────────────────────────────────────────

    /** Tambah kolom "Hasil" (sama dengan index FWO) dan buang kolom bantu jml_tidak_terlaksana. */
    private function fwoRowsWithHasil($rows)
    {
        return $rows->each(function ($r) {
            $r->hasil = FieldworkController::hasilHtml($r);
            unset($r->jml_tidak_terlaksana);
        });
    }

    public function list(Request $request)
    {
        $type = $request->input('type', 'so');

        // Tiap tab tabel = 1 widget (dash-list-{type}) dengan izin sendiri; tanpa akses → 403
        $widget = collect(config('dashboard_widgets', []))->firstWhere('key', $type);
        if (!$widget || $widget['type'] !== 'table') {
            return response()->json(['message' => 'Daftar tidak ditemukan'], 404);
        }
        if (!userCan($widget['slug'], 'can_update')) {
            return response()->json(['message' => 'Anda tidak memiliki akses ke widget ini'], 403);
        }

        // Daftar SO / SQ / WO / FWO memakai query dasar YANG SAMA dengan halaman index modulnya
        // (listBaseQuery), jadi kolomnya selalu sama dengan index. Widget hanya menambah filter + urutan.
        if ($type === 'wo') {
            $rows = WorkOrderController::listBaseQuery()
                ->whereNull('wo.deleted_at')
                ->where('wo.status', 'onprogress')
                ->orderByDesc('wo.created_at')
                ->get();

        } elseif ($type === 'fwo') {
            $rows = FieldworkController::listBaseQuery()
                ->whereNull('fw.deleted_at')
                ->where('fw.status', '!=', 'completed') // Planned + On Progress
                ->orderByDesc('fw.created_at')
                ->get();
            $rows = $this->fwoRowsWithHasil($rows);

        } elseif ($type === 'fwo-belum-jadwal') {
            // FWO tanpa tanggal mulai; yang terlama dibuat di atas. Status ditampilkan sebagai "Belum Dijadwalkan".
            $rows = $this->fwoBelumJadwalQuery(FieldworkController::listBaseQuery())
                ->orderBy('fw.created_at')
                ->get();
            $rows = $this->fwoRowsWithHasil($rows)->each(fn ($r) => $r->status = 'belum_jadwal');

        } elseif ($type === 'sq') {
            // SQ Win terbaru yang belum diterbitkan jadi SO; yang paling dekat masa berlakunya di atas
            $rows = SalesQuotationController::listBaseQuery()
                ->whereNull('s.deleted_at')
                ->where('s.is_latest', true)
                ->where('s.status', 'win')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('sales_orders as so')
                        ->whereColumn('so.id_sq', 's.id_sq')->whereNull('so.deleted_at');
                })
                ->orderByRaw('s.berlaku_sampai IS NULL, s.berlaku_sampai ASC')
                ->orderByDesc('s.created_at')
                ->get();

        } elseif ($type === 'sq-approval') {
            // SQ menunggu approval; yang paling lama menunggu di atas. Kolom index SQ + 2 kolom khusus approval di ujung.
            $rows = SalesQuotationController::listBaseQuery()
                ->leftJoin('users as u', 'u.id', '=', 's.diajukan_oleh')
                ->addSelect([
                    'u.name as diajukan_oleh',
                    DB::raw("DATE_FORMAT(s.diajukan_at, '%Y-%m-%d %H:%i') as diajukan_at"),
                ])
                ->whereNull('s.deleted_at')
                ->where('s.is_latest', true)
                ->where('s.status', 'pending')
                ->orderBy('s.diajukan_at')
                ->get();

        } elseif ($type === 'sq-belum-budget-fwo') {
            // Kolom = index SQ; yang paling lama dibuat di atas (paling lama belum dibudget)
            // 1 baris = 1 SQ FWO belum dibudget (kolom index SQ + "SQ FWO" yang langsung link ke halaman SQ FWO)
            $rows = $this->sqBelumBudgetFwoFilter(SalesQuotationController::listBaseQuery())
                ->addSelect(['sf.id_sq_fwo', 'sf.no_sq_fwo as sq_fwo', 'sf.judul_pekerjaan as judul_sq_fwo'])
                ->orderBy('s.created_at')->orderBy('swo.id_sq_wo')->orderBy('sf.urutan')->orderBy('sf.id_sq_fwo')
                ->get()
                // Urutan kolom di tabel = urutan kunci: taruh "SQ FWO" tepat setelah "No SQ"
                ->map(function ($r) {
                    $out = [];
                    foreach ((array) $r as $k => $v) {
                        if (in_array($k, ['sq_fwo', 'judul_sq_fwo'], true)) continue;
                        $out[$k] = $v;
                        if ($k === 'no_sq') { $out['sq_fwo'] = $r->sq_fwo; $out['judul_sq_fwo'] = $r->judul_sq_fwo; }
                    }
                    return (object) $out;
                });

        } elseif ($type === 'so') {
            // Status SO yang benar: on-progress | completed | cancel (sebelumnya salah memakai 'selesai')
            $rows = SalesOrderController::listBaseQuery()
                ->whereNull('s.deleted_at')
                ->whereNotIn('s.status', ['completed', 'cancel'])
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
}
