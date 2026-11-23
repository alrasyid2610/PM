<?php

namespace App\Support\DataImport;

use Illuminate\Support\Facades\DB;

/**
 * Validasi berjenjang Provinsi/Kota/Kecamatan/Kelurahan ke master `wilayah` (kode hierarkis dipisah
 * titik, mis. "11" > "11.01" > "11.01.01" > "11.01.01.2001" — lihat `WilayahController`) untuk
 * `BrSiteImport` (sejak 2026-11-22, permintaan user supaya integritas data wilayah terjaga saat
 * import — beda dari form manual "+ Site" yang tidak memvalidasi ke master sama sekali).
 *
 * Pencocokan toleran sama seperti dropdown UI (`wilayahEngine.js` → `normalize()`): huruf kecil,
 * titik/koma diabaikan, spasi ganda dirapikan — dicoba persis dulu baru toleran. Hasil disimpan
 * dengan EJAAN RESMI dari master (bukan ketikan file) supaya dropdown berjenjang di form edit Site
 * langsung mengenalinya (tidak jadi opsi "legacy").
 *
 * Kota/Kabupaten di master SELALU berprefiks "Kota "/"Kabupaten " (mis. "Kota Semarang", "Kabupaten
 * Semarang" — DUA baris beda untuk 1 nama kota, fix 2026-11-22 ditemukan lewat uji user yang mengisi
 * cuma "Semarang"). Tahap ke-3 pencocokan membuang prefix itu lalu coba lagi, TAPI kalau hasilnya
 * cocok dengan lebih dari 1 baris (seperti kasus Semarang di atas), dianggap AMBIGU dan dikembalikan
 * sebagai error yang menyebut semua kemungkinan — TIDAK pernah menebak salah satu secara diam-diam.
 *
 * Aturan: ke-4 kolom boleh kosong semua, tapi kalau diisi, level INDUKnya wajib ikut diisi & valid
 * lebih dulu (tidak boleh loncat, mis. isi Kecamatan tanpa Kota/Kabupaten).
 *
 * Tabel `wilayah` ~91 ribu baris — sengaja TIDAK dimuat semua ke memori; tiap level di-query langsung
 * (kode = PK, pencarian anak pakai prefix `kode LIKE 'parent.%'`) dengan `$cache` per-proses supaya
 * baris yang berulang memakai kota/kecamatan yang sama tidak query ulang.
 */
