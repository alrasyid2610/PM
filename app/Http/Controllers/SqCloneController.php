<?php

namespace App\Http\Controllers;

use App\Services\NotificationDispatcher;
use App\Support\DocumentNumber;
use App\Support\NotificationRoles;
use App\Support\SqKeterangan;
use App\Support\SqLock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Clone SQ (SQ baru dari SQ yang ada) dan Clone SQ WO (salin 1 WO ke SQ-nya sendiri) —
 * mengikuti cara Clone SO: halaman penuh, tiap WO/FWO/baris bisa dicentang "Sertakan" dan diedit
 * sebelum disimpan, semuanya dalam 1 transaksi.
 *
 * Beda dari Revisi SQ: Clone = SQ BARU (nomor baru, Rev.0, tidak tertaut ke SQ sumber), header boleh
 * diubah bebas (termasuk pelanggan). Revisi = salinan persis dengan nomor sama dan Rev. naik.
 *
 * Prinsip keamanan sama dengan Clone SO: identitas referensial (Testing Point BOQ, Testing Item, Perusahaan WO,
 * isi Budget Item) diambil ulang dari DB berdasarkan `source_id_*`, bukan dipercaya dari payload.
 * Setiap field yang dipakai insert WAJIB punya rule di validate() — field tanpa rule dibuang diam-diam.
 */
class SqCloneController extends Controller
{
    // ── Halaman ──────────────────────────────────────────────────────────────

    public function clonePage($id)
    {
        $sq = DB::table('sales_quotations')->where('id_sq', $id)->whereNull('deleted_at')->first();
        if (!$sq) abort(404, 'Sales Quotation tidak ditemukan');

        return view('sales-quotation.clone', ['id' => $id, 'woOnly' => false]);
    }

    public function woClonePage($idWo)
    {
        $wo = DB::table('sq_work_orders')->where('id_sq_wo', $idWo)->first();
        if (!$wo) abort(404, 'SQ Work Order tidak ditemukan');

        return view('sales-quotation.clone', ['id' => $wo->id_sq, 'woId' => $idWo, 'woOnly' => true]);
    }

    // ── Data sumber ──────────────────────────────────────────────────────────

    public function woCloneData($idWo)
    {
        $wo = DB::table('sq_work_orders')->where('id_sq_wo', $idWo)->first();
        if (!$wo) return response()->json(['message' => 'SQ Work Order tidak ditemukan'], 404);

        return $this->cloneData(request()->merge(['wo' => $idWo]), $wo->id_sq);
    }

