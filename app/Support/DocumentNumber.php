<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Penomoran dokumen tunggal untuk semua modul: KODE.YY.HURUF.NNNN
 * (SQ, SQWO, SQFWO, SO, WO, FWO, SC, ST), contoh SO.26.A.0183.
 *
 * - Urutan angka reset tiap tahun; huruf naik (A → B …) setelah 9999 dalam tahun yang sama.
 * - Nomor dihitung dari seluruh baris tabel (termasuk yang soft delete — DB::table tidak
 *   memfilter deleted_at), jadi nomor dokumen yang sudah dihapus tidak dipakai ulang.
 * - Format lama KODE-YY-NNNN (SO-26-0182, ST-26-00116) tidak diubah; angkanya dibaca supaya
 *   nomor baru melanjutkan, bukan mulai dari 0001 lagi.
 */
class DocumentNumber
{
    public const MAX_PER_LETTER = 9999;
    public const MAX_ATTEMPTS = 5;

    /** Nomor berikutnya untuk $kode di $table.$column pada tahun $at (default sekarang). */
    public static function next(string $table, string $column, string $kode, ?CarbonInterface $at = null): string
    {
        $yy        = ($at ?? now())->format('y');
        $prefixLen = strlen($kode) + 4; // panjang "KODE.YY." / "KODE-YY-"
        $col       = '`' . str_replace('`', '', $column) . '`';

        $newest = DB::table($table)
            ->whereRaw("$col REGEXP ?", ['^' . $kode . '\\.' . $yy . '\\.[A-Z]\\.[0-9]+$'])
            ->orderByRaw("SUBSTRING($col, ?, 1) DESC, CAST(SUBSTRING($col, ?) AS UNSIGNED) DESC", [$prefixLen + 1, $prefixLen + 3])
            ->value($column);

        $letter = 'A';
        $number = 0;
        if ($newest) {
            $letter = $newest[$prefixLen];
            $number = (int) substr($newest, $prefixLen + 2);
        }

        // Lanjutkan angka format lama hanya selama masih di huruf A (huruf berikutnya pasti sudah jauh melewatinya).
        if ($letter === 'A') {
            $legacy = DB::table($table)
                ->whereRaw("$col REGEXP ?", ['^' . $kode . '-' . $yy . '-[0-9]+$'])
                ->orderByRaw("CAST(SUBSTRING($col, ?) AS UNSIGNED) DESC", [$prefixLen + 1])
                ->value($column);
            if ($legacy) {
                $number = max($number, (int) substr($legacy, $prefixLen));
            }
        }

        $number++;
        if ($number > self::MAX_PER_LETTER) {
            if ($letter === 'Z') {
                throw new \RuntimeException("Nomor {$kode} tahun {$yy} sudah habis (Z.9999).");
            }
            $letter = chr(ord($letter) + 1);
            $number = 1;
        }

        return sprintf('%s.%s.%s.%04d', $kode, $yy, $letter, $number);
    }

    /**
     * Insert satu baris dengan nomor dokumen otomatis. Kalau dua permintaan mengambil nomor yang
     * sama dan unique index menolak, nomor diambil ulang (maksimal MAX_ATTEMPTS kali).
     * $data tidak perlu memuat kolom nomor. Nomor yang dipakai dikembalikan lewat $no.
     */
    public static function insertGetId(string $table, string $column, string $kode, array $data, ?string &$no = null): int
    {
        return self::retry(function () use ($table, $column, $kode, $data, &$no) {
            $no = self::next($table, $column, $kode);
            return (int) DB::table($table)->insertGetId(array_merge($data, [$column => $no]));
        });
    }

    /** Jalankan $callback; ulangi bila unique index menolak (nomor bentrok). */
    public static function retry(callable $callback)
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return $callback();
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= self::MAX_ATTEMPTS) {
                    throw $e;
                }
            }
        }
    }
}
