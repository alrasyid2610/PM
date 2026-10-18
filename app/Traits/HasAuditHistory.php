<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait HasAuditHistory
{
    /** Nama bagian (tab) tiap tabel anak di History modul induk. */
    private const AUDIT_BAGIAN = [
        'work_orders'       => 'Informasi',
        'boq'               => 'BOQ',
        'boq_tambahan'      => 'BOQ Other',
        'output_pekerjaan'  => 'Output Pekerjaan',
        'output_tambahan'   => 'Output Other',
        'wo_budgets'        => 'Budget — Plan',
        'wo_budget_actuals' => 'Budget — Realisasi',
        'lab_samples'       => 'Lab Sample Reg',
    ];

    /** Kolom kunci/teknis tabel anak yang tidak perlu tampil sebagai perubahan. */
    private const AUDIT_EXCLUDE_ANAK = [
        'id_boq_tambahan', 'id_output_tambahan', 'id_output', 'id_budget', 'id_actual',
        'id_lab_sample', 'id_wo', 'id_budget_item', 'deleted_at', 'akun', 'plan',
    ];

    public function history($id)
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
                    $changes = $this->auditTerjemahkanId($changes);
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
                    $hasil['bagian'] = $this->auditBagian($log->nama_table, $snap);
                    $hasil['label']  = $anak ? $this->auditLabel($snap) : null;
                }

                return $hasil;
            });

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

    /** Ganti nilai id pada daftar perubahan dengan namanya (nilai yang tidak ditemukan dibiarkan). */
    private function auditTerjemahkanId(array $changes): array
    {
        foreach ($changes as &$c) {
            $peta = self::AUDIT_ID_KE_NAMA[$c['field']] ?? null;
            if (!$peta) continue;

            foreach (['old_value', 'new_value'] as $k) {
                if ($c[$k] === null || $c[$k] === '') continue;
                $nama = DB::table($peta[0])->where($peta[1], $c[$k])->value($peta[2]);
                if ($nama !== null) $c[$k] = $nama;
            }
        }

        return $changes;
    }

    private function auditBagian(string $namaTable, $snap): string
    {
        if ($namaTable === 'boq_tambahan' && is_array($snap) && ($snap['jenis'] ?? null) === 'sampling') {
            return 'BOQ Sampling';
        }

        return self::AUDIT_BAGIAN[$namaTable] ?? $namaTable;
    }

    /** Penamaan record anak (item/plan/sample) dari snapshot-nya. */
    private function auditLabel($snap): ?string
    {
        if (!is_array($snap)) return null;

        foreach (['nama_item', 'judul_output', 'label', 'no_sample'] as $k) {
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
