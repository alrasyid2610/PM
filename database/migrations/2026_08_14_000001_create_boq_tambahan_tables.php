<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "BOQ Others" & "BOQ Sampling" dari PRD (lihat Project Reference bagian
 * "PRD — Struktur Modul Lab Testing") — item tagihan di luar BoQ Lab Testing
 * yang tidak terikat testing_points/testing_items, misal "Penyusunan
 * Dokumen". Struktur "Others" dan "Sampling" identik, dibedakan lewat kolom
 * `jenis` (dikonfirmasi user: "sama seperti BOQ Others, kategori berbeda
 * saja") — jadi 1 tabel per level, bukan 4 tabel terpisah per kategori.
 *
 * Tabel WO (`boq_tambahan`) dan FWO (`fwo_boq_tambahan`) sengaja dipisah dan
 * independen satu sama lain (tidak ada FK id_boq_tambahan di fwo_boq_tambahan)
 * — mengikuti pola yang sudah disepakati sebelumnya untuk Budget WO vs FWO
 * Budget, dan Lab Sample WO-langsung vs Sample FWO: WO dan FWO masing-masing
 * punya datanya sendiri, tidak saling alokasi/mengurangi qty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boq_tambahan', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->increments('id_boq_tambahan');
            $table->integer('id_wo');
            $table->enum('jenis', ['lainnya', 'sampling'])->default('lainnya');
            $table->string('nama_item', 255);
            $table->integer('qty')->default(0);
            $table->unsignedInteger('id_satuan')->nullable();
            $table->integer('harga')->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('fwo_boq_tambahan', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->increments('id_fwo_boq_tambahan');
            $table->integer('id_fwo');
            $table->enum('jenis', ['lainnya', 'sampling'])->default('lainnya');
            $table->string('nama_item', 255);
            $table->integer('qty')->default(0);
            $table->unsignedInteger('id_satuan')->nullable();
            $table->integer('harga')->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fwo_boq_tambahan');
        Schema::dropIfExists('boq_tambahan');
    }
};
