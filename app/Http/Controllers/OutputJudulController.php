<?php

namespace App\Http\Controllers;

use App\Support\OutputJudul;
use Illuminate\Http\Request;

/**
 * Daftar judul AKTIF dari Master Output untuk select di modal Output Pekerjaan / Output Other (WO dan SO).
 * Hanya baca — judul dikelola di menu "Master Output" (OutputMasterController); lihat App\Support\OutputJudul.
 */
class OutputJudulController extends Controller
{
    /** @return array<int,array{id:int,text:string,is_lhu:int}> */
    public function select2(Request $request)
    {
        $tipe = $request->query('tipe') === 'other' ? 'other' : 'pekerjaan';

        return response()->json(OutputJudul::daftar($tipe));
    }
}
