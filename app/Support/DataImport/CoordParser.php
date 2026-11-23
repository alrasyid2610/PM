<?php

namespace App\Support\DataImport;

/**
 * Parsing nilai sel Excel yang dipakai lebih dari satu jenis import (dipisah dari SamplingPointImport
 * 2026-11-21 supaya BrSiteImport bisa memakai aturan yang sama tanpa duplikasi):
 * - text(): rapikan sel jadi string (angka bulat dari Excel, mis. "1.0", tidak diberi ".0").
 * - koordinat(): desimal ATAU derajat-menit-detik (dengan arah N/S/E/W atau LU/LS/BT/BB) → desimal 8 digit.
 * - yaTidak(): sel "Ya"/"Tidak" (dan variannya) → 1/0, null kalau tidak dikenali.
 */
class CoordParser
{
    /** Sel "Ya"/"Tidak" (atau variannya) → 1/0. Kosong = $kosongBerarti (default 1 = Ya). null = tidak dikenali. */
    public static function yaTidak($v, int $kosongBerarti = 1): ?int
    {
        $t = mb_strtolower(self::text($v));
        if ($t === '') return $kosongBerarti;
        if (in_array($t, ['ya', 'y', 'aktif', '1', 'true'], true)) return 1;
        if (in_array($t, ['tidak', 'tdk', 't', 'nonaktif', 'non-aktif', 'tidak aktif', '0', 'false'], true)) return 0;
        return null;
    }

    /** Sel Excel → teks rapi. Angka bulat (mis. kode 1 yang terbaca 1.0) tidak diberi ".0". */
    public static function text($v): string
    {
        if ($v === null) return '';
        if (is_float($v) && floor($v) == $v && abs($v) < 1e15) return (string) (int) $v;
        return trim((string) $v);
    }

    /**
     * Koordinat → desimal (8 digit). Menerima desimal ("-6.2088", "-6,2088", angka Excel) atau derajat-menit-detik
     * ("6°12'31.7\"S", "6 12 31.7 S", "106°13'8.6\"E") dengan arah N/S/E/W atau LU/LS/BT/BB; S/W (LS/BB) berarti
     * negatif. Kosong → null tanpa error; tak terbaca → null + $err berisi alasan.
     */
    public static function koordinat($v, string $jenis, ?string &$err): ?float
    {
        $err = null;
        if ($v === null || trim((string) $v) === '') return null;
        if (is_int($v) || is_float($v)) return round((float) $v, 8);

        $s = trim((string) $v);
        // Samakan simbol derajat/menit/detik "cantik" hasil copy dari web atau Excel
        $s = str_replace(['º', '˚', '′', '’', '‘', '`', '´', '″', '”', '“'], ['°', '°', "'", "'", "'", "'", "'", '"', '"', '"'], $s);
        $s = str_replace("''", '"', $s);

        $arah = null;
        $pola = '/(?<![A-Za-z])(LU|LS|BT|BB|N|S|E|W)(?![A-Za-z])/i';
        if (preg_match($pola, $s, $m)) {
            $arah = strtoupper($m[1]);
            $s = trim(preg_replace($pola, ' ', $s, 1));
        }
        if ($arah !== null) {
            $cocok = $jenis === 'lat' ? in_array($arah, ['N', 'S', 'LU', 'LS'], true) : in_array($arah, ['E', 'W', 'BT', 'BB'], true);
            if (!$cocok) {
                $err = $jenis === 'lat' ? 'arah harus U/S (N/S), bukan ' . $arah . '.' : 'arah harus T/B (E/W), bukan ' . $arah . '.';
                return null;
            }
        }
        $negArah = in_array($arah, ['S', 'LS', 'W', 'BB'], true);

        // Sisa harus hanya angka dan pemisah derajat/menit/detik, maksimal 3 angka (derajat, menit, detik)
        if (!preg_match_all('/\d+(?:[.,]\d+)?/', $s, $t) || preg_match('/[^\d\s.,°\'"+\-]/u', $s) || count($t[0]) > 3) {
            $err = 'format tidak dikenali.';
            return null;
        }
        $angka = array_map(fn($x) => (float) str_replace(',', '.', $x), $t[0]);
        [$d, $mnt, $dt] = [$angka[0], $angka[1] ?? 0.0, $angka[2] ?? 0.0];
        // Menit/detik >= 60 tetap dihitung (sama seperti input DMS manual di modal Sampling Point,
        // lihat spDmsToDecimal() di business-relations/form.js) — rumus derajat+menit/60+detik/3600
        // tetap menghasilkan nilai yang benar meski formatnya tidak standar.

        $nilai = $d + $mnt / 60 + $dt / 3600;
        // Minus di depan ATAU arah S/W sama-sama berarti negatif (keduanya sekaligus tidak saling meniadakan)
        if (preg_match('/^\s*-/', $s) === 1 || $negArah) $nilai = -$nilai;
        return round($nilai, 8);
    }
}
