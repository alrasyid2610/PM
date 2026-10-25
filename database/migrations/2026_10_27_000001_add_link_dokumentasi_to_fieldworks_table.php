<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link Dokumentasi FWO (2026-10-27): tautan ke dokumentasi pekerjaan lapangan (mis. folder Drive).
 * Opsional saat FWO dibuat/diedit, tetapi WAJIB terisi sebelum FWO bisa diselesaikan
 * (dicek di FieldworkController::complete()). FWO lama dibiarkan kosong.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fieldworks', function (Blueprint $table) {
            $table->text('link_dokumentasi')->nullable()->after('keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('fieldworks', function (Blueprint $table) {
            $table->dropColumn('link_dokumentasi');
        });
    }
};
