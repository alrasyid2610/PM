<?php

namespace App\Http\Controllers;

use App\Support\SyaratSampleTemplate as Tpl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Syarat Sample per Testing Point (tab "Syarat" di detail Testing Point). Syarat dipilih dari master `syarat_sample`
 * lalu nilai isiannya (angka) diisi per Testing Point. Dibaca Lab Sample Reg lewat App\Support\SyaratSample.
 * Mengubah syarat TIDAK mengembalikan approval Testing Point (bukan perubahan struktural).
 */
class TestingPointSyaratController extends Controller
{
    /** Daftar syarat 1 Testing Point beserta teks ter-render & isian (untuk tab Syarat). */
    public function index($id)
    {
        return response()->json(\App\Support\SyaratSample::byPoint((int) $id));
    }

    /** Validasi nilai isian: hanya kunci yang ada di template, bilangan bulat >= 0. */
    private function nilaiDariRequest(Request $request, string $template): array
    {
        $isian = collect(Tpl::parse($template));
        $in    = (array) $request->input('nilai', []);
        $nilai = [];
        foreach ($isian as $i) {
            $v = $in[$i['key']] ?? null;
            if ($v === null || $v === '') continue;
            if (!ctype_digit((string) $v)) {
                abort(response()->json(['message' => "Isian '" . str_replace('_', ' ', $i['key']) . "' harus berupa angka bulat."], 422));
            }
            $nilai[$i['key']] = (int) $v;
        }
        return $nilai;
    }

    public function store(Request $request, $id)
    {
        $tp = DB::table('testing_points')->where('id_testing_point', $id)->whereNull('deleted_at')->first();
        if (!$tp) return response()->json(['message' => 'Testing Point tidak ditemukan'], 404);

        $request->validate(['id_syarat' => 'required|integer']);
        $syarat = DB::table('syarat_sample')->where('id_syarat', $request->id_syarat)->whereNull('deleted_at')->where('is_aktif', 1)->first();
        if (!$syarat) {
            return response()->json(['message' => 'Syarat tidak ditemukan atau sudah nonaktif. Pilih dari Master Syarat Sample.'], 422);
        }
        if (DB::table('testing_point_syarat')->where('id_testing_point', $id)->where('id_syarat', $syarat->id_syarat)->exists()) {
            return response()->json(['message' => 'Syarat ini sudah ada di Testing Point ini.'], 422);
        }

        $nilai  = $this->nilaiDariRequest($request, $syarat->template);
        $urutan = (int) DB::table('testing_point_syarat')->where('id_testing_point', $id)->max('urutan') + 1;

        $row = DB::table('testing_point_syarat')->insertGetId([
            'id_testing_point' => $id,
            'id_syarat'        => $syarat->id_syarat,
            'urutan'           => $urutan,
            'nilai'            => $nilai ? json_encode($nilai) : null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ], 'id_tp_syarat');

        saveAudit('testing_point_syarat', $row, 'create', '', json_encode(['syarat' => Tpl::render($syarat->template, $nilai)]), ['testing_points', $id]);

        return response()->json(['success' => true, 'id' => $row]);
    }

    public function update(Request $request, $id, $row)
    {
        $r = DB::table('testing_point_syarat as t')
            ->join('syarat_sample as s', 's.id_syarat', '=', 't.id_syarat')
            ->where('t.id_tp_syarat', $row)->where('t.id_testing_point', $id)
            ->first(['t.id_tp_syarat', 't.nilai', 's.template']);
        if (!$r) return response()->json(['message' => 'Syarat tidak ditemukan'], 404);

        $nilai  = $this->nilaiDariRequest($request, $r->template);
        $before = Tpl::render($r->template, $r->nilai ? json_decode($r->nilai, true) : []);

        DB::table('testing_point_syarat')->where('id_tp_syarat', $row)->update([
            'nilai'      => $nilai ? json_encode($nilai) : null,
            'updated_at' => now(),
        ]);

        saveAudit('testing_point_syarat', $row, 'update', json_encode(['syarat' => $before]), json_encode(['syarat' => Tpl::render($r->template, $nilai)]), ['testing_points', $id]);

        return response()->json(['success' => true]);
    }

    public function destroy($id, $row)
    {
        $r = DB::table('testing_point_syarat as t')
            ->join('syarat_sample as s', 's.id_syarat', '=', 't.id_syarat')
            ->where('t.id_tp_syarat', $row)->where('t.id_testing_point', $id)
            ->first(['t.nilai', 's.template']);
        if (!$r) return response()->json(['message' => 'Syarat tidak ditemukan'], 404);

        $teks = Tpl::render($r->template, $r->nilai ? json_decode($r->nilai, true) : []);
        DB::table('testing_point_syarat')->where('id_tp_syarat', $row)->delete();
        saveAudit('testing_point_syarat', $row, 'delete', json_encode(['syarat' => $teks]), '', ['testing_points', $id]);

        return response()->json(['success' => true]);
    }
}