    public function cloneData(Request $request, $id)
    {
        $onlyWoId = $request->input('wo');

        $sq = DB::table('sales_quotations as sq')
            ->leftJoin('business_relations as pelanggan', 'sq.id_pelanggan', '=', 'pelanggan.id_br')
            ->leftJoin('entitas as ent', 'ent.id_entitas', '=', 'pelanggan.id_entitas')
            ->leftJoin('business_relation_sites as site_pelanggan', 'sq.id_site_pelanggan', '=', 'site_pelanggan.id_site')
            ->leftJoin('business_relation_contacts as brc', 'brc.id_contact', '=', 'sq.id_pic_pelanggan')
            ->leftJoin('business_relations as del', 'sq.id_pelanggan_delivery', '=', 'del.id_br')
            ->leftJoin('entitas as ent_del', 'ent_del.id_entitas', '=', 'del.id_entitas')
            ->leftJoin('business_relation_sites as site_del', 'sq.id_site_pelanggan_delivery', '=', 'site_del.id_site')
            ->leftJoin('business_relation_contacts as brc_del', 'brc_del.id_contact', '=', 'sq.id_pic_pelanggan_delivery')
            ->leftJoin('business_relations as pay', 'sq.id_pelanggan_payment', '=', 'pay.id_br')
            ->leftJoin('entitas as ent_pay', 'ent_pay.id_entitas', '=', 'pay.id_entitas')
            ->leftJoin('business_relation_sites as site_pay', 'sq.id_site_pelanggan_payment', '=', 'site_pay.id_site')
            ->leftJoin('business_relation_contacts as brc_pay', 'brc_pay.id_contact', '=', 'sq.id_pic_pelanggan_payment')
            ->leftJoin('users as pic_o', 'pic_o.id', '=', 'sq.pic_order')
            ->leftJoin('users as mkt_i', 'mkt_i.id', '=', 'sq.pic_marketing_internal')
            ->leftJoin('users as mkt_e', 'mkt_e.id', '=', 'sq.pic_marketing_eksternal')
            ->leftJoin('office as o', 'o.id_office', '=', 'sq.id_office')
            ->where('sq.id_sq', $id)
            ->whereNull('sq.deleted_at')
            ->select([
                'sq.*',
                'o.name as nama_office',
                'pelanggan.nama as nama_pelanggan', 'ent.nama as entitas_pelanggan',
                'site_pelanggan.nama_lokasi as nama_site_pelanggan',
                'brc.nama_pic as pic_pelanggan',
                'del.nama as nama_pelanggan_delivery', 'ent_del.nama as entitas_delivery',
                'site_del.nama_lokasi as nama_site_delivery',
                'brc_del.nama_pic as pic_delivery',
                'pay.nama as nama_pelanggan_payment', 'ent_pay.nama as entitas_payment',
                'site_pay.nama_lokasi as nama_site_payment',
                'brc_pay.nama_pic as pic_payment',
                'pic_o.name as nama_pic_order',
                'mkt_i.name as nama_marketing_internal',
                'mkt_e.name as nama_marketing_eksternal',
            ])
            ->first();
        if (!$sq) return response()->json(['message' => 'Sales Quotation tidak ditemukan'], 404);

        $sq->nama_pelanggan = brDisplayName($sq->entitas_pelanggan, $sq->nama_pelanggan);
        $sq->nama_pelanggan_delivery = brDisplayName($sq->entitas_delivery, $sq->nama_pelanggan_delivery);
        $sq->nama_pelanggan_payment = brDisplayName($sq->entitas_payment, $sq->nama_pelanggan_payment);

        $wos = DB::table('sq_work_orders as w')
            ->leftJoin('business_relation_sites as brs', 'brs.id_site', '=', 'w.id_site_pelanggan_pekerjaan')
            ->leftJoin('users as pic', 'pic.id', '=', 'w.id_pic_pelanggan_pekerjaan')
            ->where('w.id_sq', $id)
            ->when($onlyWoId, fn ($q) => $q->where('w.id_sq_wo', $onlyWoId))
            ->orderBy('w.urutan')->orderBy('w.id_sq_wo')
            ->select([
                'w.id_sq_wo', 'w.no_sq_wo', 'w.judul_pekerjaan', 'w.id_site_pelanggan_pekerjaan', 'brs.nama_lokasi as nama_site', 'brs.id_br as id_br_site',
                'w.id_pic_pelanggan_pekerjaan', 'pic.name as nama_pic',
                'w.hari_mulai', 'w.durasi_hari', 'w.interval_bulan', 'w.no_urut_period', 'w.keterangan',
            ])
            ->get();

        if ($onlyWoId && $wos->isEmpty()) {
            return response()->json(['message' => 'SQ Work Order tidak ditemukan'], 404);
        }

        foreach ($wos as $wo) {
            $wo->boq = DB::table('sq_boq as b')
                ->leftJoin('testing_points as tp', 'b.id_testing_point', '=', 'tp.id_testing_point')
                ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
                ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
                ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 'b.id_satuan')
                ->where('b.id_sq_wo', $wo->id_sq_wo)
                ->orderBy('b.id_sq_boq')
                ->select([
                    'b.id_sq_boq as source_id_boq', 'b.id_testing_point', 'b.item_produk_alternate',
                    'b.qty', 'b.id_satuan', 'sat.nama as satuan', 'b.harga', 'b.discount', 'b.keterangan',
                    DB::raw("TRIM(CONCAT_WS(' ', NULLIF(tms.judul_indonesia,''), NULLIF(ts.nomor,''), NULLIF(tp.nama,''))) as point_name"),
                ])
                ->get();

            $tambahan = DB::table('sq_boq_tambahan as t')
                ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 't.id_satuan')
                ->where('t.id_sq_wo', $wo->id_sq_wo)
                ->orderBy('t.id_sq_boq_tambahan')
                ->select(['t.id_sq_boq_tambahan as source_id_boq_tambahan', 't.jenis', 't.nama_item', 't.qty', 't.id_satuan', 'sat.nama as satuan', 't.harga', 't.keterangan'])
                ->get();
            $wo->boq_other = $tambahan->where('jenis', 'lainnya')->values();
            $wo->boq_sampling = $tambahan->where('jenis', 'sampling')->values();

            $wo->budgets = $this->budgetsOf('sq_wo_budgets', 'sq_wo_budget_items', 'id_sq_wo', $wo->id_sq_wo);

