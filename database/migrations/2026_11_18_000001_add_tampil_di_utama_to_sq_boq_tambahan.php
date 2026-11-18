<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flag BOQ Other SQ yang dicetak di bagian utama penawaran (Section I, setelah BOQ lab),
     * bukan di "Biaya Sampling". Jenis tetap `lainnya`, jadi semua total tidak berubah.
     */
    public function up(): void
    {
        Schema::table('sq_boq_tambahan', function (Blueprint $table) {
            $table->boolean('tampil_di_utama')->default(false)->after('keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('sq_boq_tambahan', function (Blueprint $table) {
            $table->dropColumn('tampil_di_utama');
        });
    }
};
