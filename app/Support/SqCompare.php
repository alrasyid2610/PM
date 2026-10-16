<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Perbandingan dua Sales Quotation (mis. Rev.5 Win vs revisi lain).
 *
 * Konvensi: "sebelum" = SQ pembanding, "sesudah" = SQ yang sedang dibuka.
 * Pendapatan = BOQ bersih (setelah discount item) + BOQ Other + Sampling − discount SQ;
 * Biaya = Budget Plan WO + Budget Plan FWO (sama dengan Executive Summary).
 *
 * Tiap revisi punya id baru, jadi baris dicocokkan lewat identitas bisnis, bukan id:
 *  - WO      → judul pekerjaan + site (+ urutan kemunculan bila kembar)
 *  - BOQ     → testing point (atau item alternatif)
 *  - BOQ Other/Sampling → jenis + nama item
 *  - FWO     → judul pekerjaan
 *  - Budget  → sumber (WO / FWO) + akun
 *  - Keterangan → poin master
 */
class SqCompare
{
    public static function rp($v): string
    {
        $v = (int) $v;
        return ($v < 0 ? '-' : '') . 'Rp ' . number_format(abs($v), 0, ',', '.');
    }

    private static function tgl($v): string
    {
        return $v ? substr((string) $v, 0, 10) : '—';
    }

    private static function key(string $base, array &$seen): string
    {
        $base = mb_strtolower(trim($base));
        $seen[$base] = ($seen[$base] ?? 0) + 1;
        return $base . '#' . $seen[$base];
    }

    /** Foto isi SQ dalam bentuk terstruktur (siap dibandingkan). */
    public static function snapshot(int $id): ?array
    {
        $sq = DB::table('sales_quotations as sq')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'sq.id_pelanggan')
            ->leftJoin('entitas as ent', 'ent.id_entitas', '=', 'br.id_entitas')
            ->leftJoin('business_relation_sites as brs', 'brs.id_site', '=', 'sq.id_site_pelanggan')
            ->leftJoin('business_relation_contacts as brc', 'brc.id_contact', '=', 'sq.id_pic_pelanggan')
            ->where('sq.id_sq', $id)->whereNull('sq.deleted_at')
            ->select(['sq.*', 'br.nama as nama_pelanggan', 'ent.nama as entitas_pelanggan', 'brs.nama_lokasi as nama_site', 'brc.nama_pic as nama_pic'])
            ->first();
        if (!$sq) return null;

        $f = fn (string $l, $v, ?string $t = null) => ['l' => $l, 'v' => (string) $v, 't' => $t ?? ((string) $v === '' ? '—' : (string) $v)];
        $header = [
            'judul'     => ['label' => 'Judul Order', 'fields' => ['x' => $f('Judul Order', $sq->judul_order ?? '')]],
            'pelanggan' => ['label' => 'Pelanggan', 'fields' => ['x' => $f('Pelanggan', brDisplayName($sq->entitas_pelanggan, $sq->nama_pelanggan))]],
            'site'      => ['label' => 'Site Pelanggan', 'fields' => ['x' => $f('Site Pelanggan', $sq->nama_site ?? '')]],
            'pic'       => ['label' => 'PIC Pelanggan', 'fields' => ['x' => $f('PIC Pelanggan', $sq->nama_pic ?? '')]],
            'tanggal'   => ['label' => 'Tanggal SQ', 'fields' => ['x' => $f('Tanggal SQ', self::tgl($sq->tanggal_sq))]],
            'berlaku'   => ['label' => 'Berlaku Sampai', 'fields' => ['x' => $f('Berlaku Sampai', self::tgl($sq->berlaku_sampai))]],
            'mulai'     => ['label' => 'Rencana Mulai', 'fields' => ['x' => $f('Rencana Mulai', self::tgl($sq->rencana_mulai))]],
            'discount'  => ['label' => 'Discount SQ', 'fields' => ['x' => $f('Discount SQ', (int) $sq->discount, self::rp($sq->discount))]],
            'bayar'     => ['label' => 'Cara Pembayaran', 'fields' => ['x' => $f('Cara Pembayaran', $sq->cara_pembayaran ?? '')]],
            'ket'       => ['label' => 'Keterangan', 'fields' => ['x' => $f('Keterangan', $sq->keterangan ?? '')]],
        ];

