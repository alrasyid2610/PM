<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Perbaikan DATA (bukan skema): teks parameter yang tersimpan double-encoded
 * (mis. "NH₃" tersimpan sebagai "NHâ‚ƒ", akibat input/paste dari sumber yang
 * salah encoding). Ditampilkan jadi simbol aneh di PDF FWO.
 *
 * Cara balik: UTF-8 yang salah-baca sebagai Windows-1252 bisa dikembalikan
 * dengan konversi ke Windows-1252 lalu baca ulang sebagai UTF-8. Hanya baris
 * yang hasilnya valid UTF-8 dan benar-benar berubah yang di-update.
 */
return new class extends Migration
{
    private const COLUMNS = ['kode', 'nama'];

    public function up(): void
    {
        $this->repair(fn(string $s) => $this->decodeMojibake($s));
    }

    public function down(): void
    {
        // Tidak bisa dibalik secara aman (data asli sudah diperbaiki). No-op.
    }

    private function repair(callable $fix): void
    {
        DB::table('testing_parameters')
            ->where(function ($q) {
                foreach (self::COLUMNS as $col) {
                    $q->orWhere($col, 'like', '%â%')->orWhere($col, 'like', '%Ã%');
                }
            })
            ->orderBy('id_testing_parameter')
            ->chunk(200, function ($rows) use ($fix) {
                foreach ($rows as $row) {
                    $update = [];
                    foreach (self::COLUMNS as $col) {
                        $val = $row->{$col} ?? null;
                        if (!is_string($val) || $val === '') continue;
                        $fixed = $fix($val);
                        if ($fixed !== $val) $update[$col] = $fixed;
                    }
                    if ($update) {
                        DB::table('testing_parameters')
                            ->where('id_testing_parameter', $row->id_testing_parameter)
                            ->update($update);
                    }
                }
            });
    }

    private function decodeMojibake(string $s): string
    {
        $bytes = @iconv('UTF-8', 'Windows-1252//IGNORE', $s);
        if ($bytes === false || $bytes === '') return $s;
        return mb_check_encoding($bytes, 'UTF-8') ? $bytes : $s;
    }
};
