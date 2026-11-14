<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Master judul Output (tabel `output_judul`, dikelola di menu "Master Output").
 *
 * Judul output HANYA boleh berasal dari master (tidak lagi diketik bebas / ditambah otomatis saat simpan — sejak 2026-11-14).
 * Output menyimpan `id_output_judul` + salinan teks judulnya (`judul_output` / `nama_item`), karena Termin, SO, notifikasi,
 * dan riwayat membaca teks itu langsung. Tipe master: 'pekerjaan' (Output Pekerjaan) | 'other' (Output Other).
 */
class OutputJudul
{
    /** Judul AKTIF untuk satu tipe, urut abjad: [['id','text','is_lhu'], ...] (untuk select di modal). */
    public static function daftar(string $tipe): array
    {
        return DB::table('output_judul')
            ->where('tipe', $tipe)->where('is_aktif', 1)
            ->orderBy('judul')
            ->get(['id_output_judul', 'judul', 'is_lhu'])
            ->map(fn ($m) => ['id' => (int) $m->id_output_judul, 'text' => $m->judul, 'is_lhu' => (int) $m->is_lhu])
            ->all();
    }

    /**
     * Cari baris master yang dipilih untuk output. Mengembalikan baris master (id_output_judul, judul, tipe, is_lhu)
     * atau null bila tidak sah. Syarat: ada di master, tipenya cocok, dan AKTIF — kecuali sama dengan judul yang SUDAH
     * dipakai output itu (`$idSaatIni`), supaya output lama berjudul nonaktif tetap bisa diedit tanpa mengganti judul.
     *
     * `$teks` hanya cadangan bila id tidak dikirim: dicocokkan persis (tidak peka huruf besar-kecil) ke master — judul yang
     * tidak ada di master tidak pernah diterima, jadi typo tidak bisa masuk lewat jalur ini.
     */
    public static function pilih(string $tipe, mixed $id, ?string $teks = null, ?int $idSaatIni = null): ?object
    {
        $q = DB::table('output_judul')->where('tipe', $tipe);

        if ($id !== null && $id !== '') {
            $q->where('id_output_judul', (int) $id);
        } else {
            $teks = trim(preg_replace('/\s+/u', ' ', (string) $teks));
            if ($teks === '') {
                return null;
            }
            $q->where('judul', $teks);
        }

        $m = $q->first(['id_output_judul', 'judul', 'tipe', 'is_lhu', 'is_aktif']);
        if (!$m) {
            return null;
        }

        if (!$m->is_aktif && (int) $m->id_output_judul !== (int) $idSaatIni) {
            return null;
        }

        return $m;
    }

    /** Pesan penolakan seragam bila judul tidak sah. */
    public static function pesanTidakSah(): string
    {
        return 'Judul output harus dipilih dari Master Output (judul tidak ditemukan atau sudah nonaktif). '
            . 'Minta admin menambahkannya di menu Master Output bila belum ada.';
    }
}
