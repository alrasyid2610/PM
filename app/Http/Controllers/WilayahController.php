<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WilayahController extends Controller
{
    public function provinces(): JsonResponse
    {
        $data = DB::table('wilayah')
            ->whereRaw("kode NOT LIKE '%.%'")
            ->orderBy('kode')
            ->select('kode as id', 'nama as name')
            ->get();

        return response()->json($data);
    }

    public function children(Request $request): JsonResponse
    {
        $parent = $request->query('kode');

        if (!$parent) return response()->json([]);

        $dotCount = substr_count($parent, '.') + 1;

        $data = DB::table('wilayah')
            ->where('kode', 'LIKE', $parent . '.%')
            ->whereRaw("(LENGTH(kode) - LENGTH(REPLACE(kode, '.', ''))) = ?", [$dotCount])
            ->orderBy('kode')
            ->select('kode as id', 'nama as name')
            ->get();

        return response()->json($data);
    }
}
