<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

if (!function_exists('saveAudit')) {

    function saveAudit($table, $rowId, $action, $before = null, $after = null)
    {
        DB::table('audit_logs')->insert([
            'nama_table' => $table,
            'row_id' => $rowId,
            'action' => $action,
            'old_value' => $before ?: null,
            'new_value' => $after ?: null,
            'created_by' => Auth::id(),
            'created_at' => now()
        ]);
    }
}
