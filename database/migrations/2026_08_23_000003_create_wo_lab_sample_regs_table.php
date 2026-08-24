<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lab Sample Reg — proses terpisah dari sampling lapangan (tab Sample FWO):
 * mencatat kapan & siapa di lab yang menerima sample. 1 baris = 1 event
 * penerimaan (bisa berisi banyak sample sekaligus lewat lab_samples.id_wo_lab_sample_reg).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wo_lab_sample_regs', function (Blueprint $table) {
            $table->increments('id_wo_lab_sample_reg');
            $table->integer('id_wo'); // referensi ke work_orders.id_wo (query-level, tidak pakai FK constraint — konvensi tabel lain di project ini)
            $table->date('tanggal_diterima');
            $table->unsignedInteger('id_personnel_penerima')->nullable(); // → personnel.id_personnel (query-level)
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wo_lab_sample_regs');
    }
};
