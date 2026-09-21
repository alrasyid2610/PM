<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Traceability Clone SO — SO hasil clone menyimpan referensi ke SO sumbernya
// (1 langkah ke belakang saja, bukan seluruh rantai clone). Relasi
// query-level saja (tanpa FK constraint sungguhan), konsisten dengan
// konvensi project ini.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->unsignedInteger('id_so_referensi')->nullable()->after('id_sc');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('id_so_referensi');
        });
    }
};
