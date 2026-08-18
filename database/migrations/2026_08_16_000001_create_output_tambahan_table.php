<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Output Others" dari PRD (lihat Project Reference bagian "PRD — Struktur
 * Modul Lab Testing") — level WO saja, berdiri sendiri (tidak terhubung ke
 * BOQ Other), dikonfirmasi user (2026-08-13/14). Gabungan 2 kebutuhan:
 * baris tagihan (qty/satuan/harga, seperti boq_tambahan) + tracking dokumen
 * (status/attachments/link_drive/tanggal, seperti output_pekerjaan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('output_tambahan', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->increments('id_output_tambahan');
            $table->integer('id_wo');
            $table->string('nama_item', 255);
            $table->integer('qty')->default(0);
            $table->unsignedInteger('id_satuan')->nullable();
            $table->integer('harga')->default(0);
            $table->enum('status', ['belum_siap', 'siap', 'terkirim'])->default('belum_siap');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->json('attachments')->nullable();
            $table->text('link_drive')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('output_tambahan');
    }
};
