<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Discount per item BOQ bisa diisi dalam persen ATAU Rupiah (pola sama dengan
 * discount SO). Yang disimpan:
 *  - discount_persen : persen dari harga kotor item (qty × harga), nullable.
 *  - discount        : Rupiah (sudah ada) = persen × (qty × harga), dibulatkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boq', function (Blueprint $table) {
            $table->decimal('discount_persen', 7, 4)->nullable()->after('discount');
        });
    }

    public function down(): void
    {
        Schema::table('boq', function (Blueprint $table) {
            $table->dropColumn('discount_persen');
        });
    }
};
