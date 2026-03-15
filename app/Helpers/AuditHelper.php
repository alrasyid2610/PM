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
            'old_value' => $before ? json_encode($before) : null,
            'new_value' => $after ? json_encode($after) : null,
            'created_by' => Auth::id(),
            'created_at' => now()
        ]);
    }
}
