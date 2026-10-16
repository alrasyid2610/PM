<?php

namespace App\Http\Controllers;

use App\Support\OutputJudul;
use Illuminate\Http\Request;

/**
 * Daftar judul Output untuk select2 (Output Pekerjaan & Output Other). Hanya baca —
 * judul baru masuk master otomatis saat output disimpan (lihat OutputJudul::resolve()).
 */
class OutputJudulController extends Controller
{
    public function select2(Request $request)
    {
        $tipe = $request->query('tipe') === 'other' ? 'other' : 'pekerjaan';

        return response()->json(
            collect(OutputJudul::daftar($tipe))->map(fn ($j) => ['id' => $j, 'text' => $j])->values()
        );
    }
}
