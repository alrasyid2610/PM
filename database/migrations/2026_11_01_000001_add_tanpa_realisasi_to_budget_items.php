<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda "Tanpa realisasi" pada item Budget Plan (WO, FWO, SQ WO, SQ FWO) — mis. alat milik sendiri yang
 * tidak mengeluarkan uang sehingga tidak perlu diisi realisasinya. Ditandai oleh PIC Order SQ/SO terkait
 * (lihat App\Support\BudgetTanpaRealisasi). Data lama: tidak bertanda (default 0).
 */
return new class extends Migration
{
    private const TABEL = ['wo_budget_items', 'fwo_budget_items', 'sq_wo_budget_items', 'sq_fwo_budget_items'];

    public function up(): void
    {
        foreach (self::TABEL as $tabel) {
            if (Schema::hasColumn($tabel, 'tanpa_realisasi')) continue;

            Schema::table($tabel, function (Blueprint $table) {
                $table->boolean('tanpa_realisasi')->default(false)->after('is_cash_advance');
                $table->string('alasan_tanpa_realisasi', 255)->nullable()->after('tanpa_realisasi');
                $table->unsignedBigInteger('ditandai_oleh')->nullable()->after('alasan_tanpa_realisasi'); // → users.id (query-level)
                $table->timestamp('ditandai_at')->nullable()->after('ditandai_oleh');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABEL as $tabel) {
            if (!Schema::hasColumn($tabel, 'tanpa_realisasi')) continue;

            Schema::table($tabel, function (Blueprint $table) {
                $table->dropColumn(['tanpa_realisasi', 'alasan_tanpa_realisasi', 'ditandai_oleh', 'ditandai_at']);
            });
        }
    }
};