            $fwos = DB::table('sq_fieldworks as f')
                ->leftJoin('business_relation_sites as brs', 'brs.id_site', '=', 'f.id_site_pelanggan_pekerjaan')
                ->leftJoin('business_relation_contacts as brc', 'brc.id_contact', '=', 'f.id_pic_pelanggan_pekerjaan')
                ->where('f.id_sq_wo', $wo->id_sq_wo)
                ->orderBy('f.urutan')->orderBy('f.id_sq_fwo')
                ->select([
                    'f.id_sq_fwo', 'f.no_sq_fwo', 'f.judul_pekerjaan', 'f.id_site_pelanggan_pekerjaan', 'brs.nama_lokasi as nama_site', 'brs.id_br as id_br_site',
                    'f.id_pic_pelanggan_pekerjaan', 'brc.nama_pic', 'f.hari_ke', 'f.durasi_hari', 'f.keterangan',
                ])
                ->get();

            foreach ($fwos as $f) {
                $f->fieldwork_boq = DB::table('sq_fieldwork_boq as fb')
                    ->join('sq_boq as b', 'b.id_sq_boq', '=', 'fb.id_sq_boq')
                    ->leftJoin('testing_points as tp', 'b.id_testing_point', '=', 'tp.id_testing_point')
                    ->leftJoin('testing_matriks_samples as tms', 'tp.id_testing_matriks_sample', '=', 'tms.id_testing_matriks_sample')
                    ->leftJoin('testing_standards as ts', 'tp.id_testing_standard', '=', 'ts.id_testing_standard')
                    ->where('fb.id_sq_fwo', $f->id_sq_fwo)
                    ->orderBy('fb.id_sq_fwo_boq')
                    ->select([
                        'fb.id_sq_fwo_boq as source_id_fwo_boq', 'b.id_testing_point', 'fb.qty', 'fb.keterangan',
                        DB::raw("TRIM(CONCAT_WS(' ', NULLIF(tms.judul_indonesia,''), NULLIF(ts.nomor,''), NULLIF(tp.nama,''))) as point_name"),
                    ])
                    ->get();
                $f->budgets = $this->budgetsOf('sq_fwo_budgets', 'sq_fwo_budget_items', 'id_sq_fwo', $f->id_sq_fwo);
            }
            $wo->fwos = $fwos;
        }

        return response()->json(['sq' => $sq, 'wos' => $wos]);
    }

    /** Budget Plan + ringkasan item (read-only di halaman clone; item disalin dari DB saat simpan). */
    private function budgetsOf(string $budgetTable, string $itemTable, string $parentCol, $parentId)
    {
        $budgets = DB::table($budgetTable)->where($parentCol, $parentId)->orderBy('created_at')->orderBy('id_sq_budget')
            ->get(['id_sq_budget as source_id_budget', 'label', 'keterangan', 'hari_mulai', 'hari_selesai']);

        foreach ($budgets as $b) {
            $b->items = DB::table("{$itemTable} as bi")
                ->leftJoin('budget_accounts as ba', 'ba.id_account', '=', 'bi.id_account')
                ->where('bi.id_sq_budget', $b->source_id_budget)
                ->orderBy('bi.id_sq_budget_item')
                ->get(['ba.nama as nama_account', 'bi.nominal_budget', 'bi.is_cash_advance']);
        }

        return $budgets;
    }

    // ── Simpan ───────────────────────────────────────────────────────────────

    public function woClonePost(Request $request, $idWo)
    {
        $wo = DB::table('sq_work_orders')->where('id_sq_wo', $idWo)->first();
        if (!$wo) return response()->json(['message' => 'SQ Work Order tidak ditemukan'], 404);

        // WO baru masuk ke SQ sumber, jadi aturan penguncian SQ berlaku (hanya Draft yang boleh ditambah)
        if ($lock = SqLock::bySq($wo->id_sq)) return $lock;

        $wos = $request->input('wos', []);
        if (count($wos) !== 1 || (int) ($wos[0]['source_id_wo'] ?? 0) !== (int) $idWo) {
            return response()->json(['message' => 'Payload tidak valid: hanya boleh memuat WO yang sedang disalin.'], 422);
        }

        return $this->clonePost($request->merge(['wo_only' => true]), $wo->id_sq);
    }

    public function clonePost(Request $request, $id)
    {
        $woOnly = (bool) $request->input('wo_only');

        $source = DB::table('sales_quotations')->where('id_sq', $id)->whereNull('deleted_at')->first();
        if (!$source) return response()->json(['message' => 'Sales Quotation tidak ditemukan'], 404);

        $rules = [
            'wos' => 'required|array|min:1',
            'wos.*.source_id_wo' => 'required|integer',
            'wos.*.include' => 'required|boolean',
            'wos.*.judul_pekerjaan' => 'nullable|string|max:255',
            'wos.*.id_site_pelanggan_pekerjaan' => 'nullable|integer',
            'wos.*.id_pic_pelanggan_pekerjaan' => 'nullable|integer',
            'wos.*.hari_mulai' => 'nullable|integer|min:1',
            'wos.*.durasi_hari' => 'nullable|integer|min:1',
            'wos.*.interval_bulan' => 'nullable|integer',
            'wos.*.no_urut_period' => 'nullable|integer|min:1',
            'wos.*.keterangan' => 'nullable|string',

            'wos.*.boq' => 'nullable|array',
            'wos.*.boq.*.source_id_boq' => 'nullable|integer',
            'wos.*.boq.*.include' => 'required|boolean',
            'wos.*.boq.*.id_testing_point' => 'nullable|integer',
            'wos.*.boq.*.id_testing_items' => 'nullable|array',
            'wos.*.boq.*.id_testing_items.*' => 'integer',
            'wos.*.boq.*.item_produk_alternate' => 'nullable|string|max:500',
            'wos.*.boq.*.qty' => 'nullable|integer|min:0',
            'wos.*.boq.*.id_satuan' => 'nullable|integer|exists:satuan,id_satuan',
            'wos.*.boq.*.harga' => 'nullable|numeric|min:0',
            'wos.*.boq.*.discount' => 'nullable|numeric|min:0',
            'wos.*.boq.*.keterangan' => 'nullable|string',

            'wos.*.budgets' => 'nullable|array',
            'wos.*.fwos' => 'nullable|array',
            'wos.*.fwos.*.source_id_fwo' => 'required|integer',
            'wos.*.fwos.*.include' => 'required|boolean',
            'wos.*.fwos.*.judul_pekerjaan' => 'nullable|string|max:500',
            'wos.*.fwos.*.id_site_pelanggan_pekerjaan' => 'nullable|integer',
            'wos.*.fwos.*.id_pic_pelanggan_pekerjaan' => 'nullable|integer',
            'wos.*.fwos.*.hari_ke' => 'nullable|integer|min:1',
            'wos.*.fwos.*.durasi_hari' => 'nullable|integer|min:1',
            'wos.*.fwos.*.keterangan' => 'nullable|string',
            'wos.*.fwos.*.fieldwork_boq' => 'nullable|array',
            'wos.*.fwos.*.fieldwork_boq.*.source_id_fwo_boq' => 'required|integer',
            'wos.*.fwos.*.fieldwork_boq.*.include' => 'required|boolean',
            'wos.*.fwos.*.fieldwork_boq.*.qty' => 'nullable|integer|min:0',
            'wos.*.fwos.*.fieldwork_boq.*.keterangan' => 'nullable|string',
            'wos.*.fwos.*.budgets' => 'nullable|array',
        ];

        foreach (['boq_other', 'boq_sampling'] as $key) {
            $rules["wos.*.{$key}"] = 'nullable|array';
            $rules["wos.*.{$key}.*.source_id_boq_tambahan"] = 'nullable|integer';
            $rules["wos.*.{$key}.*.include"] = 'required|boolean';
            $rules["wos.*.{$key}.*.nama_item"] = 'nullable|string|max:255';
            $rules["wos.*.{$key}.*.qty"] = 'nullable|integer|min:0';
            $rules["wos.*.{$key}.*.id_satuan"] = 'nullable|integer|exists:satuan,id_satuan';
            $rules["wos.*.{$key}.*.harga"] = 'nullable|numeric|min:0';
            $rules["wos.*.{$key}.*.keterangan"] = 'nullable|string';
        }
        foreach (['wos.*.budgets', 'wos.*.fwos.*.budgets'] as $bp) {
            $rules["{$bp}.*.source_id_budget"] = 'required|integer';
            $rules["{$bp}.*.include"] = 'required|boolean';
            $rules["{$bp}.*.label"] = 'nullable|string|max:255';
            $rules["{$bp}.*.keterangan"] = 'nullable|string';
            $rules["{$bp}.*.hari_mulai"] = 'nullable|integer|min:1';
            $rules["{$bp}.*.hari_selesai"] = 'nullable|integer|min:1';
        }

        $messages = [
            'sq.tanggal_sq.required' => 'Tanggal SQ wajib diisi.',
            'sq.judul_order.required' => 'Judul Order wajib diisi.',
            'sq.berlaku_sampai.after_or_equal' => 'Berlaku Sampai tidak boleh sebelum Tanggal SQ.',
        ];

        if (!$woOnly) {
            $rules += [
                'sq' => 'required|array',
                'sq.tanggal_sq' => 'required|date',
                'sq.judul_order' => 'required|string|max:255',
                'sq.berlaku_sampai' => 'nullable|date|after_or_equal:sq.tanggal_sq',
                'sq.rencana_mulai' => 'nullable|date',
                'sq.cara_pembayaran' => 'nullable|string',
                'sq.keterangan' => 'nullable|string',
                'sq.salin_keterangan' => 'nullable|boolean',
            ];
            foreach (SalesQuotationController::orderPartyRules() as $field => $rule) {
                $rules["sq.{$field}"] = $rule;
            }
            foreach (SalesQuotationController::orderPartyMessages() as $key => $msg) {
                $messages["sq.{$key}"] = $msg;
            }
        }

        $validated = $request->validate($rules, $messages);

        $sourceWos = DB::table('sq_work_orders')->where('id_sq', $id)->get()->keyBy('id_sq_wo');
        $skippedFwoBoq = 0;

        try {
            $result = DB::transaction(function () use ($validated, $source, $woOnly, $sourceWos, &$skippedFwoBoq) {
                if ($woOnly) {
                    $targetSqId = (int) $source->id_sq;
                    $urutan = (int) DB::table('sq_work_orders')->where('id_sq', $targetSqId)->max('urutan');
                } else {
                    $targetSqId = $this->insertSqHeader($validated['sq'], $source);
                    $urutan = 0;
                }

                $newWoIds = [];
                foreach ($validated['wos'] as $woIn) {
                    if (empty($woIn['include'])) continue;

                    $src = $sourceWos->get($woIn['source_id_wo']);
                    if (!$src) throw new \RuntimeException('Work Order sumber tidak valid atau bukan milik SQ ini.');

                    $newWoIds[] = $this->cloneWo($woIn, $src, $targetSqId, ++$urutan, $skippedFwoBoq);
                }

                if (!$newWoIds && $woOnly) {
                    throw new \RuntimeException('Work Order ini tidak dicentang untuk disertakan.');
                }

                return ['id_sq' => $targetSqId, 'id_sq_wo' => $newWoIds[0] ?? null, 'total_wo' => count($newWoIds)];
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $skipNote = $skippedFwoBoq ? " ({$skippedFwoBoq} alokasi Fieldwork BOQ dilewati karena BOQ WO terkait tidak disertakan)" : '';

        if ($woOnly) {
            return response()->json($result + ['success' => true, 'message' => 'SQ Work Order berhasil disalin' . $skipNote]);
        }

        $sqBaru = DB::table('sales_quotations')->where('id_sq', $result['id_sq'])->first(['no_sq', 'judul_order']);
        NotificationDispatcher::fire('sq.created', [
            'title' => 'SQ baru: {{no_sq}}',
            'body'  => '{{judul_order}}',
            'url'   => url('sales-quotations?open=' . $result['id_sq']),
        ], [
            'no_sq'       => $sqBaru->no_sq ?? '',
            'judul_order' => $sqBaru->judul_order ?? '',
            'nama_user'   => Auth::user()?->name,
        ], NotificationRoles::sq((int) $result['id_sq']));

        return response()->json($result + [
            'success' => true,
            'no_sq' => $sqBaru->no_sq ?? null,
            'message' => "Sales Quotation berhasil disalin menjadi {$sqBaru->no_sq} ({$result['total_wo']} WO)" . $skipNote,
        ]);
    }

    /** SQ baru: Draft, Rev.0, tidak tertaut ke SQ sumber. Mengembalikan id_sq baru. */
    private function insertSqHeader(array $in, object $source): int
    {
        $idSq = DocumentNumber::insertGetId('sales_quotations', 'no_sq', 'SQ', array_merge(
            SalesQuotationController::orderPartyColumns(new Request($in)),
            [
                'revisi' => 0,
                'is_latest' => true,
                'tanggal_sq' => $in['tanggal_sq'],
                'berlaku_sampai' => $in['berlaku_sampai'] ?? null,
                'judul_order' => $in['judul_order'],
                'rencana_mulai' => $in['rencana_mulai'] ?? null,
                'discount' => (int) ($source->discount ?? 0),
                'cara_pembayaran' => $in['cara_pembayaran'] ?? null,
                'keterangan' => $in['keterangan'] ?? null,
                'status' => 'draft',
                'attachment' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ));

        // Poin Keterangan Penawaran: salin dari SQ sumber (default) atau pakai paket standar
        if (!empty($in['salin_keterangan'])) {
            SqKeterangan::salin((int) $source->id_sq, (int) $idSq);
        } else {
            SqKeterangan::isiStandar((int) $idSq);
        }

        $after = DB::table('sales_quotations')->where('id_sq', $idSq)->get()->toJson();
        saveAudit('sales_quotations', $idSq, 'Create', json_encode(['note' => "Clone dari SQ {$source->no_sq} Rev.{$source->revisi}"]), $after);

        return (int) $idSq;
    }

    /** Salin 1 WO sumber (+ BOQ, Other/Sampling, Budget, FWO) ke SQ target. Mengembalikan id_sq_wo baru. */
    private function cloneWo(array $in, object $src, int $targetSqId, int $urutan, int &$skippedFwoBoq): int
    {
        $label = $src->no_sq_wo ?: "WO #{$src->id_sq_wo}";

        $judul = trim((string) ($in['judul_pekerjaan'] ?? ''));
        if ($judul === '') throw new \RuntimeException("{$label}: Judul Pekerjaan wajib diisi.");

        // Perusahaan WO selalu diturunkan dari Site yang dipilih (sq_work_orders tidak punya field Perusahaan di UI)
        $siteId = $in['id_site_pelanggan_pekerjaan'] ?? null;
        $brId = null;
        if ($siteId) {
            $brId = DB::table('business_relation_sites')->where('id_site', $siteId)->value('id_br');
            if (!$brId) throw new \RuntimeException("{$label}: Site yang dipilih tidak ditemukan.");
        }

        $idWo = DocumentNumber::insertGetId('sq_work_orders', 'no_sq_wo', 'SQWO', [
            'id_sq' => $targetSqId,
            'urutan' => $urutan,
            'judul_pekerjaan' => $judul,
            'id_pelanggan_pekerjaan' => $brId,
            'id_site_pelanggan_pekerjaan' => $siteId,
            'id_pic_pelanggan_pekerjaan' => $in['id_pic_pelanggan_pekerjaan'] ?? null,
            'hari_mulai' => $in['hari_mulai'] ?? ($src->hari_mulai ?: 1),
            'durasi_hari' => $in['durasi_hari'] ?? null,
            'interval_bulan' => $in['interval_bulan'] ?? null,
            'no_urut_period' => !empty($in['interval_bulan']) ? ($in['no_urut_period'] ?? null) : null,
            'keterangan' => $in['keterangan'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ── BOQ (+ Testing Item) ──
        $srcBoq = DB::table('sq_boq')->where('id_sq_wo', $src->id_sq_wo)->get()->keyBy('id_sq_boq');
        $boqIdBySrc = [];
        $usedPoints = [];

        foreach (($in['boq'] ?? []) as $r) {
            if (empty($r['include'])) continue;

            $qty = (int) ($r['qty'] ?? 0);
            if ($qty < 1) throw new \RuntimeException("{$label}: isi Qty (minimal 1) untuk semua baris BOQ yang disertakan.");
            $harga = (int) round((float) ($r['harga'] ?? 0));
            $discount = min((int) round((float) ($r['discount'] ?? 0)), $qty * $harga);

            if (!empty($r['source_id_boq'])) {
                $s = $srcBoq->get($r['source_id_boq']);
                if (!$s) throw new \RuntimeException("{$label}: BOQ sumber tidak valid.");
                $pointId = $s->id_testing_point;
                $itemIds = DB::table('sq_boq_items')->where('id_sq_boq', $s->id_sq_boq)->pluck('id_testing_item')->all();
                $alt = array_key_exists('item_produk_alternate', $r) ? $r['item_produk_alternate'] : $s->item_produk_alternate;
            } else {
                // BOQ baru: Testing Point harus ada, Testing Item disaring ulang agar benar milik Testing Point itu
                $s = null;
                $pointId = $r['id_testing_point'] ?? null;
                if (!$pointId || !DB::table('testing_points')->where('id_testing_point', $pointId)->exists()) {
                    throw new \RuntimeException("{$label}: Testing Point untuk BOQ baru tidak valid.");
                }
                $itemIds = DB::table('testing_items')->where('id_testing_point', $pointId)
                    ->whereIn('id_testing_item', $r['id_testing_items'] ?? [])->pluck('id_testing_item')->all();
                if (!$itemIds) throw new \RuntimeException("{$label}: pilih minimal 1 Testing Item untuk BOQ baru.");
                $alt = $r['item_produk_alternate'] ?? null;
            }

            if (isset($usedPoints[$pointId])) {
                throw new \RuntimeException("{$label}: Testing Point yang sama dipakai di lebih dari satu BOQ.");
            }
            $usedPoints[$pointId] = true;

            $idBoq = DB::table('sq_boq')->insertGetId([
                'id_sq_wo' => $idWo,
                'id_testing_point' => $pointId,
                'item_produk_alternate' => $alt,
                'qty' => $qty,
                'id_satuan' => $r['id_satuan'] ?? null,
                'harga' => $harga,
                'discount' => $discount,
                'keterangan' => $r['keterangan'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if ($s) $boqIdBySrc[$s->id_sq_boq] = $idBoq;

            if ($itemIds) {
                DB::table('sq_boq_items')->insert(array_map(fn ($i) => [
                    'id_sq_boq' => $idBoq, 'id_testing_item' => $i, 'created_at' => now(), 'updated_at' => now(),
                ], $itemIds));
            }
        }

        // ── BOQ Other & BOQ Sampling ──
        $srcTambahan = DB::table('sq_boq_tambahan')->where('id_sq_wo', $src->id_sq_wo)->get()->keyBy('id_sq_boq_tambahan');
        foreach (['boq_other' => 'lainnya', 'boq_sampling' => 'sampling'] as $key => $jenis) {
            foreach (($in[$key] ?? []) as $r) {
                if (empty($r['include'])) continue;

                if (!empty($r['source_id_boq_tambahan'])) {
                    $s = $srcTambahan->get($r['source_id_boq_tambahan']);
                    if (!$s || $s->jenis !== $jenis) throw new \RuntimeException("{$label}: item BOQ " . ($jenis === 'sampling' ? 'Sampling' : 'Other') . ' sumber tidak valid.');
                }

                $nama = trim((string) ($r['nama_item'] ?? ''));
                $qty = (int) ($r['qty'] ?? 0);
                if ($nama === '') throw new \RuntimeException("{$label}: isi Nama Item untuk semua baris BOQ " . ($jenis === 'sampling' ? 'Sampling' : 'Other') . '.');
                if ($qty < 1) throw new \RuntimeException("{$label}: isi Qty (minimal 1) untuk semua baris BOQ " . ($jenis === 'sampling' ? 'Sampling' : 'Other') . '.');

                DB::table('sq_boq_tambahan')->insert([
                    'id_sq_wo' => $idWo,
                    'jenis' => $jenis,
                    'nama_item' => $nama,
                    'qty' => $qty,
                    'id_satuan' => $r['id_satuan'] ?? null,
                    'harga' => (int) round((float) ($r['harga'] ?? 0)),
                    'keterangan' => $r['keterangan'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // ── Budget Plan WO ──
        $this->cloneBudgets($in['budgets'] ?? [], 'sq_wo_budgets', 'sq_wo_budget_items', 'id_sq_wo', $src->id_sq_wo, $idWo, $label);

        // ── FWO (+ Fieldwork BOQ + Budget Plan) ──
        $srcFwos = DB::table('sq_fieldworks')->where('id_sq_wo', $src->id_sq_wo)->get()->keyBy('id_sq_fwo');
        $fwoUrutan = 0;
        foreach (($in['fwos'] ?? []) as $fIn) {
            if (empty($fIn['include'])) continue;

            $sf = $srcFwos->get($fIn['source_id_fwo']);
            if (!$sf) throw new \RuntimeException("{$label}: FWO sumber tidak valid.");
            $fLabel = ($sf->no_sq_fwo ?: "FWO #{$sf->id_sq_fwo}");

            $fJudul = trim((string) ($fIn['judul_pekerjaan'] ?? ''));
            if ($fJudul === '') throw new \RuntimeException("{$label} — {$fLabel}: Judul Pekerjaan wajib diisi.");

            $idFwo = DocumentNumber::insertGetId('sq_fieldworks', 'no_sq_fwo', 'SQFWO', [
                'id_sq_wo' => $idWo,
                'urutan' => ++$fwoUrutan,
                'judul_pekerjaan' => $fJudul,
                'id_site_pelanggan_pekerjaan' => $fIn['id_site_pelanggan_pekerjaan'] ?? null,
                'id_pic_pelanggan_pekerjaan' => $fIn['id_pic_pelanggan_pekerjaan'] ?? null,
                'hari_ke' => $fIn['hari_ke'] ?? ($sf->hari_ke ?: 1),
                'durasi_hari' => $fIn['durasi_hari'] ?? ($sf->durasi_hari ?: 1),
                'keterangan' => $fIn['keterangan'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $srcFwoBoq = DB::table('sq_fieldwork_boq')->where('id_sq_fwo', $sf->id_sq_fwo)->get()->keyBy('id_sq_fwo_boq');
            foreach (($fIn['fieldwork_boq'] ?? []) as $fr) {
                if (empty($fr['include'])) continue;

                $sfb = $srcFwoBoq->get($fr['source_id_fwo_boq']);
                if (!$sfb) throw new \RuntimeException("{$label} — {$fLabel}: Fieldwork BOQ sumber tidak valid.");

                // BOQ WO-nya tidak ikut disalin (di-uncheck) → alokasi ini dilewati, bukan menggagalkan seluruh clone
                if (!isset($boqIdBySrc[$sfb->id_sq_boq])) { $skippedFwoBoq++; continue; }

                $qty = (int) ($fr['qty'] ?? 0);
                if ($qty < 1) throw new \RuntimeException("{$label} — {$fLabel}: isi Qty (minimal 1) untuk semua baris Fieldwork BOQ.");

                $idFwoBoq = DB::table('sq_fieldwork_boq')->insertGetId([
                    'id_sq_fwo' => $idFwo,
                    'id_sq_boq' => $boqIdBySrc[$sfb->id_sq_boq],
                    'qty' => $qty,
                    'keterangan' => $fr['keterangan'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $items = DB::table('sq_fieldwork_boq_items')->where('id_sq_fwo_boq', $sfb->id_sq_fwo_boq)->pluck('id_testing_item')->all();
                if ($items) {
                    DB::table('sq_fieldwork_boq_items')->insert(array_map(fn ($i) => [
                        'id_sq_fwo_boq' => $idFwoBoq, 'id_testing_item' => $i, 'created_at' => now(), 'updated_at' => now(),
                    ], $items));
                }
            }

            $this->cloneBudgets($fIn['budgets'] ?? [], 'sq_fwo_budgets', 'sq_fwo_budget_items', 'id_sq_fwo', $sf->id_sq_fwo, $idFwo, "{$label} — {$fLabel}");
        }

        $after = DB::table('sq_work_orders')->where('id_sq_wo', $idWo)->get()->toJson();
        saveAudit('sq_work_orders', $idWo, 'Create', json_encode(['note' => 'Clone dari SQ WO ' . ($src->no_sq_wo ?: $src->id_sq_wo)]), $after);

        return (int) $idWo;
    }

    /**
     * Budget Plan dipilih per-Plan (centang); Item-nya disalin utuh dari DB, bukan dari payload.
     * Plan sumber harus milik induk (WO/FWO) yang sedang disalin.
     */
    private function cloneBudgets(array $budgets, string $budgetTable, string $itemTable, string $parentCol, $srcParentId, $newParentId, string $label): void
    {
        $srcPlans = DB::table($budgetTable)->where($parentCol, $srcParentId)->get()->keyBy('id_sq_budget');

        foreach ($budgets as $b) {
            if (empty($b['include'])) continue;

            $sp = $srcPlans->get($b['source_id_budget']);
            if (!$sp) throw new \RuntimeException("{$label}: Budget Plan sumber tidak valid.");

            $plabel = trim((string) ($b['label'] ?? ''));
            if ($plabel === '') throw new \RuntimeException("{$label}: isi Label untuk semua Budget Plan yang disertakan.");

            $hm = $b['hari_mulai'] ?? null;
            $hs = $b['hari_selesai'] ?? null;
            if ($hm && $hs && $hs < $hm) throw new \RuntimeException("{$label}: Hari Selesai Budget Plan \"{$plabel}\" tidak boleh sebelum Hari Mulai.");

            $newPlanId = DB::table($budgetTable)->insertGetId([
                $parentCol => $newParentId,
                'label' => $plabel,
                'keterangan' => $b['keterangan'] ?? null,
                'hari_mulai' => $hm,
                'hari_selesai' => $hs,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (DB::table($itemTable)->where('id_sq_budget', $sp->id_sq_budget)->get() as $item) {
                $row = (array) $item;
                unset($row['id_sq_budget_item']);
                $row['id_sq_budget'] = $newPlanId;
                $row['created_at'] = now();
                $row['updated_at'] = now();
                DB::table($itemTable)->insert($row);
            }
        }
    }
}
