<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait HasAuditHistoryWithLines
{
    public function history($id)
    {
        return $this->historyById($id);
    }

    public function historyById($id)
    {
        $table         = $this->auditTable();
        $excludeFields = $this->auditExcludeFields();
        $linesPK       = $this->auditLinesPrimaryKey();
        $linesExclude  = $this->auditLinesExcludeFields();

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
            ->map(function ($log) use ($excludeFields, $linesPK, $linesExclude) {

                $old = $this->parseAuditValue($log->old_value);
                $new = $this->parseAuditValue($log->new_value);

                // --- Master field changes ---
                $masterChanges = [];
                foreach ($new as $key => $newVal) {
                    if ($key === '_lines') continue;
                    if (in_array($key, $excludeFields)) continue;
                    $oldVal = $old[$key] ?? null;
                    if ((string)$oldVal !== (string)$newVal) {
                        $masterChanges[] = [
                            'field'     => $key,
                            'old_value' => $oldVal,
                            'new_value' => $newVal,
                        ];
                    }
                }

                // --- Lines diff ---
                $linesDiff = $this->computeLinesDiff(
                    $old['_lines'] ?? [],
                    $new['_lines'] ?? [],
                    $linesPK,
                    $linesExclude
                );

                $totalChanges = count($masterChanges)
                    + count($linesDiff['added'])
                    + count($linesDiff['removed'])
                    + count($linesDiff['modified']);

                return [
                    'id'             => $log->id,
                    'action'         => strtolower($log->action),
                    'master_changes' => $masterChanges,
                    'lines_diff'     => $linesDiff,
                    'total_changes'  => $totalChanges,
                    'created_by_name'=> $log->created_by_name ?? 'System',
                    'created_at'     => $log->created_at,
                ];
            });

        return response()->json($logs);
    }

    private function computeLinesDiff(array $oldLines, array $newLines, string $pk, array $exclude): array
    {
        $oldById = collect($oldLines)->keyBy($pk);
        $newById = collect($newLines)->keyBy($pk);

        $added = $newById->diffKeys($oldById)->values()->toArray();

        $removed = $oldById->diffKeys($newById)->values()->toArray();

        $labelField = $this->auditLinesLabelField();

        $modified = [];
        foreach ($newById as $pkVal => $newLine) {
            if (!$oldById->has($pkVal)) continue;
            $oldLine = $oldById[$pkVal];
            $changes = [];
            foreach ($newLine as $key => $val) {
                if (in_array($key, $exclude)) continue;
                if ((string)($oldLine[$key] ?? null) !== (string)$val) {
                    $changes[] = [
                        'field'     => $key,
                        'old_value' => $oldLine[$key] ?? null,
                        'new_value' => $val,
                    ];
                }
            }
            if (!empty($changes)) {
                $modified[] = [
                    'pk'      => $pkVal,
                    'label'   => $newLine[$labelField] ?? $newLine['nomor'] ?? "#$pkVal",
                    'changes' => $changes,
                ];
            }
        }

        return compact('added', 'removed', 'modified');
    }

    private function parseAuditValue($value): array
    {
        if (empty($value)) return [];

        $decoded = json_decode($value, true);

        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        if (is_array($decoded) && isset($decoded[0]) && is_array($decoded[0])) {
            return $decoded[0];
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

    protected function auditLinesTable(): string
    {
        return '';
    }

    protected function auditLinesForeignKey(): string
    {
        return '';
    }

    protected function auditLinesPrimaryKey(): string
    {
        return 'id';
    }

    protected function auditLinesExcludeFields(): array
    {
        return ['updated_at', 'created_at'];
    }

    protected function auditLinesLabelField(): string
    {
        return 'nomor';
    }
}
