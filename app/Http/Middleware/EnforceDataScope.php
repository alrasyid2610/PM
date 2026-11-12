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

        $so  = [];
        $sq  = [];
        $wo  = [];
        $fwo = [];

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
            // ── Operasional (WO/FWO & turunannya) — mengikuti SO induk, sejak 2026-11-12 ──
            case 'work-orders':
            case 'output-pekerjaan':
            case 'fieldwork-boq':
            case 'fieldworks':
            case 'fwo-boq-other':
            case 'fwo-boq-sampling':
            case 'wo-boq-other':
            case 'wo-boq-sampling':
            case 'wo-output-other':
            case 'lab-samples':
            case 'wo-samples':
            case 'lab-sample-regs':
            case 'boq':
                [$so2, $wo, $fwo] = $this->opsViaRoute($prefix, $route->getName(), $p, $request);
                $so = array_merge($so, $so2);
                break;
            default:
                return $next($request);
        }

        foreach (array_unique(array_filter($so)) as $id) {
            if (!DataScope::canSeeSo($id)) {
                return $this->deny($request);
            }
        }
        foreach (array_unique(array_filter($wo)) as $id) {
            if (!DataScope::canSeeWo($id)) {
                return $this->deny($request);
            }
        }
        foreach (array_unique(array_filter($fwo)) as $id) {
            if (!DataScope::canSeeFwo($id)) {
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

    /**
     * Modul Operasional: turunkan parameter rute / body ke [id_so[], id_wo[], id_fwo[]] yang harus boleh dilihat user.
     * Id baris anak (BOQ Other, sample, output, dst.) diturunkan ke WO/FWO induknya.
     */
    private function opsViaRoute(string $prefix, ?string $name, array $p, Request $request): array
    {
        $so = [];
        $wo = [];
        $fwo = [];
        $id = $p['id'] ?? null;

        switch ($prefix) {
            case 'work-orders':
                if ($name === 'work-orders.by-so') {
                    $so[] = $id;                       // di rute ini {id} = id SO
                } else {
                    $wo[] = $id;
                }
                $so[] = $request->input('id_so');      // create / store
                break;

            case 'boq':
                if ($name === 'boq.by-wo') {
                    $wo[] = $id;                       // {id} = id WO
                } elseif ($name === 'boq.section-items') {
                    $wo[] = $this->val('boq', 'id_boq', $id, 'id_wo');
                } elseif ($name === 'boq.history') {
                    $wo[] = $this->val('boq', 'id_boq', $id, 'id_wo');
                } else {
                    $wo[] = $id;                       // show/update/destroy: {id} = id WO
                }
                $wo[] = $p['id_wo'] ?? null;
                $wo[] = $request->input('id_wo');
                break;

            case 'output-pekerjaan':
                $wo[] = $p['id_wo'] ?? ($id ? $this->val('output_pekerjaan', 'id_output', $id, 'id_wo') : null);
                $wo[] = $request->input('id_wo');
                break;

            case 'wo-boq-other':
            case 'wo-boq-sampling':
                $wo[] = $p['id_wo'] ?? ($id ? $this->val('boq_tambahan', 'id_boq_tambahan', $id, 'id_wo') : null);
                $wo[] = $request->input('id_wo');
                break;

            case 'wo-output-other':
                $wo[] = $p['id_wo'] ?? null;
                if ($id) {
                    $row = DB::table('output_tambahan')->where('id_output_tambahan', $id)->first(['id_wo', 'id_so']);
                    $wo[] = $row->id_wo ?? null;
                    $so[] = $row->id_so ?? null;
                }
                $wo[] = $request->input('id_wo');
                $so[] = $request->input('id_so');
                break;

            case 'fieldworks':
                $fwo[] = $id;
                $wo[] = $request->input('id_wo');      // create / store
                break;

            case 'fieldwork-boq':
                $fwo[] = $p['id_fwo'] ?? null;
                break;

            case 'fwo-boq-other':
            case 'fwo-boq-sampling':
                $fwo[] = $p['id_fwo'] ?? ($id ? $this->val('fwo_boq_tambahan', 'id_fwo_boq_tambahan', $id, 'id_fwo') : null);
                $fwo[] = $request->input('id_fwo');
                break;

            case 'lab-samples':
                $fwo[] = $p['id_fwo'] ?? null;
                if (!empty($p['id_fwo_boq'])) {
                    $fwo[] = $this->val('fieldwork_boq', 'id_fwo_boq', $p['id_fwo_boq'], 'id_fwo');
                }
                if ($id) {
                    $fwo[] = $this->val('lab_samples', 'id_lab_sample', $id, 'id_fwo');
                }
                break;

            case 'wo-samples':
                $wo[] = $p['id_wo'] ?? null;
                if ($id) {
                    $s = DB::table('lab_samples')->where('id_lab_sample', $id)->first(['id_wo', 'id_fwo']);
                    $wo[]  = $s->id_wo ?? null;
                    $fwo[] = $s->id_fwo ?? null;
                }
                break;

            case 'lab-sample-regs':
                if ($id) {
                    $wo[] = $this->val('wo_lab_sample_regs', 'id_wo_lab_sample_reg', $id, 'id_wo');
                }
                $wo[] = $request->query('wo');
                break;
        }

        return [$so, $wo, $fwo];
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
