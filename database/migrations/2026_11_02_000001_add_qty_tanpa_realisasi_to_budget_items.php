<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Tanpa realisasi" jadi per-qty (2026-11-02): satu item tetap 1 baris (mis. qty 4), tapi sebagian qty-nya
 * (mis. 2 unit milik sendiri) tidak perlu realisasi. `qty_tanpa_realisasi` = jumlah unit itu,
 * `nominal_tanpa_realisasi` = nilainya (diturunkan server tiap simpan), `tanpa_realisasi` = turunan (qty > 0).
 * Penanda lama (boolean saja) di-reset — fokus ke data ke depan.
 */
return new class extends Migration
{
    private array $tables = ['wo_budget_items', 'fwo_budget_items', 'sq_wo_budget_items', 'sq_fwo_budget_items'];

    public function up(): void
    {
        foreach ($this->tables as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->decimal('qty_tanpa_realisasi', 12, 2)->default(0)->after('tanpa_realisasi');
                $table->unsignedBigInteger('nominal_tanpa_realisasi')->default(0)->after('qty_tanpa_realisasi');
            });

            DB::table($t)->where('tanpa_realisasi', 1)->update([
                'tanpa_realisasi' => 0, 'alasan_tanpa_realisasi' => null, 'ditandai_oleh' => null, 'ditandai_at' => null,
            ]);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropColumn(['qty_tanpa_realisasi', 'nominal_tanpa_realisasi']);
            });
        }
    }
};
