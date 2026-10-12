<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Nilai default (Rp) saat account ini dipilih di baris Budget Item
        // (FWO Budget, Budget WO, SQ WO Budget, SQ FWO Budget) — tetap bisa
        // diedit manual per baris, cuma dipakai sebagai nilai awal.
        Schema::table('budget_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('nilai_default')->nullable()->after('kode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_accounts', function (Blueprint $table) {
            $table->dropColumn('nilai_default');
        });
    }
};
