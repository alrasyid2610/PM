<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Master Output (menu baru "Master Output"): judul output tidak lagi diketik bebas, tapi dipilih dari master `output_judul`.
 *
 * - `output_judul`: + `is_aktif` (judul nonaktif tetap valid untuk output lama, tapi tidak muncul untuk input baru)
 *                   + `is_lhu`   (default saklar LHU untuk judul bertipe pekerjaan; menggantikan deteksi kata "LHU" di JS).
 * - `output_pekerjaan` & `output_tambahan`: + `id_output_judul` (→ output_judul). Kolom teks lama (`judul_output` /
 *   `nama_item`) DIPERTAHANKAN sebagai salinan teks — Termin, SO, notifikasi, dan riwayat membacanya langsung.
 *
 * Data lama: id diisi dengan mencocokkan teks lama ke master per tipe (tidak peka huruf besar-kecil). Teks yang belum ada
 * di master dimasukkan ke master dengan status NONAKTIF (arsip) lalu ditautkan, jadi tidak ada baris yang tertinggal
 * tanpa id. Teks lama TIDAK diubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('output_judul', function (Blueprint $table) {
            $table->boolean('is_aktif')->default(true)->after('judul');
            $table->boolean('is_lhu')->default(false)->after('is_aktif');
        });

        Schema::table('output_pekerjaan', function (Blueprint $table) {
            $table->unsignedBigInteger('id_output_judul')->nullable()->index();
        });

        Schema::table('output_tambahan', function (Blueprint $table) {
            $table->unsignedBigInteger('id_output_judul')->nullable()->index();
        });

        $arsip = 0;
        $arsip += $this->isiId('output_pekerjaan', 'judul_output', 'pekerjaan');
        $arsip += $this->isiId('output_tambahan', 'nama_item', 'other');

        // Penanda LHU di master: judul bertipe pekerjaan yang mengandung kata LHU (aturan lama di JS)
        foreach (DB::table('output_judul')->where('tipe', 'pekerjaan')->get(['id_output_judul', 'judul']) as $m) {
            if (preg_match('/\bLHU\b/i', (string) $m->judul)) {
                DB::table('output_judul')->where('id_output_judul', $m->id_output_judul)->update(['is_lhu' => 1]);
            }
        }

        $sisa = DB::table('output_pekerjaan')->whereNull('id_output_judul')->count()
            + DB::table('output_tambahan')->whereNull('id_output_judul')->count();
        Log::info("Master Output: {$arsip} judul dari output lama dimasukkan ke master sebagai nonaktif (arsip); output tanpa id tersisa: {$sisa}");
    }

    /**
     * Tautkan semua baris `$tabel` ke master tipe `$tipe`. Judul yang belum ada di master dimasukkan sebagai nonaktif.
     * Mengembalikan jumlah judul master baru yang dibuat.
     */
    private function isiId(string $tabel, string $kolom, string $tipe): int
    {
        $baru = DB::table($tabel)
            ->whereNotNull($kolom)->whereRaw("TRIM($kolom) <> ''")
            ->selectRaw("DISTINCT TRIM($kolom) AS teks")
            ->pluck('teks')
            ->filter(fn ($t) => !DB::table('output_judul')->where('tipe', $tipe)->where('judul', $t)->exists())
            ->all();

        foreach ($baru as $teks) {
            DB::table('output_judul')->insertOrIgnore([
                'tipe' => $tipe, 'judul' => $teks, 'is_aktif' => 0, 'is_lhu' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // collate eksplisit: kolom teks output & master bisa berbeda collation (unicode_ci vs general_ci)
        DB::update("
            UPDATE `$tabel` o
            JOIN `output_judul` m ON m.tipe = ? AND m.judul = TRIM(o.`$kolom`) COLLATE utf8mb4_general_ci
            SET o.id_output_judul = m.id_output_judul
            WHERE o.id_output_judul IS NULL
        ", [$tipe]);

        return count($baru);
    }

    public function down(): void
    {
        Schema::table('output_tambahan', function (Blueprint $table) {
            $table->dropIndex(['id_output_judul']);
            $table->dropColumn('id_output_judul');
        });

        Schema::table('output_pekerjaan', function (Blueprint $table) {
            $table->dropIndex(['id_output_judul']);
            $table->dropColumn('id_output_judul');
        });

        Schema::table('output_judul', function (Blueprint $table) {
            $table->dropColumn(['is_aktif', 'is_lhu']);
        });
    }
};
