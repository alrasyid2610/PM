<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

if (!function_exists('saveAudit')) {

    /**
     * Catat perubahan ke audit_logs.
     *
     * $parent = [tabel_induk, id_induk] bila record ini adalah bagian (tab) dari modul induk,
     * supaya perubahannya ikut tampil di History induk (mis. ['work_orders', $idWo] untuk BOQ).
     */
    function saveAudit($table, $rowId, $action, $before = null, $after = null, ?array $parent = null)
    {
        // Update yang tidak mengubah apa pun (selain updated_at) tidak dicatat
        if (strtolower((string) $action) === 'update' && $before && $after && auditTanpaPerubahan($before, $after)) {
            return;
        }

        static $punyaParent = null;
        $punyaParent ??= Schema::hasColumn('audit_logs', 'parent_table');

        $row = [
            'nama_table' => $table,
            'row_id' => $rowId,
            'action' => $action,
            'old_value' => $before ?: null,
            'new_value' => $after ?: null,
            'created_by' => Auth::id(),
            'created_at' => now()
        ];

        // Kolom parent baru ada setelah migration dijalankan; sebelum itu jangan sampai audit gagal
        if ($punyaParent && $parent) {
            $row['parent_table'] = $parent[0];
            $row['parent_id'] = $parent[1];
        }

        DB::table('audit_logs')->insert($row);
    }
}

if (!function_exists('auditTanpaPerubahan')) {

    /** True bila snapshot sebelum & sesudah (JSON) sama setelah `updated_at` diabaikan. */
    function auditTanpaPerubahan($before, $after): bool
    {
        $strip = function ($v) use (&$strip) {
            if (!is_array($v)) return $v;
            unset($v['updated_at']);
            return array_map($strip, $v);
        };

        $b = json_decode($before, true);
        $a = json_decode($after, true);
        if (!is_array($b) || !is_array($a)) return false;

        return $strip($b) == $strip($a);
    }
}
