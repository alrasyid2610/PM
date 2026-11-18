<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Pembaca Syarat Sample per Testing Point (tabel `testing_point_syarat` + master `syarat_sample`).
 * Dipakai tab Syarat di Testing Point dan Lab Sample Reg (modal registrasi & Form Registrasi PDF).
 * Teks selalu dirender langsung dari master + nilai (tidak dikunci per pendaftaran — keputusan 2026-11-19).
 */
class SyaratSample
{
    /**
     * Syarat 1 Testing Point berurutan: [['id_tp_syarat','id_syarat','urutan','teks','template','isian'=>[['key','default','value']], 'aktif'], ...]
     * `aktif` = status master (syarat yang masternya sudah dinonaktifkan tetap tampil untuk Testing Point yang memakainya).
     */
    public static function byPoint(int $idTp): array
    {
        return self::byPoints([$idTp])[$idTp] ?? [];
    }

    /** Sama dengan byPoint() untuk banyak Testing Point sekaligus: [id_testing_point => [syarat, ...]]. */
    public static function byPoints(array $idTps): array
    {
        $idTps = array_values(array_unique(array_filter(array_map('intval', $idTps))));
        if (!$idTps) return [];

        $rows = DB::table('testing_point_syarat as t')
            ->join('syarat_sample as s', 's.id_syarat', '=', 't.id_syarat')
            ->whereIn('t.id_testing_point', $idTps)
            ->whereNull('s.deleted_at')
            ->orderBy('t.id_testing_point')->orderBy('t.urutan')->orderBy('t.id_tp_syarat')
            ->get(['t.id_tp_syarat', 't.id_testing_point', 't.id_syarat', 't.urutan', 't.nilai', 's.template', 's.is_aktif']);

        $out = [];
        foreach ($rows as $r) {
            $nilai = $r->nilai ? (json_decode($r->nilai, true) ?: []) : [];
            $isian = array_map(fn ($i) => [
                'key'     => $i['key'],
                'default' => $i['default'],
                'value'   => $nilai[$i['key']] ?? null,
            ], SyaratSampleTemplate::parse($r->template));

            $out[(int) $r->id_testing_point][] = [
                'id_tp_syarat' => (int) $r->id_tp_syarat,
                'id_syarat'    => (int) $r->id_syarat,
                'urutan'       => (int) $r->urutan,
                'template'     => $r->template,
                'isian'        => $isian,
                'teks'         => SyaratSampleTemplate::render($r->template, $nilai),
                'aktif'        => (bool) $r->is_aktif,
            ];
        }

        return $out;
    }
}
