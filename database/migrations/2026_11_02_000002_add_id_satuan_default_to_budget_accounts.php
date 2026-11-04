<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unit Default pada Budget Account (2026-11-02): satuan (master `satuan`) yang otomatis terisi di kolom Unit
 * baris Budget Item saat account ini dipilih di Budget Plan SQ WO, SQ FWO, WO, FWO — pasangan Nilai Default.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_accounts', function (Blueprint $table) {
            $table->unsignedInteger('id_satuan_default')->nullable()->after('nilai_default');
        });
    }

    public function down(): void
    {
        Schema::table('budget_accounts', function (Blueprint $table) {
            $table->dropColumn('id_satuan_default');
        });
    }
};
