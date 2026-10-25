<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Item Budget Plan punya kolom Hari (2026-10-26): total item = harga_satuan × qty × hari.
 * Default di UI mengikuti rentang tanggal plan tapi bisa diubah per item. `nominal_budget` tetap
 * TOTAL item. Data lama: hari 1, jadi total lama tidak berubah.
 */
return new class extends Migration
{
    private array $tables = ['wo_budget_items', 'fwo_budget_items', 'sq_wo_budget_items', 'sq_fwo_budget_items'];

    public function up(): void
    {
        foreach ($this->tables as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->decimal('hari', 8, 2)->default(1)->after('nominal_budget');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropColumn('hari');
            });
        }
    }
};