class WilayahResolver
{
    private static function normalize(?string $s): string
    {
        $s = mb_strtolower(trim((string) $s));
        $s = str_replace(['.', ','], ' ', $s);
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    private static function dots(string $kode): int
    {
        return substr_count($kode, '.');
    }

    /** Buang prefix administratif ("Kota"/"Kabupaten"/"Kab") di depan nama kota/kabupaten yang sudah dinormalisasi. */
    private static function tanpaPrefixAdmin(string $normalized): string
    {
        return (string) preg_replace('/^(kota|kabupaten|kab)\s+/', '', $normalized);
    }

    /**
     * Cari 1 anak dari $parentKode (null = level 0/Provinsi) yang namanya cocok dengan $nama.
     * 3 tahap: (1) persis, (2) toleran (normalize), (3) tanpa prefix "Kota"/"Kabupaten" — khusus
     * tahap 3, kalau cocok dengan LEBIH DARI 1 baris (mis. "Semarang" cocok dengan "Kota Semarang"
     * DAN "Kabupaten Semarang"), dianggap AMBIGU — tidak boleh menebak salah satu.
     * @return array{status:'found',row:object}|array{status:'ambigu',opsi:string[]}|array{status:'none'}
     */
    private static function cari(?string $parentKode, string $nama, array &$cache): array
    {
        $kunci = ($parentKode ?? '-') . '|' . self::normalize($nama);
        if (array_key_exists($kunci, $cache)) return $cache[$kunci];

        $q = DB::table('wilayah');
        if ($parentKode === null) {
            $q->whereRaw("kode NOT LIKE '%.%'");
        } else {
            $q->where('kode', 'like', $parentKode . '.%')
                ->whereRaw("(LENGTH(kode) - LENGTH(REPLACE(kode, '.', ''))) = ?", [self::dots($parentKode) + 1]);
        }

        $kandidat = $q->get(['kode', 'nama']);
        $target = self::normalize($nama);

        $hasil = $kandidat->first(fn($r) => trim($r->nama) === trim($nama))
            ?? $kandidat->first(fn($r) => self::normalize($r->nama) === $target);
        if ($hasil) return $cache[$kunci] = ['status' => 'found', 'row' => $hasil];

        // Tahap 3: tanpa prefix "Kota"/"Kabupaten" (mis. user ketik "Semarang" saja)
        $targetInti = self::tanpaPrefixAdmin($target);
        $cocokInti = $kandidat->filter(fn($r) => self::tanpaPrefixAdmin(self::normalize($r->nama)) === $targetInti)->values();
        if ($cocokInti->count() === 1) {
            return $cache[$kunci] = ['status' => 'found', 'row' => $cocokInti->first()];
        }
        if ($cocokInti->count() > 1) {
            return $cache[$kunci] = ['status' => 'ambigu', 'opsi' => $cocokInti->pluck('nama')->all()];
        }

        return $cache[$kunci] = ['status' => 'none'];
    }

    /**
     * @return array{provinsi:?string,kota:?string,kecamatan:?string,kelurahan:?string}|string
     *         Array nama resmi (null = memang kosong) kalau valid, atau string pesan error (siap tampil ke user).
     */
    public static function resolve(?string $provinsi, ?string $kota, ?string $kecamatan, ?string $kelurahan, array &$cache): array|string
    {
        $provinsi = trim((string) $provinsi) ?: null;
        $kota = trim((string) $kota) ?: null;
        $kecamatan = trim((string) $kecamatan) ?: null;
        $kelurahan = trim((string) $kelurahan) ?: null;

        $out = ['provinsi' => null, 'kota' => null, 'kecamatan' => null, 'kelurahan' => null];
        $kodeProv = $kodeKota = $kodeKec = null;

        if ($provinsi !== null) {
            $r = self::cari(null, $provinsi, $cache);
            if ($r['status'] === 'ambigu') return 'Provinsi "' . $provinsi . '" ada ' . count($r['opsi']) . ' kemungkinan: ' . implode(', ', $r['opsi']) . '. Tulis lebih lengkap untuk memperjelas.';
            if ($r['status'] === 'none') return 'Provinsi "' . $provinsi . '" tidak ditemukan di master wilayah.';
            $out['provinsi'] = $r['row']->nama;
            $kodeProv = $r['row']->kode;
        }

        if ($kota !== null) {
            if ($kodeProv === null) return 'Kolom Kota/Kabupaten diisi ("' . $kota . '") tapi Provinsi kosong — isi Provinsi dulu.';
            $r = self::cari($kodeProv, $kota, $cache);
            if ($r['status'] === 'ambigu') return 'Kota/Kabupaten "' . $kota . '" ada ' . count($r['opsi']) . ' kemungkinan di Provinsi "' . $out['provinsi'] . '": ' . implode(', ', $r['opsi']) . '. Tulis lengkap dengan kata "Kota" atau "Kabupaten" di depan untuk memperjelas.';
            if ($r['status'] === 'none') return 'Kota/Kabupaten "' . $kota . '" tidak ditemukan di Provinsi "' . $out['provinsi'] . '" (sesuai master wilayah).';
            $out['kota'] = $r['row']->nama;
            $kodeKota = $r['row']->kode;
        }

        if ($kecamatan !== null) {
            if ($kodeKota === null) return 'Kolom Kecamatan diisi ("' . $kecamatan . '") tapi Kota/Kabupaten kosong — isi Kota/Kabupaten dulu.';
            $r = self::cari($kodeKota, $kecamatan, $cache);
            if ($r['status'] === 'ambigu') return 'Kecamatan "' . $kecamatan . '" ada ' . count($r['opsi']) . ' kemungkinan di "' . $out['kota'] . '": ' . implode(', ', $r['opsi']) . '. Tulis lebih lengkap untuk memperjelas.';
            if ($r['status'] === 'none') return 'Kecamatan "' . $kecamatan . '" tidak ditemukan di Kota/Kabupaten "' . $out['kota'] . '" (sesuai master wilayah).';
            $out['kecamatan'] = $r['row']->nama;
            $kodeKec = $r['row']->kode;
        }

        if ($kelurahan !== null) {
            if ($kodeKec === null) return 'Kolom Kelurahan diisi ("' . $kelurahan . '") tapi Kecamatan kosong — isi Kecamatan dulu.';
            $r = self::cari($kodeKec, $kelurahan, $cache);
            if ($r['status'] === 'ambigu') return 'Kelurahan "' . $kelurahan . '" ada ' . count($r['opsi']) . ' kemungkinan di "' . $out['kecamatan'] . '": ' . implode(', ', $r['opsi']) . '. Tulis lebih lengkap untuk memperjelas.';
            if ($r['status'] === 'none') return 'Kelurahan "' . $kelurahan . '" tidak ditemukan di Kecamatan "' . $out['kecamatan'] . '" (sesuai master wilayah).';
            $out['kelurahan'] = $r['row']->nama;
        }

        return $out;
    }
}
