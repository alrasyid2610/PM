<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom baru untuk proses Lab Sample Reg (penerimaan sample oleh lab) —
 * terpisah dari proses sampling lapangan (kolom `status` yang sudah ada).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_samples', function (Blueprint $table) {
            $table->unsignedInteger('id_wo_lab_sample_reg')->nullable()->after('id_boq');
            $table->string('no_reg_lab', 30)->nullable()->after('no_sample');
            $table->enum('status_lab', ['diterima'])->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('lab_samples', function (Blueprint $table) {
            $table->dropColumn(['id_wo_lab_sample_reg', 'no_reg_lab', 'status_lab']);
        });
    }
};
