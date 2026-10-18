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
