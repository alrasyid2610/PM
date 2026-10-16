<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Master judul Output (tabel `output_judul`) — hanya daftar pilihan. Output menyimpan
 * teks judulnya, bukan id master, jadi master boleh berubah tanpa memengaruhi output.
 */
class OutputJudul
{
    /** Semua judul untuk satu tipe ('pekerjaan' | 'other'), urut abjad. */
    public static function daftar(string $tipe): array
    {
        try {
            return DB::table('output_judul')->where('tipe', $tipe)->orderBy('judul')->pluck('judul')->all();
        } catch (\Throwable $e) {
            // Migration belum dijalankan: daftar kosong, ketik manual tetap bisa
            Log::warning('OutputJudul::daftar gagal: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Pastikan judul ada di master dan kembalikan bentuk bakunya. Pencocokan tidak peka
     * huruf besar-kecil: "lhu air" memakai "LHU Air" yang sudah ada. Judul baru disimpan
     * apa adanya (spasi berlebih dirapikan). Gagal menyimpan master tidak boleh
     * menggagalkan penyimpanan output, jadi kembalikan judul yang sudah dirapikan saja.
     */
    public static function resolve(string $tipe, ?string $judul): string
    {
        $judul = trim(preg_replace('/\s+/u', ' ', (string) $judul));
        if ($judul === '') return '';

        try {
            $ada = DB::table('output_judul')->where('tipe', $tipe)->where('judul', $judul)->value('judul');
            if ($ada !== null) return $ada;

            DB::table('output_judul')->insertOrIgnore([
                'tipe' => $tipe, 'judul' => $judul, 'created_at' => now(), 'updated_at' => now(),
            ]);
            return DB::table('output_judul')->where('tipe', $tipe)->where('judul', $judul)->value('judul') ?? $judul;
        } catch (\Throwable $e) {
            Log::warning('OutputJudul::resolve gagal: ' . $e->getMessage());
            return $judul;
        }
    }
}
