<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Output Other level SO (2026-10-26): item bisa dimiliki SO langsung (tanpa WO), mengikuti pola Output Other WO.
 * `id_so` baru (nullable, index) dan `id_wo` jadi boleh kosong — tepat satu dari keduanya terisi (dijaga di
 * OutputTambahanController::store()). Data lama tidak berubah (semua baris lama tetap punya id_wo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('output_tambahan', function (Blueprint $table) {
            $table->integer('id_wo')->nullable()->change();
            $table->unsignedBigInteger('id_so')->nullable()->after('id_wo')->index();
        });
    }

    public function down(): void
    {
        // id_wo sengaja dibiarkan nullable: baris level SO (id_wo NULL) tidak bisa dikembalikan ke NOT NULL tanpa menghapusnya.
        Schema::table('output_tambahan', function (Blueprint $table) {
            $table->dropIndex(['id_so']);
            $table->dropColumn('id_so');
        });
    }
};
