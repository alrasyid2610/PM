<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait HasAuditHistory
{
    /** Nama bagian (tab) tiap tabel anak di History modul induk. */
    private const AUDIT_BAGIAN = [
        'work_orders'       => 'Work Order',
        'termin'            => 'Termin',
        'boq'               => 'BOQ',
        'boq_tambahan'      => 'BOQ Other',
        'output_pekerjaan'  => 'Output Pekerjaan',
        'output_tambahan'   => 'Output Other',
        'wo_budgets'        => 'Budget — Plan',
        'wo_budget_actuals' => 'Budget — Realisasi',
        'lab_samples'       => 'Lab Sample Reg',
        'fieldworks'        => 'Fieldwork Order',
        'fieldwork_boq'     => 'BOQ',
        'fwo_boq_tambahan'  => 'BOQ Other',
        'fieldwork_personels' => 'Personel',
        'fieldwork_attachments' => 'Lampiran',
        'fwo_budgets'       => 'Budget — Plan',
        'fwo_budget_actuals' => 'Budget — Realisasi',
        'sq_keterangan'     => 'Keterangan',
        'sq_work_orders'    => 'Work Order',
        'sq_fieldworks'     => 'Fieldwork Order',
        'sq_boq'            => 'BOQ',
        'sq_boq_tambahan'   => 'BOQ Other',
        'sq_fieldwork_boq'  => 'BOQ',
        'sq_wo_budgets'     => 'Budget — Plan',
        'sq_fwo_budgets'    => 'Budget — Plan',
    ];

    /** Kolom kunci/teknis tabel anak yang tidak perlu tampil sebagai perubahan. */
    private const AUDIT_EXCLUDE_ANAK = [
        'id_boq_tambahan', 'id_output_tambahan', 'id_output', 'id_budget', 'id_actual',
        'id_lab_sample', 'id_wo', 'id_budget_item', 'deleted_at', 'akun', 'plan',
        'id_fwo', 'id_fwo_boq', 'id_fwo_boq_tambahan',
        'id_so', 'id_termin', 'id_sq', 'id_sq_wo', 'id_sq_fwo', 'id_sq_boq_tambahan', 'id_sq_budget', 'id_sq_keterangan', 'id_sq_fwo_boq',
    ];

    public function history($id)
    {
        return $this->auditHistory($id);
    }

    /** Implementasi History; dipanggil juga oleh controller yang meng-override history() sendiri. */
    public function auditHistory($id)
    {
        $table         = $this->auditTable();
        $excludeFields = $this->auditExcludeFields();

        // Modul induk (mis. WO) menampilkan juga perubahan tab-tab anaknya
        $parentTable = $this->auditParentTable();
        $adaParent   = $parentTable && Schema::hasColumn('audit_logs', 'parent_table');
        $extraTables = $parentTable ? $this->auditExtraTables() : [];

        $logs = DB::table('audit_logs as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.created_by')
            ->where(function ($q) use ($table, $id, $adaParent, $parentTable, $extraTables) {
                $q->where(fn ($w) => $w->where('a.nama_table', $table)->where('a.row_id', $id));
                if ($adaParent) {
                    $q->orWhere(fn ($w) => $w->where('a.parent_table', $parentTable)->where('a.parent_id', $id));
                }
                if ($extraTables) {
                    $q->orWhere(fn ($w) => $w->whereIn('a.nama_table', $extraTables)->where('a.row_id', $id));
                }
            })
            ->select([
                'a.id',
                'a.nama_table',
                'a.action',
                'a.old_value',
                'a.new_value',
                'a.created_at',
                'u.name as created_by_name',
            ])
            ->orderByDesc('a.created_at')
            ->orderByDesc('a.id')
            ->get()
            ->map(function ($log) use ($excludeFields, $table, $parentTable) {

                $old = $this->parseAuditValue($log->old_value);
                $new = $this->parseAuditValue($log->new_value);
                $anak = $parentTable && $log->nama_table !== $table;

                $changes = [];

                if ($log->nama_table === 'boq' && $parentTable) {
                    $changes = app(\App\Http\Controllers\BoqController::class)
                        ->auditChanges($log->old_value, $log->new_value);
                } elseif (is_array($old) && is_array($new)) {
                    $exclude = $anak ? array_merge($excludeFields, self::AUDIT_EXCLUDE_ANAK) : $excludeFields;
                    foreach ($new as $key => $newVal) {
                        if (in_array($key, $exclude)) continue;
                        $oldVal = $old[$key] ?? null;
                        if ((string)$oldVal !== (string)$newVal) {
                            $changes[] = [
                                'field'     => $key,
                                'old_value' => $oldVal,
                                'new_value' => $newVal,
                            ];
                        }
                    }

                    // Field yang hilang di data baru (mis. item budget dihapus) ikut tercatat
                    if ($parentTable) {
                        foreach ($old as $key => $oldVal) {
                            if (array_key_exists($key, $new) || in_array($key, $exclude)) continue;
                            $changes[] = ['field' => $key, 'old_value' => $oldVal, 'new_value' => null];
                        }
                    }
                }

                if ($parentTable) {
                    $changes = $this->auditTerjemahkanId($changes, $log->nama_table);
                }

                $hasil = [
                    'id'              => $log->id,
                    'action'          => strtolower($log->action),
                    'changes'         => $changes,
                    'total_changes'   => count($changes),
                    'created_by_name' => $log->created_by_name ?? 'System',
                    'created_at'      => $log->created_at,
                ];

                if ($parentTable) {
                    $snap = $new ?: $old;
                    $hasil['bagian'] = $this->auditBagian($log->nama_table, $snap, $table, $parentTable);
                    $hasil['label']  = $anak ? $this->auditLabel($snap) : null;
                }

                return $hasil;
            })
            // Log lama berisi update tanpa perubahan (hanya updated_at) disembunyikan di History modul induk
            ->filter(fn ($l) => !($parentTable && $l['action'] === 'update' && $l['total_changes'] === 0))
            ->values();

        return response()->json($logs);
    }

    /** Field berisi id → [tabel, kolom id, kolom nama] untuk ditampilkan sebagai nama di History modul induk. */
    private const AUDIT_ID_KE_NAMA = [
        'verified_by'                => ['users', 'id', 'name'],
        'id_satuan'                  => ['satuan', 'id_satuan', 'nama'],
        'id_personnel_penerima'      => ['personnel', 'id_personnel', 'nama'],
        'id_so'                      => ['sales_orders', 'id_so', 'no_so'],
        'id_pelanggan_pekerjaan'     => ['business_relations', 'id_br', 'nama'],
        'id_site_pelanggan_pekerjaan' => ['business_relation_sites', 'id_site', 'nama_lokasi'],
        'id_pic_pelanggan_pekerjaan' => ['users', 'id', 'name'],
    ];

    /** Tambahan/penimpa peta id→nama untuk log tabel tertentu (nama field sama bisa merujuk tabel berbeda). */
    private const AUDIT_ID_PER_TABEL = [
        'fieldworks' => [
            'id_pic_pelanggan_pekerjaan' => ['business_relation_contacts', 'id_contact', 'nama_pic'],
            'id_penyelia'                => ['users', 'id', 'name'],
            'id_wo'                      => ['work_orders', 'id_wo', 'no_wo'],
        ],
        'sales_quotations' => [
            'id_pelanggan'                => ['business_relations', 'id_br', 'nama'],
            'id_pelanggan_delivery'       => ['business_relations', 'id_br', 'nama'],
            'id_pelanggan_payment'        => ['business_relations', 'id_br', 'nama'],
            'id_site_pelanggan'           => ['business_relation_sites', 'id_site', 'nama_lokasi'],
            'id_site_pelanggan_delivery'  => ['business_relation_sites', 'id_site', 'nama_lokasi'],
            'id_site_pelanggan_payment'   => ['business_relation_sites', 'id_site', 'nama_lokasi'],
            'id_pic_pelanggan'            => ['business_relation_contacts', 'id_contact', 'nama_pic'],
            'id_pic_pelanggan_delivery'   => ['business_relation_contacts', 'id_contact', 'nama_pic'],
            'id_pic_pelanggan_payment'    => ['business_relation_contacts', 'id_contact', 'nama_pic'],
            'pic_input'                   => ['users', 'id', 'name'],
            'pic_marketing_internal'      => ['users', 'id', 'name'],
            'pic_marketing_eksternal'     => ['users', 'id', 'name'],
            'id_office'                   => ['office', 'id_office', 'name'],
            'id_sq_induk'                 => ['sales_quotations', 'id_sq', 'no_sq'],
        ],
        'sales_orders' => [
            'id_pelanggan'                => ['business_relations', 'id_br', 'nama'],
            'id_pelanggan_delivery'       => ['business_relations', 'id_br', 'nama'],
            'id_pelanggan_payment'        => ['business_relations', 'id_br', 'nama'],
            'id_site_pelanggan'           => ['business_relation_sites', 'id_site', 'nama_lokasi'],
            'id_site_pelanggan_delivery'  => ['business_relation_sites', 'id_site', 'nama_lokasi'],
            'id_site_pelanggan_payment'   => ['business_relation_sites', 'id_site', 'nama_lokasi'],
            'id_pic_pelanggan'            => ['business_relation_contacts', 'id_contact', 'nama_pic'],
            'id_pic_pelanggan_delivery'   => ['business_relation_contacts', 'id_contact', 'nama_pic'],
            'id_pic_pelanggan_payment'    => ['business_relation_contacts', 'id_contact', 'nama_pic'],
            'pic_input'                   => ['users', 'id', 'name'],
            'pic_order'                   => ['users', 'id', 'name'],
            'pic_marketing_internal'      => ['users', 'id', 'name'],
            'pic_marketing_eksternal'     => ['users', 'id', 'name'],
            'id_office'                   => ['office', 'id_office', 'name'],
            'id_sq'                       => ['sales_quotations', 'id_sq', 'no_sq'],
            'id_sc'                       => ['contracts', 'id_contract', 'no_contract'],
            'id_so_referensi'             => ['sales_orders', 'id_so', 'no_so'],
        ],
        'sq_work_orders' => [
            'id_pelanggan_pekerjaan'      => ['business_relations', 'id_br', 'nama'],
            'id_site_pelanggan_pekerjaan' => ['business_relation_sites', 'id_site', 'nama_lokasi'],
            'id_pic_pelanggan_pekerjaan'  => ['users', 'id', 'name'],
        ],
        'sq_fieldworks' => [
            'id_site_pelanggan_pekerjaan' => ['business_relation_sites', 'id_site', 'nama_lokasi'],
            'id_pic_pelanggan_pekerjaan'  => ['business_relation_contacts', 'id_contact', 'nama_pic'],
            'id_sq_wo'                    => ['sq_work_orders', 'id_sq_wo', 'no_sq_wo'],
        ],
    ];

    /** Ganti nilai id pada daftar perubahan dengan namanya (nilai yang tidak ditemukan dibiarkan). */
    private function auditTerjemahkanId(array $changes, string $namaTable = ''): array
    {
        // Field dengan nama sama bisa merujuk tabel berbeda per modul
        $petaId = array_merge(self::AUDIT_ID_KE_NAMA, self::AUDIT_ID_PER_TABEL[$namaTable] ?? []);

        foreach ($changes as &$c) {
            $peta = $petaId[$c['field']] ?? null;
            if (!$peta) continue;

            foreach (['old_value', 'new_value'] as $k) {
                if ($c[$k] === null || $c[$k] === '') continue;
                $nama = DB::table($peta[0])->where($peta[1], $c[$k])->value($peta[2]);
                if ($nama !== null) $c[$k] = $nama;
            }
        }

        return $changes;
    }

    private function auditBagian(string $namaTable, $snap, string $table = '', ?string $parentTable = null): string
    {
        // Log milik tabel modul ini sendiri
        if ($namaTable === $table) return 'Informasi';

        if (in_array($namaTable, ['boq_tambahan', 'fwo_boq_tambahan', 'sq_boq_tambahan'], true) && is_array($snap) && ($snap['jenis'] ?? null) === 'sampling') {
            return 'BOQ Sampling';
        }
        // Sample di FWO disebut Sample, di WO disebut Lab Sample Reg
        if ($namaTable === 'lab_samples' && $parentTable === 'fieldworks') return 'Sample';

        return self::AUDIT_BAGIAN[$namaTable] ?? $namaTable;
    }

    /** Penamaan record anak (item/plan/sample) dari snapshot-nya. */
    private function auditLabel($snap): ?string
    {
        if (!is_array($snap)) return null;

        foreach (['nama_item', 'judul_output', 'label', 'no_sample', 'no_fwo', 'no_sq_wo', 'no_sq_fwo', 'no_wo', 'no_termin'] as $k) {
            if (!empty($snap[$k])) return (string) $snap[$k];
        }
        if (!empty($snap['akun'])) {
            return $snap['akun'] . (!empty($snap['plan']) ? ' (' . $snap['plan'] . ')' : '');
        }

        return null;
    }

    private function parseAuditValue($value)
    {
        if (empty($value)) return [];

        $decoded = json_decode($value, true);

        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        if (is_array($decoded) && isset($decoded[0])) {
            return is_array($decoded[0]) ? $decoded[0] : $decoded;
        }

        if (is_array($decoded)) {
            return $decoded;
        }

        return [];
    }

    protected function auditTable(): string
    {
        return '';
    }

    protected function auditExcludeFields(): array
    {
        return ['updated_at', 'created_at'];
    }

    /** Isi dengan nama tabel induk bila History modul ini juga menampilkan perubahan tab anaknya. */
    protected function auditParentTable(): ?string
    {
        return null;
    }

    /** Tabel lama yang row_id-nya sudah id induk (log sebelum kolom parent ada), mis. ['boq'] untuk WO. */
    protected function auditExtraTables(): array
    {
        return [];
    }
}
