<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Sales Quotation (SQ) — Fase 1. Lihat Obsidian
 * Modules/Sales Quotation.md untuk detail keputusan desain lengkap.
 *
 * Pohon SQ adalah versi ESTIMASI dari SO (hari ke-N, bukan tanggal; tanpa
 * realisasi/personel/termin/output). FK asli dipakai HANYA di dalam pohon
 * SQ (cascade) — ke tabel master (BR, testing point/item, satuan, account,
 * users, office) sengaja TANPA FK, mengikuti pola project ini + PK master
 * tidak seragam tipenya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_quotations', function (Blueprint $table) {
            $table->id('id_sq');
            $table->string('no_sq', 50);
            $table->unsignedSmallInteger('revisi')->default(0);
            $table->unsignedBigInteger('id_sq_induk')->nullable();
            $table->boolean('is_latest')->default(true);

            $table->date('tanggal_sq')->nullable();
            $table->date('berlaku_sampai')->nullable();
            $table->string('judul_order', 255)->nullable();

            // Data Pemesan/Delivery/Payment — Trait HasOrderPartyFields
            $table->integer('id_pelanggan')->nullable();
            $table->integer('id_site_pelanggan')->nullable();
            $table->integer('id_pic_pelanggan')->nullable();
            $table->integer('id_pelanggan_delivery')->nullable();
            $table->integer('id_site_pelanggan_delivery')->nullable();
            $table->integer('id_pic_pelanggan_delivery')->nullable();
            $table->integer('id_pelanggan_payment')->nullable();
            $table->integer('id_site_pelanggan_payment')->nullable();
            $table->integer('id_pic_pelanggan_payment')->nullable();

            $table->unsignedInteger('pic_input')->nullable();
            $table->unsignedInteger('pic_marketing_internal')->nullable();
            $table->unsignedInteger('pic_marketing_eksternal')->nullable();
            $table->integer('id_office')->nullable();

            $table->date('rencana_mulai')->nullable();
            $table->unsignedBigInteger('discount')->default(0);
            $table->text('cara_pembayaran')->nullable();
            $table->text('keterangan')->nullable();

            $table->enum('status', ['draft', 'terkirim', 'diterima', 'ditolak', 'cancel', 'expired'])->default('draft');
            $table->text('keterangan_status')->nullable();
            $table->timestamp('terkirim_at')->nullable();
            $table->timestamp('diputuskan_at')->nullable();

            $table->json('attachment')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_sq_induk')->references('id_sq')->on('sales_quotations')->cascadeOnDelete();
            $table->unique(['no_sq', 'revisi']);
            $table->index(['no_sq', 'is_latest']);
            $table->index('status');
            $table->index('id_pelanggan');
        });

        Schema::create('sq_work_orders', function (Blueprint $table) {
            $table->id('id_sq_wo');
            $table->foreignId('id_sq')->constrained('sales_quotations', 'id_sq')->cascadeOnDelete();
            $table->unsignedSmallInteger('urutan')->default(1);
            $table->string('judul_pekerjaan', 255)->nullable();
            $table->integer('id_pelanggan_pekerjaan')->nullable();
            $table->integer('id_site_pelanggan_pekerjaan')->nullable();
            $table->integer('id_pic_pelanggan_pekerjaan')->nullable();
            $table->unsignedSmallInteger('hari_mulai')->default(1);
            $table->unsignedSmallInteger('durasi_hari')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('sq_boq', function (Blueprint $table) {
            $table->id('id_sq_boq');
            $table->foreignId('id_sq_wo')->constrained('sq_work_orders', 'id_sq_wo')->cascadeOnDelete();
            $table->integer('id_testing_point')->nullable();
            $table->string('item_produk_alternate', 500)->nullable();
            $table->integer('qty')->default(0);
            $table->unsignedInteger('id_satuan')->nullable();
            $table->unsignedBigInteger('harga')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['id_sq_wo', 'id_testing_point']);
        });

        Schema::create('sq_boq_items', function (Blueprint $table) {
            $table->id('id_sq_boq_item');
            $table->foreignId('id_sq_boq')->constrained('sq_boq', 'id_sq_boq')->cascadeOnDelete();
            $table->integer('id_testing_item')->nullable();
            $table->timestamps();

            $table->unique(['id_sq_boq', 'id_testing_item']);
        });

        Schema::create('sq_boq_tambahan', function (Blueprint $table) {
            $table->id('id_sq_boq_tambahan');
            $table->foreignId('id_sq_wo')->constrained('sq_work_orders', 'id_sq_wo')->cascadeOnDelete();
            $table->enum('jenis', ['lainnya', 'sampling'])->default('lainnya');
            $table->string('nama_item', 255);
            $table->integer('qty')->default(0);
            $table->unsignedInteger('id_satuan')->nullable();
            $table->unsignedBigInteger('harga')->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('sq_fieldworks', function (Blueprint $table) {
            $table->id('id_sq_fwo');
            $table->foreignId('id_sq_wo')->constrained('sq_work_orders', 'id_sq_wo')->cascadeOnDelete();
            $table->unsignedSmallInteger('urutan')->default(1);
            $table->string('judul_pekerjaan', 500)->nullable();
            $table->integer('id_site_pelanggan_pekerjaan')->nullable();
            $table->integer('id_pic_pelanggan_pekerjaan')->nullable();
            $table->unsignedSmallInteger('hari_ke')->default(1);
            $table->unsignedSmallInteger('durasi_hari')->default(1);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('sq_fieldwork_boq', function (Blueprint $table) {
            $table->id('id_sq_fwo_boq');
            $table->foreignId('id_sq_fwo')->constrained('sq_fieldworks', 'id_sq_fwo')->cascadeOnDelete();
            $table->foreignId('id_sq_boq')->constrained('sq_boq', 'id_sq_boq')->cascadeOnDelete();
            $table->integer('qty')->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('sq_fieldwork_boq_items', function (Blueprint $table) {
            $table->id('id_sq_fwo_boq_item');
            $table->foreignId('id_sq_fwo_boq')->constrained('sq_fieldwork_boq', 'id_sq_fwo_boq')->cascadeOnDelete();
            $table->integer('id_testing_item')->nullable();
            $table->timestamps();

            $table->unique(['id_sq_fwo_boq', 'id_testing_item']);
        });

        Schema::create('sq_wo_budgets', function (Blueprint $table) {
            $table->id('id_sq_budget');
            $table->foreignId('id_sq_wo')->constrained('sq_work_orders', 'id_sq_wo')->cascadeOnDelete();
            $table->string('label', 255);
            $table->text('keterangan')->nullable();
            $table->unsignedSmallInteger('hari_mulai')->nullable();
            $table->unsignedSmallInteger('hari_selesai')->nullable();
            $table->timestamps();
        });

        Schema::create('sq_wo_budget_items', function (Blueprint $table) {
            $table->id('id_sq_budget_item');
            $table->foreignId('id_sq_budget')->constrained('sq_wo_budgets', 'id_sq_budget')->cascadeOnDelete();
            $table->unsignedInteger('id_account')->nullable();
            $table->unsignedBigInteger('nominal_budget')->default(0);
            $table->text('keterangan')->nullable();
            $table->boolean('is_cash_advance')->default(false);
            $table->timestamps();
        });

        Schema::create('sq_fwo_budgets', function (Blueprint $table) {
            $table->id('id_sq_budget');
            $table->foreignId('id_sq_fwo')->constrained('sq_fieldworks', 'id_sq_fwo')->cascadeOnDelete();
            $table->string('label', 255);
            $table->text('keterangan')->nullable();
            $table->unsignedSmallInteger('hari_mulai')->nullable();
            $table->unsignedSmallInteger('hari_selesai')->nullable();
            $table->timestamps();
        });

        Schema::create('sq_fwo_budget_items', function (Blueprint $table) {
            $table->id('id_sq_budget_item');
            $table->foreignId('id_sq_budget')->constrained('sq_fwo_budgets', 'id_sq_budget')->cascadeOnDelete();
            $table->unsignedInteger('id_account')->nullable();
            $table->unsignedBigInteger('nominal_budget')->default(0);
            $table->text('keterangan')->nullable();
            $table->boolean('is_cash_advance')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sq_fwo_budget_items');
        Schema::dropIfExists('sq_fwo_budgets');
        Schema::dropIfExists('sq_wo_budget_items');
        Schema::dropIfExists('sq_wo_budgets');
        Schema::dropIfExists('sq_fieldwork_boq_items');
        Schema::dropIfExists('sq_fieldwork_boq');
        Schema::dropIfExists('sq_fieldworks');
        Schema::dropIfExists('sq_boq_tambahan');
        Schema::dropIfExists('sq_boq_items');
        Schema::dropIfExists('sq_boq');
        Schema::dropIfExists('sq_work_orders');
        Schema::dropIfExists('sales_quotations');
    }
};