        $wos = DB::table('sq_work_orders as w')
            ->leftJoin('business_relation_sites as brs', 'brs.id_site', '=', 'w.id_site_pelanggan_pekerjaan')
            ->where('w.id_sq', $id)->orderBy('w.urutan')->orderBy('w.id_sq_wo')
            ->select(['w.*', 'brs.nama_lokasi as nama_site'])->get();
        $woIds = $wos->pluck('id_sq_wo');

        $boq = $woIds->isEmpty() ? collect() : DB::table('sq_boq as b')
            ->leftJoin('testing_points as tp', 'tp.id_testing_point', '=', 'b.id_testing_point')
            ->leftJoin('testing_matriks_samples as tms', 'tms.id_testing_matriks_sample', '=', 'tp.id_testing_matriks_sample')
            ->leftJoin('testing_standards as ts', 'ts.id_testing_standard', '=', 'tp.id_testing_standard')
            ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 'b.id_satuan')
            ->whereIn('b.id_sq_wo', $woIds)->orderBy('b.id_sq_boq')
            ->select(['b.*', 'tp.nama as tp_nama', 'ts.nomor as std_nomor', 'tms.judul_indonesia as matriks', 'sat.nama as satuan'])
            ->get()->groupBy('id_sq_wo');
        $tam = $woIds->isEmpty() ? collect() : DB::table('sq_boq_tambahan as t')
            ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 't.id_satuan')
            ->whereIn('t.id_sq_wo', $woIds)->orderBy('t.id_sq_boq_tambahan')
            ->select(['t.*', 'sat.nama as satuan'])->get()->groupBy('id_sq_wo');
        $fwos = $woIds->isEmpty() ? collect() : DB::table('sq_fieldworks')
            ->whereIn('id_sq_wo', $woIds)->orderBy('urutan')->orderBy('id_sq_fwo')->get()->groupBy('id_sq_wo');
        $fwoBoqCount = DB::table('sq_fieldwork_boq')->whereIn('id_sq_fwo', $fwos->flatten(1)->pluck('id_sq_fwo'))
            ->selectRaw('id_sq_fwo, COUNT(*) c')->groupBy('id_sq_fwo')->pluck('c', 'id_sq_fwo');

        $budWo = $woIds->isEmpty() ? collect() : DB::table('sq_wo_budgets as b')
            ->join('sq_wo_budget_items as i', 'i.id_sq_budget', '=', 'b.id_sq_budget')
            ->leftJoin('budget_accounts as a', 'a.id_account', '=', 'i.id_account')
            ->whereIn('b.id_sq_wo', $woIds)
            ->selectRaw('b.id_sq_wo, i.id_account, a.kode, a.nama, SUM(i.nominal_budget) as nominal')
            ->groupBy('b.id_sq_wo', 'i.id_account', 'a.kode', 'a.nama')->get()->groupBy('id_sq_wo');
        $budFwo = $fwos->isEmpty() ? collect() : DB::table('sq_fwo_budgets as b')
            ->join('sq_fwo_budget_items as i', 'i.id_sq_budget', '=', 'b.id_sq_budget')
            ->leftJoin('budget_accounts as a', 'a.id_account', '=', 'i.id_account')
            ->whereIn('b.id_sq_fwo', $fwos->flatten(1)->pluck('id_sq_fwo'))
            ->selectRaw('b.id_sq_fwo, i.id_account, a.kode, a.nama, SUM(i.nominal_budget) as nominal')
            ->groupBy('b.id_sq_fwo', 'i.id_account', 'a.kode', 'a.nama')->get()->groupBy('id_sq_fwo');
        $akun = fn ($r) => $r->nama ? trim(($r->kode ? $r->kode . ' — ' : '') . $r->nama) : 'Tanpa akun';

        $woOut = [];
        $seenWo = [];
        $pendapatanBruto = 0;
        $biaya = 0;
        foreach ($wos as $wo) {
            $seen = [];
            $items = ['boq' => [], 'tambahan' => [], 'fwo' => [], 'budget' => []];
            $pend = 0;
            $bi = 0;

            foreach ($boq->get($wo->id_sq_wo, collect()) as $b) {
                $net = max(0, (int) $b->qty * (int) $b->harga - (int) $b->discount);
                $pend += $net;
                $base = $b->id_testing_point ? 'tp' . $b->id_testing_point : 'alt:' . ($b->item_produk_alternate ?? $b->id_sq_boq);
                $items['boq'][self::key($base, $seen)] = [
                    'label' => $b->matriks ?: ($b->item_produk_alternate ?: 'Item BOQ'),
                    'sub'   => trim(implode(' ', array_filter([$b->std_nomor, $b->tp_nama]))) ?: null,
                    'value' => $net,
                    'fields' => [
                        'qty'      => $f('Qty', (int) $b->qty, (int) $b->qty . ($b->satuan ? ' ' . $b->satuan : '')),
                        'harga'    => $f('Harga', (int) $b->harga, self::rp($b->harga)),
                        'discount' => $f('Discount', (int) $b->discount, self::rp($b->discount)),
                    ],
                ];
            }

            $seen = [];
            foreach ($tam->get($wo->id_sq_wo, collect()) as $t) {
                $val = (int) $t->qty * (int) $t->harga;
                $pend += $val;
                $items['tambahan'][self::key($t->jenis . '|' . $t->nama_item, $seen)] = [
                    'label' => $t->nama_item,
                    'sub'   => $t->jenis === 'sampling' ? 'BOQ Sampling' : 'BOQ Other',
                    'value' => $val,
                    'fields' => [
                        'qty'   => $f('Qty', (int) $t->qty, (int) $t->qty . ($t->satuan ? ' ' . $t->satuan : '')),
                        'harga' => $f('Harga', (int) $t->harga, self::rp($t->harga)),
                    ],
                ];
            }

            $seen = [];
            $seenAkun = [];
            foreach ($fwos->get($wo->id_sq_wo, collect()) as $fw) {
                $items['fwo'][self::key($fw->judul_pekerjaan ?? 'FWO', $seen)] = [
                    'label' => $fw->judul_pekerjaan ?: $fw->no_sq_fwo,
                    'sub'   => 'FWO',
                    'value' => null,
                    'fields' => [
                        'hari'   => $f('Hari ke', (int) $fw->hari_ke),
                        'durasi' => $f('Durasi', (int) $fw->durasi_hari, (int) $fw->durasi_hari . ' hari'),
                        'boq'    => $f('Jumlah BOQ FWO', (int) ($fwoBoqCount[$fw->id_sq_fwo] ?? 0)),
                    ],
                ];
                foreach ($budFwo->get($fw->id_sq_fwo, collect()) as $r) {
                    $bi += (int) $r->nominal;
                    $items['budget'][self::key('fwo:' . mb_strtolower($fw->judul_pekerjaan ?? '') . '|' . ($r->id_account ?? 0), $seenAkun)] = [
                        'label' => $akun($r), 'sub' => 'Budget FWO: ' . ($fw->judul_pekerjaan ?: $fw->no_sq_fwo),
                        'value' => (int) $r->nominal,
                        'fields' => ['nominal' => $f('Nominal', (int) $r->nominal, self::rp($r->nominal))],
                    ];
                }
            }
            foreach ($budWo->get($wo->id_sq_wo, collect()) as $r) {
                $bi += (int) $r->nominal;
                $items['budget'][self::key('wo|' . ($r->id_account ?? 0), $seenAkun)] = [
                    'label' => $akun($r), 'sub' => 'Budget WO',
                    'value' => (int) $r->nominal,
                    'fields' => ['nominal' => $f('Nominal', (int) $r->nominal, self::rp($r->nominal))],
                ];
            }

            $pendapatanBruto += $pend;
            $biaya += $bi;
            $woOut[self::key(($wo->judul_pekerjaan ?? '') . '|' . ($wo->id_site_pelanggan_pekerjaan ?? 0), $seenWo)] = [
                'label'     => $wo->judul_pekerjaan ?: $wo->no_sq_wo,
                'sub'       => $wo->nama_site ?: null,
                'meta'      => [
                    'hari'   => $f('Hari mulai', (int) $wo->hari_mulai),
                    'durasi' => $f('Durasi', (int) $wo->durasi_hari, (int) $wo->durasi_hari . ' hari'),
                ],
                'items'     => $items,
                'pendapatan' => $pend,
                'biaya'      => $bi,
            ];
        }

        $keterangan = [];
        foreach (SqKeterangan::rows($id) as $r) {
            $keterangan['poin' . $r->id_poin] = [
                'label' => $r->kategori_label,
                'sub'   => null,
                'value' => null,
                'fields' => ['teks' => $f('Isi', trim(preg_replace('/\s+/', ' ', (string) $r->teks)))],
            ];
        }

        $discount = (int) $sq->discount;
        $pendapatan = max(0, $pendapatanBruto - $discount);

        return [
            'meta' => [
                'id_sq' => (int) $sq->id_sq, 'no_sq' => $sq->no_sq, 'revisi' => (int) $sq->revisi,
                'status' => $sq->status, 'judul' => $sq->judul_order,
            ],
            'pendapatan' => $pendapatan,
            'biaya'      => $biaya,
            'header'     => $header,
            'wos'        => $woOut,
            'keterangan' => $keterangan,
        ];
    }

    /** Bandingkan dua kumpulan item (key => item). Hasil: baris berurut, yang sama di paling bawah. */
    private static function diffItems(array $sebelum, array $sesudah): array
    {
        $rows = [];
        foreach (array_unique(array_merge(array_keys($sebelum), array_keys($sesudah))) as $k) {
            $b = $sebelum[$k] ?? null;
            $a = $sesudah[$k] ?? null;
            $ref = $a ?? $b;

            $changes = [];
            if ($a && $b) {
                foreach ($a['fields'] as $fk => $fa) {
                    $fb = $b['fields'][$fk] ?? null;
                    if (($fb['v'] ?? null) !== $fa['v']) {
                        $changes[] = ['label' => $fa['l'], 'dari' => $fb['t'] ?? '—', 'ke' => $fa['t']];
                    }
                }
            }

            $rows[] = [
                'label'   => $ref['label'],
                'sub'     => $ref['sub'] ?? null,
                'status'  => !$b ? 'baru' : (!$a ? 'dihapus' : ($changes ? 'berubah' : 'sama')),
                'changes' => $changes,
                'ringkas' => (!$a || !$b) ? implode(' · ', array_map(fn ($x) => $x['l'] . ' ' . $x['t'], $ref['fields'])) : null,
                'dari'    => $b['value'] ?? null,
                'ke'      => $a['value'] ?? null,
                'delta'   => (int) ($a['value'] ?? 0) - (int) ($b['value'] ?? 0),
            ];
        }

        usort($rows, function ($x, $y) {
            $sx = $x['status'] === 'sama' ? 1 : 0;
            $sy = $y['status'] === 'sama' ? 1 : 0;
            return [$sx, -abs($x['delta']), $x['label']] <=> [$sy, -abs($y['delta']), $y['label']];
        });
        return $rows;
    }

    /** Bandingkan SQ $idIni (sesudah) dengan $idPembanding (sebelum). */
    public static function compare(int $idIni, int $idPembanding): ?array
    {
        $a = self::snapshot($idIni);
        $b = self::snapshot($idPembanding);
        if (!$a || !$b) return null;

        $header = self::diffItems($b['header'], $a['header']);
        $keterangan = self::diffItems($b['keterangan'], $a['keterangan']);

        $wos = [];
        $keys = array_unique(array_merge(array_keys($b['wos']), array_keys($a['wos'])));
        foreach ($keys as $k) {
            $wb = $b['wos'][$k] ?? null;
            $wa = $a['wos'][$k] ?? null;
            $ref = $wa ?? $wb;

            $sections = [];
            foreach (['boq', 'tambahan', 'fwo', 'budget'] as $sec) {
                $sections[$sec] = self::diffItems($wb['items'][$sec] ?? [], $wa['items'][$sec] ?? []);
            }
            $metaRows = ($wa && $wb) ? self::diffItems(['m' => ['label' => 'Jadwal', 'fields' => $wb['meta']]], ['m' => ['label' => 'Jadwal', 'fields' => $wa['meta']]]) : [];
            $metaChanges = $metaRows ? $metaRows[0]['changes'] : [];

            $adaBeda = $metaChanges !== [];
            foreach ($sections as $rows) foreach ($rows as $r) if ($r['status'] !== 'sama') $adaBeda = true;

            $wos[] = [
                'label'      => $ref['label'],
                'sub'        => $ref['sub'],
                'status'     => !$wb ? 'baru' : (!$wa ? 'dihapus' : ($adaBeda ? 'berubah' : 'sama')),
                'meta'       => $metaChanges,
                'sections'   => $sections,
                'd_pendapatan' => (int) ($wa['pendapatan'] ?? 0) - (int) ($wb['pendapatan'] ?? 0),
                'd_biaya'      => (int) ($wa['biaya'] ?? 0) - (int) ($wb['biaya'] ?? 0),
            ];
        }
        usort($wos, function ($x, $y) {
            $sx = $x['status'] === 'sama' ? 1 : 0;
            $sy = $y['status'] === 'sama' ? 1 : 0;
            return [$sx, -abs($x['d_pendapatan']) - abs($x['d_biaya'])] <=> [$sy, -abs($y['d_pendapatan']) - abs($y['d_biaya'])];
        });

        // Hitung jumlah perubahan (baris daun)
        $hitung = ['berubah' => 0, 'baru' => 0, 'dihapus' => 0];
        $tambah = function (array $rows) use (&$hitung) {
            foreach ($rows as $r) if (isset($hitung[$r['status']])) $hitung[$r['status']]++;
        };
        $tambah($header);
        $tambah($keterangan);
        foreach ($wos as $w) {
            if ($w['status'] === 'baru' || $w['status'] === 'dihapus') $hitung[$w['status']]++;
            if ($w['meta']) $hitung['berubah']++;
            foreach ($w['sections'] as $rows) $tambah($rows);
        }

        $stat = function (int $dari, int $ke) {
            $d = $ke - $dari;
            return ['dari' => $dari, 'ke' => $ke, 'delta' => $d, 'pct' => $dari != 0 ? round($d / abs($dari) * 100, 1) : null];
        };
        $marginB = $b['pendapatan'] - $b['biaya'];
        $marginA = $a['pendapatan'] - $a['biaya'];
        $ringkasan = [
            'pendapatan' => $stat($b['pendapatan'], $a['pendapatan']),
            'biaya'      => $stat($b['biaya'], $a['biaya']),
            'margin'     => $stat($marginB, $marginA),
            'margin_pct' => [
                'dari' => $b['pendapatan'] > 0 ? round($marginB / $b['pendapatan'] * 100, 1) : null,
                'ke'   => $a['pendapatan'] > 0 ? round($marginA / $a['pendapatan'] * 100, 1) : null,
            ],
            'jumlah' => $hitung,
        ];

        return [
            'ini'        => $a['meta'],
            'pembanding' => $b['meta'],
            'ringkasan'  => $ringkasan,
            'narasi'     => self::narasi($ringkasan, $wos, $hitung),
            'header'     => $header,
            'wos'        => $wos,
            'keterangan' => $keterangan,
        ];
    }

    private static function narasi(array $r, array $wos, array $hitung): string
    {
        if (array_sum($hitung) === 0) return 'Tidak ada perbedaan isi antara kedua SQ.';

        $kal = [];
        $dp = $r['pendapatan']['delta'];
        if ($dp !== 0) {
            $pct = $r['pendapatan']['pct'] !== null ? ' (' . ($dp > 0 ? '+' : '') . str_replace('.', ',', (string) $r['pendapatan']['pct']) . '%)' : '';
            $kal[] = 'Harga ' . ($dp > 0 ? 'naik ' : 'turun ') . self::rp(abs($dp)) . $pct;

            // Penyebab terbesar: baris BOQ / BOQ Other yang berubah paling jauh
            $penyebab = [];
            foreach ($wos as $w) {
                foreach (['boq', 'tambahan'] as $sec) {
                    foreach ($w['sections'][$sec] as $row) {
                        if ($row['status'] === 'sama' || $row['delta'] === 0) continue;
                        $penyebab[] = $row;
                    }
                }
            }
            usort($penyebab, fn ($x, $y) => abs($y['delta']) <=> abs($x['delta']));
            $top = array_slice($penyebab, 0, 3);
            if ($top) {
                $kal[count($kal) - 1] .= ', terutama karena ' . implode('; ', array_map(
                    fn ($x) => $x['label'] . ($x['status'] === 'baru' ? ' (baru, ' : ($x['status'] === 'dihapus' ? ' (dihapus, ' : ' (')) . ($x['delta'] > 0 ? '+' : '-') . self::rp(abs($x['delta'])) . ')',
                    $top
                ));
            }
        } else {
            $kal[] = 'Total harga tidak berubah';
        }

        $db = $r['biaya']['delta'];
        if ($db !== 0) {
            $kal[] = 'biaya budget ' . ($db > 0 ? 'naik ' : 'turun ') . self::rp(abs($db));
        }
        if ($r['margin_pct']['dari'] !== null && $r['margin_pct']['ke'] !== null && $r['margin_pct']['dari'] !== $r['margin_pct']['ke']) {
            $kal[] = 'margin ' . ($r['margin_pct']['ke'] > $r['margin_pct']['dari'] ? 'naik' : 'turun') . ' dari '
                . str_replace('.', ',', (string) $r['margin_pct']['dari']) . '% ke ' . str_replace('.', ',', (string) $r['margin_pct']['ke']) . '%';
        }

        return ucfirst(implode(', ', $kal)) . '.';
    }
}
