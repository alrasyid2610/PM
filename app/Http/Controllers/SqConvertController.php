<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Sales Quotation — Fase 5: Convert SQ → SO.
 *
 * 1 SQ hanya boleh diterbitkan jadi SO 1 kali (UI: "Terbitkan SO") (penanda: sales_orders.id_sq, kolom lama
 * yang sebelumnya tidak dipakai). Yang ikut terbawa: header (Pemesan/
 * Delivery/Payment/PIC/Office/discount), WO, BOQ (+items), BOQ Other/
 * Sampling, Budget Plan WO, dan sejak 2026-09-27 juga FWO + Fieldwork BOQ
 * (+items) + Budget Plan FWO (Plan + Item — SQ tidak pernah punya Actual,
 * baik di level WO maupun FWO). Tidak dibawa: Personel FWO, BOQ
 * Other/Sampling FWO, Attachment, Sample (tabel `sq_*` untuk itu tidak ada).
 *
 * "Hari ke-N" SQ diubah jadi tanggal nyata dari 1 Tanggal Mulai yang diisi
 * user di modal: tanggal WO = mulai + (hari_mulai − 1), selesai = mulai +
 * (durasi − 1). FWO ikut terbawa sejak 2026-09-27 (setelah Fase 3 SQ
 * Fieldwork ada): hari_ke FWO dihitung dari basis hari yang SAMA dengan
 * hari_mulai WO (lihat SqFieldworkController — hari_ke divalidasi terhadap
 * rentang hari WO induknya), jadi tanggalnya dihitung dari $start yang sama.
 * hanya SQ berstatus Final yang boleh diterbitkan (kesepakatan 2026-09-27); setelah sukses status SQ otomatis jadi 'completed'.
 */
class SqConvertController extends Controller
{
    /** Field SO wajib (soRequiredRules) yang harus sudah terisi di SQ. */
    private const SQ_REQUIRED = [
        'judul_order' => 'Judul Order',
        'id_pelanggan' => 'Perusahaan Pemesan',
        'id_site_pelanggan' => 'Site Pemesan',
        'id_pic_pelanggan' => 'PIC Pemesan',
        'id_pelanggan_delivery' => 'Perusahaan Pengiriman',
        'id_site_pelanggan_delivery' => 'Site Pengiriman',
        'id_pic_pelanggan_delivery' => 'PIC Pengiriman',
        'id_pelanggan_payment' => 'Perusahaan Pembayaran',
        'id_site_pelanggan_payment' => 'Site Pembayaran',
        'id_pic_pelanggan_payment' => 'PIC Pembayaran',
        'pic_input' => 'PIC Input',
        'pic_marketing_internal' => 'Marketing Internal',
    ];

    public function preview($id)
    {
        $sq = DB::table('sales_quotations')->where('id_sq', $id)->whereNull('deleted_at')->first();
        if (!$sq) return response()->json(['message' => 'Sales Quotation tidak ditemukan'], 404);

        $so = DB::table('sales_orders')->where('id_sq', $id)->whereNull('deleted_at')->first();

        $missing = [];
        foreach (self::SQ_REQUIRED as $col => $label) {
            if (empty($sq->{$col})) $missing[] = $label;
        }

        $wos = DB::table('sq_work_orders as w')
            ->leftJoin('business_relation_sites as brs', 'brs.id_site', '=', 'w.id_site_pelanggan_pekerjaan')
            ->where('w.id_sq', $id)
            ->orderBy('w.urutan')->orderBy('w.id_sq_wo')
            ->select(['w.id_sq_wo', 'w.no_sq_wo', 'w.judul_pekerjaan', 'w.hari_mulai', 'w.durasi_hari',
                'w.interval_bulan', 'w.no_urut_period', 'brs.nama_lokasi as nama_site'])
            ->get();
        $woIds = $wos->pluck('id_sq_wo');

        $boq = DB::table('sq_boq')->whereIn('id_sq_wo', $woIds)->selectRaw('id_sq_wo, COUNT(*) c')->groupBy('id_sq_wo')->pluck('c', 'id_sq_wo');
        $tam = DB::table('sq_boq_tambahan')->whereIn('id_sq_wo', $woIds)->selectRaw('id_sq_wo, COUNT(*) c')->groupBy('id_sq_wo')->pluck('c', 'id_sq_wo');
        $bud = DB::table('sq_wo_budgets')->whereIn('id_sq_wo', $woIds)->selectRaw('id_sq_wo, COUNT(*) c')->groupBy('id_sq_wo')->pluck('c', 'id_sq_wo');
        $fwo = DB::table('sq_fieldworks')->whereIn('id_sq_wo', $woIds)->selectRaw('id_sq_wo, COUNT(*) c')->groupBy('id_sq_wo')->pluck('c', 'id_sq_wo');

        $wos->transform(function ($w) use ($boq, $tam, $bud, $fwo) {
            $w->boq_count = (int) ($boq[$w->id_sq_wo] ?? 0);
            $w->tambahan_count = (int) ($tam[$w->id_sq_wo] ?? 0);
            $w->budget_count = (int) ($bud[$w->id_sq_wo] ?? 0);
            $w->fwo_count = (int) ($fwo[$w->id_sq_wo] ?? 0);
            return $w;
        });

        return response()->json([
            'no_sq' => $sq->no_sq,
            'revisi' => $sq->revisi,
            'judul_order' => $sq->judul_order,
            'rencana_mulai' => $sq->rencana_mulai,
            'pic_marketing_eksternal' => $sq->pic_marketing_eksternal,
            'status' => $sq->status,
            'missing' => $missing,
            'sudah_convert' => $so ? ['id_so' => $so->id_so, 'no_so' => $so->no_so] : null,
            'wos' => $wos,
        ]);
    }

