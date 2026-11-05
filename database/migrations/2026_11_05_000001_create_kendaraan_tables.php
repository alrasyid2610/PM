<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Kendaraan (master data) + penugasan kendaraan di FWO.
 * - kendaraan: milik sendiri / sewa (vendor hanya relevan untuk sewa).
 * - fieldwork_kendaraan: pivot FWO ↔ kendaraan (1 FWO boleh banyak kendaraan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kendaraan', function (Blueprint $table) {
            $table->increments('id_kendaraan');
            $table->string('nama', 150);
            $table->string('no_polisi', 20);
            $table->string('jenis', 30)->default('mobil');
            $table->string('merek', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->unsignedSmallInteger('tahun')->nullable();
            $table->string('warna', 50)->nullable();
            $table->string('kepemilikan', 15)->default('milik_sendiri'); // milik_sendiri | sewa
            $table->string('nama_vendor', 150)->nullable();
            $table->string('kontak_vendor', 100)->nullable();
            $table->date('masa_berlaku_stnk')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('attachment', 1500)->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('no_polisi');
        });

        Schema::create('fieldwork_kendaraan', function (Blueprint $table) {
            $table->increments('id_fwo_kendaraan');
            $table->integer('id_fwo');
            $table->unsignedInteger('id_kendaraan');
            $table->timestamps();

            $table->unique(['id_fwo', 'id_kendaraan']);
            $table->index('id_kendaraan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fieldwork_kendaraan');
        Schema::dropIfExists('kendaraan');
    }
};
