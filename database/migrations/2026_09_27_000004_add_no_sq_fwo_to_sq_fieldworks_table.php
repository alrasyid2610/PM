<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nomor identitas SQ FWO (format `SQFWO-YY-NNNN`, unique) — belum ada di
 * migration awal `sq_fieldworks`. Pola sama seperti `sq_work_orders.no_sq_wo`.
 * Data SQ sudah kosong (di-truncate 2026-09-27) jadi unique index aman
 * ditambahkan langsung tanpa backfill.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sq_fieldworks', function (Blueprint $table) {
            $table->string('no_sq_fwo', 50)->nullable()->unique()->after('id_sq_fwo');
        });
    }

    public function down(): void
    {
        Schema::table('sq_fieldworks', function (Blueprint $table) {
            $table->dropColumn('no_sq_fwo');
        });
    }
};
