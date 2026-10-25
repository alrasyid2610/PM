<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Item Budget Plan punya Qty, Unit (master `satuan`), dan Harga Satuan (2026-10-26).
 * `nominal_budget` tetap TOTAL item (= qty × harga_satuan), jadi semua SUM/laporan lama tidak berubah.
 * Data lama: qty 1, harga_satuan = nominal_budget, tanpa unit.
 */
return new class extends Migration
{
    private array $tables = ['wo_budget_items', 'fwo_budget_items', 'sq_wo_budget_items', 'sq_fwo_budget_items'];

    public function up(): void
    {
        foreach ($this->tables as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->decimal('qty', 12, 2)->default(1)->after('nominal_budget');
                $table->unsignedInteger('id_satuan')->nullable()->after('qty');
                $table->unsignedBigInteger('harga_satuan')->default(0)->after('id_satuan');
            });
            DB::table($t)->update(['harga_satuan' => DB::raw('nominal_budget')]);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropColumn(['qty', 'id_satuan', 'harga_satuan']);
            });
        }
    }
};