    public function convert(Request $request, $id)
    {
        if (!userCan('sales-orders', 'can_create')) {
            return response()->json(['message' => 'Anda tidak punya izin membuat Sales Order.'], 403);
        }

        $input = $request->validate([
            'tanggal_so' => 'required|date',
            'tanggal_mulai' => 'required|date',
            'pic_order' => 'required|integer',
            'tidak_ada_po' => 'nullable|boolean',
            'no_po' => 'nullable|string|max:50',
            'tanggal_po' => 'nullable|date',
        ], [
            'tanggal_so.required' => 'Tanggal SO wajib diisi.',
            'tanggal_mulai.required' => 'Tanggal Mulai wajib diisi.',
            'pic_order.required' => 'PIC Order wajib dipilih.',
        ]);

        $sq = DB::table('sales_quotations')->where('id_sq', $id)->whereNull('deleted_at')->first();
        if (!$sq) return response()->json(['message' => 'Sales Quotation tidak ditemukan'], 404);

        if ($sq->status !== 'final') {
            return response()->json(['message' => 'Hanya SQ berstatus Final yang bisa diterbitkan menjadi SO. Finalkan SQ dulu.'], 422);
        }

        foreach (self::SQ_REQUIRED as $col => $label) {
            if (empty($sq->{$col})) {
                return response()->json(['message' => "Lengkapi dulu \"{$label}\" di Sales Quotation sebelum menerbitkan SO."], 422);
            }
        }

        try {
            $result = DB::transaction(function () use ($id, $sq, $input) {
                // Kunci baris SQ supaya 2 klik bersamaan tidak menghasilkan 2 SO.
                $locked = DB::table('sales_quotations')->where('id_sq', $id)->lockForUpdate()->first();
                if ($locked->status !== 'final') {
                    throw new \RuntimeException('Hanya SQ berstatus Final yang bisa diterbitkan menjadi SO.');
                }
                if (DB::table('sales_orders')->where('id_sq', $id)->whereNull('deleted_at')->exists()) {
                    throw new \RuntimeException('Sales Quotation ini sudah pernah diterbitkan menjadi Sales Order.');
                }

                $start = Carbon::parse($input['tanggal_mulai'])->startOfDay();
                $wos = DB::table('sq_work_orders')->where('id_sq', $id)->orderBy('urutan')->orderBy('id_sq_wo')->get();

                // Tanggal per WO dihitung dulu untuk menentukan Tanggal Selesai SO.
                $dates = [];
                $soEnd = $start->copy();
                foreach ($wos as $wo) {
                    $mulai = $start->copy()->addDays(max(0, (int) $wo->hari_mulai - 1));
                    $selesai = $mulai->copy()->addDays(max(0, (int) ($wo->durasi_hari ?: 1) - 1));
                    $dates[$wo->id_sq_wo] = [$mulai, $selesai];
                    if ($selesai->gt($soEnd)) $soEnd = $selesai->copy();
                }

                $idSo = DB::table('sales_orders')->insertGetId([
                    'id_sq' => $id,
                    'id_sc' => null,
                    'no_so' => $this->generateSoNumber(),
                    'tanggal_so' => $input['tanggal_so'],
                    'judul_order' => $sq->judul_order,
                    'tidak_ada_po' => !empty($input['tidak_ada_po']) ? 1 : 0,
                    'no_po' => !empty($input['tidak_ada_po']) ? null : ($input['no_po'] ?? null),
                    'tanggal_po' => !empty($input['tidak_ada_po']) ? null : ($input['tanggal_po'] ?? null),
                    'tanggal_mulai' => $start->toDateString(),
                    'tanggal_selesai' => $soEnd->toDateString(),
                    'id_office' => $sq->id_office,
                    'id_pelanggan' => $sq->id_pelanggan,
                    'id_site_pelanggan' => $sq->id_site_pelanggan,
                    'id_pic_pelanggan' => $sq->id_pic_pelanggan,
                    'id_pelanggan_delivery' => $sq->id_pelanggan_delivery,
                    'id_site_pelanggan_delivery' => $sq->id_site_pelanggan_delivery,
                    'id_pic_pelanggan_delivery' => $sq->id_pic_pelanggan_delivery,
                    'id_pelanggan_payment' => $sq->id_pelanggan_payment,
                    'id_site_pelanggan_payment' => $sq->id_site_pelanggan_payment,
                    'id_pic_pelanggan_payment' => $sq->id_pic_pelanggan_payment,
                    'pic_input' => $sq->pic_input,
                    'pic_order' => $input['pic_order'],
                    'pic_marketing_internal' => $sq->pic_marketing_internal,
                    'pic_marketing_eksternal' => $sq->pic_marketing_eksternal,
                    'discount' => (int) ($sq->discount ?? 0),
                    'status' => 'on-progress',
                    'cara_pembayaran' => $sq->cara_pembayaran,
                    'keterangan' => $sq->keterangan,
                    'attachment' => json_encode([]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $soAfter = DB::table('sales_orders')->where('id_so', $idSo)->get()->toJson();
                saveAudit('sales_orders', $idSo, 'Create', '', $soAfter);

                $totalWo = 0;
                foreach ($wos as $wo) {
                    [$mulai, $selesai] = $dates[$wo->id_sq_wo];

                    // work_orders.id_pelanggan/id_site_pelanggan_pekerjaan NOT NULL:
                    // pakai Site WO SQ (Perusahaan diturunkan dari Site), kalau
                    // kosong jatuh ke Pemesan SQ.
                    $siteId = $wo->id_site_pelanggan_pekerjaan ?: $sq->id_site_pelanggan;
                    $brId = $wo->id_pelanggan_pekerjaan
                        ?: DB::table('business_relation_sites')->where('id_site', $siteId)->value('id_br')
                        ?: $sq->id_pelanggan;

                    $idWo = DB::table('work_orders')->insertGetId([
                        'no_wo' => $this->generateNoWo(),
                        'id_so' => $idSo,
                        'id_pelanggan_pekerjaan' => $brId,
                        'id_site_pelanggan_pekerjaan' => $siteId,
                        // PIC Pekerjaan WO SQ merujuk kontak pelanggan, sedangkan
                        // work_orders merujuk users — tidak bisa dipetakan, diisi
                        // manual di detail WO.
                        'id_pic_pelanggan_pekerjaan' => null,
                        'judul_pekerjaan' => $wo->judul_pekerjaan ?: ($sq->judul_order ?: 'Work Order'),
                        'interval_bulan' => $wo->interval_bulan,
                        'no_urut_period' => $wo->no_urut_period,
                        'keterangan' => $wo->keterangan,
                        'status' => 'onprogress',
                        'tanggal_mulai' => $mulai->toDateString(),
                        'tanggal_selesai' => $selesai->toDateString(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $totalWo++;

                    // id_sq_boq => id_boq baru — dipakai untuk remap Fieldwork
                    // BOQ (di bawah) ke BOQ WO hasil salinan yang benar.
                    $boqIdBySqBoq = [];

                    foreach (DB::table('sq_boq')->where('id_sq_wo', $wo->id_sq_wo)->get() as $b) {
                        $idBoq = DB::table('boq')->insertGetId([
                            'id_wo' => $idWo,
                            'id_testing_point' => $b->id_testing_point,
                            'item_produk_alternate' => $b->item_produk_alternate,
                            'qty' => $b->qty,
                            'id_satuan' => $b->id_satuan,
                            'harga' => $b->harga,
                            'discount' => min((int) $b->discount, (int) $b->qty * (int) $b->harga),
                            'keterangan' => $b->keterangan,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $boqIdBySqBoq[$b->id_sq_boq] = $idBoq;

                        $items = DB::table('sq_boq_items')->where('id_sq_boq', $b->id_sq_boq)->pluck('id_testing_item');
                        if ($items->isNotEmpty()) {
                            DB::table('boq_items')->insert($items->map(fn($i) => [
                                'id_boq' => $idBoq, 'id_testing_item' => $i,
                                'created_at' => now(), 'updated_at' => now(),
                            ])->all());
                        }
                    }

                    foreach (DB::table('sq_boq_tambahan')->where('id_sq_wo', $wo->id_sq_wo)->get() as $t) {
                        DB::table('boq_tambahan')->insert([
                            'id_wo' => $idWo,
                            'jenis' => $t->jenis,
                            'nama_item' => $t->nama_item,
                            'qty' => $t->qty,
                            'id_satuan' => $t->id_satuan,
                            'harga' => $t->harga,
                            'keterangan' => $t->keterangan,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    foreach (DB::table('sq_wo_budgets')->where('id_sq_wo', $wo->id_sq_wo)->get() as $bp) {
                        $idBudget = DB::table('wo_budgets')->insertGetId([
                            'id_wo' => $idWo,
                            'label' => $bp->label,
                            'keterangan' => $bp->keterangan,
                            'tanggal_mulai' => $bp->hari_mulai ? $start->copy()->addDays($bp->hari_mulai - 1)->toDateString() : null,
                            'tanggal_selesai' => $bp->hari_selesai ? $start->copy()->addDays($bp->hari_selesai - 1)->toDateString() : null,
                            'status' => 'open',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        foreach (DB::table('sq_wo_budget_items')->where('id_sq_budget', $bp->id_sq_budget)->get() as $bi) {
                            DB::table('wo_budget_items')->insert([
                                'id_budget' => $idBudget,
                                'id_account' => $bi->id_account,
                                'nominal_budget' => $bi->nominal_budget,
                                'keterangan' => $bi->keterangan,
                                'is_cash_advance' => $bi->is_cash_advance,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }

                    // ── FWO + Fieldwork BOQ + Budget FWO ──
                    foreach (DB::table('sq_fieldworks')->where('id_sq_wo', $wo->id_sq_wo)->orderBy('urutan')->orderBy('id_sq_fwo')->get() as $fwo) {
                        $fwoMulai = $start->copy()->addDays(max(0, (int) $fwo->hari_ke - 1));
                        $fwoSelesai = $fwoMulai->copy()->addDays(max(0, (int) ($fwo->durasi_hari ?: 1) - 1));

                        $idFwo = DB::table('fieldworks')->insertGetId([
                            'id_wo' => $idWo,
                            'no_fwo' => $this->generateNoFwo(),
                            'judul_pekerjaan' => $fwo->judul_pekerjaan ?: ($wo->judul_pekerjaan ?: 'Fieldwork'),
                            // id_site/id_pic_pelanggan_pekerjaan sq_fieldworks sudah
                            // merujuk business_relation_sites/contacts persis sama
                            // seperti fieldworks asli — tidak perlu remap.
                            'id_site_pelanggan_pekerjaan' => $fwo->id_site_pelanggan_pekerjaan,
                            'id_pic_pelanggan_pekerjaan' => $fwo->id_pic_pelanggan_pekerjaan,
                            'tanggal_mulai' => $fwoMulai->toDateString(),
                            'tanggal_selesai' => $fwoSelesai->toDateString(),
                            'status' => 'planned',
                            'keterangan' => $fwo->keterangan,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        foreach (DB::table('sq_fieldwork_boq')->where('id_sq_fwo', $fwo->id_sq_fwo)->get() as $fb) {
                            if (!isset($boqIdBySqBoq[$fb->id_sq_boq])) continue; // BOQ WO tidak ikut tersalin (harusnya tidak mungkin, tapi dijaga)
                            $idBoqBaru = $boqIdBySqBoq[$fb->id_sq_boq];
                            $idTestingPoint = DB::table('boq')->where('id_boq', $idBoqBaru)->value('id_testing_point');

                            $idFwoBoq = DB::table('fieldwork_boq')->insertGetId([
                                'id_fwo' => $idFwo,
                                'id_boq' => $idBoqBaru,
                                'id_testing_point' => $idTestingPoint,
                                'qty' => $fb->qty,
                                'keterangan' => $fb->keterangan,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            // Items selalu disinkron utuh dari boq_items BOQ baru —
                            // pola sama FieldworkBoqController::update().
                            $boqItemIds = DB::table('boq_items')->where('id_boq', $idBoqBaru)->pluck('id_testing_item');
                            if ($boqItemIds->isNotEmpty()) {
                                DB::table('fieldwork_boq_items')->insert($boqItemIds->map(fn($i) => [
                                    'id_fwo_boq' => $idFwoBoq, 'id_testing_item' => $i,
                                    'created_at' => now(), 'updated_at' => now(),
                                ])->all());
                            }
                        }

                        foreach (DB::table('sq_fwo_budgets')->where('id_sq_fwo', $fwo->id_sq_fwo)->get() as $fbp) {
                            $idFwoBudget = DB::table('fwo_budgets')->insertGetId([
                                'id_fwo' => $idFwo,
                                'label' => $fbp->label,
                                'keterangan' => $fbp->keterangan,
                                'tanggal_mulai' => $fbp->hari_mulai ? $start->copy()->addDays($fbp->hari_mulai - 1)->toDateString() : null,
                                'tanggal_selesai' => $fbp->hari_selesai ? $start->copy()->addDays($fbp->hari_selesai - 1)->toDateString() : null,
                                'status' => 'open',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            foreach (DB::table('sq_fwo_budget_items')->where('id_sq_budget', $fbp->id_sq_budget)->get() as $fbi) {
                                DB::table('fwo_budget_items')->insert([
                                    'id_budget' => $idFwoBudget,
                                    'id_account' => $fbi->id_account,
                                    'nominal_budget' => $fbi->nominal_budget,
                                    'keterangan' => $fbi->keterangan,
                                    'is_cash_advance' => $fbi->is_cash_advance,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    }
                }

                $sqBefore = DB::table('sales_quotations')->where('id_sq', $id)->get()->toJson();
                DB::table('sales_quotations')->where('id_sq', $id)->update([
                    'status' => 'completed',
                    'diputuskan_at' => now(),
                    'updated_at' => now(),
                ]);
                saveAudit('sales_quotations', $id, 'update', $sqBefore, DB::table('sales_quotations')->where('id_sq', $id)->get()->toJson());

                return ['id_so' => $idSo, 'no_so' => DB::table('sales_orders')->where('id_so', $idSo)->value('no_so'), 'total_wo' => $totalWo];
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(array_merge(['success' => true, 'message' => "Berhasil diterbitkan menjadi {$result['no_so']} ({$result['total_wo']} WO)"], $result));
    }

    // Pola sama SalesOrderController::generateSoNumber() (private di sana).
    private function generateSoNumber(): string
    {
        $prefix = 'SO-' . now()->format('y') . '-';
        $last = DB::table('sales_orders')->where('no_so', 'like', $prefix . '%')->orderByDesc('id_so')->first();
        $n = $last ? ((int) substr($last->no_so, -4)) + 1 : 1;
        return $prefix . str_pad($n, 4, '0', STR_PAD_LEFT);
    }

    // Pola sama SalesOrderController::generateNoWo() — urut by id_wo, bukan created_at.
    private function generateNoWo(): string
    {
        $prefix = 'WO-' . now()->format('y') . '-';
        $latest = DB::table('work_orders')->where('no_wo', 'like', $prefix . '%')->orderByDesc('id_wo')->first();
        if (!$latest) return $prefix . '0001';
        return $prefix . str_pad(((int) explode('-', $latest->no_wo)[2]) + 1, 4, '0', STR_PAD_LEFT);
    }

    // Pola sama SalesOrderController::generateNoFwo() (format FWO.YY.A.NNNN, huruf naik tiap 9999).
    private function generateNoFwo(): string
    {
        $year = now()->format('y');
        $latest = DB::table('fieldworks')->where('no_fwo', 'like', "FWO.{$year}.%")->orderBy('no_fwo', 'desc')->value('no_fwo');
        if (!$latest) return "FWO.{$year}.A.0001";

        $parts = explode('.', $latest);
        $letter = $parts[2] ?? 'A';
        $number = intval($parts[3] ?? 0);
        if ($number < 9999) return sprintf('FWO.%s.%s.%04d', $year, $letter, $number + 1);
        return sprintf('FWO.%s.%s.0001', $year, chr(ord($letter) + 1));
    }
}
