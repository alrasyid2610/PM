<?php

namespace App\Http\Middleware;

use App\Support\DataScope;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penjaga rute berbasis ID untuk segregasi data SO & SQ: menolak (403) akses ke SO/SQ (dan anak-anaknya)
 * milik orang di luar hierarki user. Filter daftar ada di controller lewat App\Support\DataScope.
 */
class EnforceDataScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        if (!auth()->check() || !$route) {
            return $next($request);
        }

        $prefix = explode('/', trim($request->path(), '/'))[0] ?? '';
        $p      = $route->parameters();

        $so = [];
        $sq = [];

        switch ($prefix) {
            case 'sales-orders':
                $so[] = $p['id'] ?? null;
                break;
            case 'so-output-other':
                $so[] = $p['id_so'] ?? $this->val('output_tambahan', 'id_output_tambahan', $p['id'] ?? null, 'id_so');
                $so[] = $request->input('id_so');
                break;
            case 'sales-quotations':
                $sq[] = $p['id'] ?? null;
                $sq[] = $p['other'] ?? null;
                break;
            case 'sq-work-orders':
                $sq[] = $p['id_sq'] ?? $this->val('sq_work_orders', 'id_sq_wo', $p['id'] ?? null, 'id_sq');
                $sq[] = $request->input('id_sq');
                break;
            case 'sq-boq':
            case 'sq-boq-tambahan':
            case 'sq-wo-budgets':
            case 'sq-fieldworks':
            case 'sq-fwo-boq':
            case 'sq-fwo-budgets':
                $sq = array_merge($sq, $this->sqViaChildren($prefix, $p, $request));
                break;
            default:
                return $next($request);
        }

        foreach (array_unique(array_filter($so)) as $id) {
            if (!DataScope::canSeeSo($id)) {
                return $this->deny($request);
            }
        }
        foreach (array_unique(array_filter($sq)) as $id) {
            if (!DataScope::canSeeSq($id)) {
                return $this->deny($request);
            }
        }

        return $next($request);
    }

    /** id_sq dari parameter rute / body untuk rute anak SQ WO / SQ FWO / budget. */
    private function sqViaChildren(string $prefix, array $p, Request $request): array
    {
        $idWo  = $p['id_sq_wo'] ?? $request->input('id_sq_wo');
        $idFwo = $p['id_sq_fwo'] ?? $request->input('id_sq_fwo');
        $id    = $p['id'] ?? null;

        // Id baris anak (bukan id induk) → turunkan ke SQ WO / SQ FWO induknya
        if ($id) {
            switch ($prefix) {
                case 'sq-boq-tambahan':
                    $idWo = $idWo ?: $this->val('sq_boq_tambahan', 'id_sq_boq_tambahan', $id, 'id_sq_wo');
                    break;
                case 'sq-wo-budgets':
                    $idWo = $idWo ?: $this->val('sq_wo_budgets', 'id_sq_budget', $id, 'id_sq_wo');
                    break;
                case 'sq-fieldworks':
                    $idWo = $idWo ?: $this->val('sq_fieldworks', 'id_sq_fwo', $id, 'id_sq_wo');
                    break;
                case 'sq-fwo-budgets':
                    $idFwo = $idFwo ?: $this->val('sq_fwo_budgets', 'id_sq_budget', $id, 'id_sq_fwo');
                    break;
            }
        }
        if (!empty($p['id_sq_boq'])) {
            $idWo = $idWo ?: $this->val('sq_boq', 'id_sq_boq', $p['id_sq_boq'], 'id_sq_wo');
        }
        if ($idFwo) {
            $idWo = $idWo ?: $this->val('sq_fieldworks', 'id_sq_fwo', $idFwo, 'id_sq_wo');
        }

        $ids = [];
        if ($idWo) {
            $ids[] = $this->val('sq_work_orders', 'id_sq_wo', $idWo, 'id_sq');
        }
        $ids[] = $request->input('id_sq');

        return $ids;
    }

    private function val(string $table, string $pk, $id, string $col)
    {
        if (!$id) {
            return null;
        }

        return DB::table($table)->where($pk, $id)->value($col);
    }

    private function deny(Request $request): Response
    {
        $msg = 'Anda tidak memiliki akses ke data ini (bukan data PIC Anda / bawahan Anda).';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $msg], 403);
        }

        abort(403, $msg);
    }
}
