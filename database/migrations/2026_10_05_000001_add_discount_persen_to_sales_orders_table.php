<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Discount SO bisa diisi dalam persen ATAU Rupiah. Yang disimpan:
 *  - discount_persen : persen (nullable). Sumber utama bila diisi.
 *  - discount        : Rupiah (sudah ada). Dihitung dari persen × subtotal semua BOQ.
 * Kalau subtotal belum ada (SO baru, BOQ belum diisi) discount = 0 dan
 * nilai Rupiah dihitung ulang saat ditampilkan / dicetak.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->decimal('discount_persen', 7, 4)->nullable()->after('discount');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('discount_persen');
        });
    }
};
