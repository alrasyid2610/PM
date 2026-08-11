<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Budget & Expenses di level Work Order — independen dari FWO Budget
 * (fwo_budgets/fwo_budget_items/fwo_budget_actuals). Sengaja dibuat sebagai
 * sistem terpisah (bukan perluasan fwo_budgets) karena lock logic-nya beda:
 * WO pakai work_orders.status, FWO pakai fieldworks.status. Lihat Project
 * Reference bagian "Budget WO" untuk detail keputusan arsitektur ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wo_budgets', function (Blueprint $table) {
            $table->increments('id_budget');
            $table->unsignedInteger('id_wo');
            $table->string('label', 255);
            $table->text('keterangan')->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->enum('status', ['open', 'completed'])->default('open');
            $table->string('dokumen_realisasi', 500)->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('wo_budget_items', function (Blueprint $table) {
            $table->increments('id_budget_item');
            $table->unsignedInteger('id_budget');
            $table->unsignedInteger('id_account');
            $table->bigInteger('nominal_budget')->default(0);
            $table->text('keterangan')->nullable();
            $table->boolean('is_cash_advance')->default(0);
            $table->timestamps();
        });

        Schema::create('wo_budget_actuals', function (Blueprint $table) {
            $table->increments('id_actual');
            $table->unsignedInteger('id_budget_item');
            $table->bigInteger('nominal_actual')->default(0);
            $table->text('keterangan')->nullable();
            $table->json('attachments')->nullable();
            $table->enum('status_verifikasi', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->string('catatan_verifikasi', 500)->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wo_budget_actuals');
        Schema::dropIfExists('wo_budget_items');
        Schema::dropIfExists('wo_budgets');
    }
};
