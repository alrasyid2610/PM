<?php

namespace App\Support\DataImport;

/**
 * Satu jenis import di menu Import Data (mis. Sampling ENV, Sampling WE). Controller `DataImportController`
 * hanya mengurus alur umum (template, upload, pratinjau, proses, file hasil); semua yang spesifik jenis
 * data — kolom, aturan validasi, cara menyimpan — ada di kelas turunan ini, jadi jenis baru cukup
 * menambah satu kelas + satu baris di `ImportRegistry`.
 *
 * Konteks (`$ctx`): array yang dibuat `prepare()` sekali per proses dan diteruskan ke tiap `validate()`
 * (mis. daftar kode yang sudah ada). `validate()` boleh mengubahnya untuk melacak duplikat di dalam file.
 */
interface ImportDefinition
{
    /** Kunci unik (dipakai di URL & Select2), mis. "sampling-env". */
    public function key(): string;

    public function label(): string;

    /** Kalimat singkat di bawah pilihan jenis (catatan/peringatan). */
    public function note(): ?string;

    /**
     * Kolom template, berurutan: [['key'=>'kode','label'=>'Kode','required'=>true,'width'=>16,'type'=>'String (255)'], ...].
     * `label` = teks header baris 1 di Excel (dicocokkan tanpa peka huruf besar-kecil saat upload);
     * `type` = keterangan tipe data yang dicetak di baris 2 sheet Data.
     */
    public function columns(): array;

    /** Poin-poin aturan untuk sheet "Petunjuk". */
    public function rules(): array;

    /** Satu baris contoh (nilai sesuai urutan `columns()`), dicetak di baris 3 sheet Data. Tidak ikut diimpor. */
    public function example(): array;

    /**
     * Pilihan yang harus diisi user SEBELUM template dibuat (data induk tempat baris-baris ini bernaung), mis.
     * [['key'=>'id_site','label'=>'Site','source'=>'sites']]. `source` = nama daftar opsi di
     * `DataImportController::optionList()`. Kosong = jenis ini berdiri sendiri (tidak ada pilihan tambahan).
     */
    public function contexts(): array;

    /**
     * Periksa isian konteks dari request (`ctx[key]`). Berhasil: ['values' => ['id_site' => 12], 'info' => ['Site' => 'X', ...]]
     * (`info` = teks manusiawi untuk sheet Info & pesan salah-file). Gagal: string pesan.
     */
    public function resolveContext(array $input): array|string;

    /** Siapkan keadaan validasi untuk satu proses (mis. daftar kode yang sudah ada), dari nilai konteks. */
    public function prepare(array $context): array;

    /**
     * Periksa satu baris. Kembalian: ['status' => 'valid'|'skip'|'error', 'message' => ?string, 'clean' => array].
     * `skip` = baris sah tapi tidak dimasukkan (mis. duplikat), `error` = isian salah.
     */
    public function validate(array $raw, array &$ctx): array;

    /** Simpan satu baris valid (+ audit). Mengembalikan id baris baru. */
    public function insert(array $clean, array $context): int;

    /** Dua teks pendek per baris untuk tabel pratinjau (mis. [kode, nama]); judul kolomnya dari `summaryLabels()`. */
    public function summary(array $raw): array;

    /** Judul dua kolom ringkasan pratinjau, mis. ['Kode', 'Nama']. */
    public function summaryLabels(): array;
}
