<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Discount SQ bisa diisi dalam persen ATAU Rupiah (tab Informasi detail SQ), sama seperti Discount SO.
 *  - discount_persen : persen (nullable), decimal(7,4). Sumber utama bila diisi.
 *  - discount        : Rupiah (sudah ada).
 * SQ lama yang hanya punya `discount` (Rupiah) tetap berlaku apa adanya (discount_persen = NULL).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_quotations', function (Blueprint $table) {
            $table->decimal('discount_persen', 7, 4)->nullable()->after('discount');
        });
    }

    public function down(): void
    {
        Schema::table('sales_quotations', function (Blueprint $table) {
            $table->dropColumn('discount_persen');
        });
    }
};
