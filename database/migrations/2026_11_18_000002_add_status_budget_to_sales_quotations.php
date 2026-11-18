<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Tandai Budget Selesai" SQ (2026-11-18): PIC Order menandai Budget (WO & FWO, boleh kosong — mis.
 * SQ yang sample-nya diantar langsung ke kantor) sudah beres diisi, supaya pembuat SQ (role pic_input)
 * dapat notifikasi. Wajib sebelum Ajukan Approval (lihat SalesQuotationController::syaratFinalkan()).
 * Reset otomatis ke 'belum' tiap ada perubahan struktural Budget WO/FWO (App\Support\SqBudgetSelesai).
 *
 * TIDAK ada backfill (keputusan user 2026-11-19): SQ lama ikut default kolom ini ('belum'), sama
 * seperti SQ baru — tidak ditandai 'selesai' secara otomatis. SQ lama yang statusnya sudah terkunci
 * (pending/final/win/lose/void) tidak terdampak (syarat ini hanya dicek saat Ajukan Approval); SQ
 * Draft lama yang belum pernah diajukan akan perlu ditandai PIC Order dulu seperti SQ baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_quotations', function (Blueprint $table) {
            $table->string('status_budget', 20)->default('belum')->after('catatan_approval');
            $table->unsignedBigInteger('budget_selesai_oleh')->nullable()->after('status_budget');
            $table->timestamp('budget_selesai_at')->nullable()->after('budget_selesai_oleh');
            $table->index('status_budget');
        });
    }

    public function down(): void
    {
        Schema::table('sales_quotations', function (Blueprint $table) {
            $table->dropIndex(['status_budget']);
            $table->dropColumn(['status_budget', 'budget_selesai_oleh', 'budget_selesai_at']);
        });
    }
};
