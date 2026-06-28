<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;trait HasAuditHistory
{
    public function history($id)
    {
        $table         = $this->auditTable();
        $excludeFields = $this->auditExcludeFields();

        $logs = DB::table('audit_logs as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.created_by')
            ->where('a.nama_table', $table)
            ->where('a.row_id', $id)
            ->select([
                'a.id',
                'a.action',
                'a.old_value',
                'a.new_value',
                'a.created_at',
                'u.name as created_by_name',
            ])
            ->orderByDesc('a.created_at')
            ->get()
            ->map(function ($log) use ($excludeFields) {

                $old = $this->parseAuditValue($log->old_value);
                $new = $this->parseAuditValue($log->new_value);

                $changes = [];

                if (is_array($old) && is_array($new)) {
                    foreach ($new as $key => $newVal) {
                        if (in_array($key, $excludeFields)) continue;
                        $oldVal = $old[$key] ?? null;
                        if ((string)$oldVal !== (string)$newVal) {
                            $changes[] = [
                                'field'     => $key,
                                'old_value' => $oldVal,
                                'new_value' => $newVal,
                            ];
                        }
                    }
                }

                return [
                    'id'              => $log->id,
                    'action'          => strtolower($log->action),
                    'changes'         => $changes,
                    'total_changes'   => count($changes),
                    'created_by_name' => $log->created_by_name ?? 'System',
                    'created_at'      => $log->created_at,
                ];
            });

        return response()->json($logs);
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
}
