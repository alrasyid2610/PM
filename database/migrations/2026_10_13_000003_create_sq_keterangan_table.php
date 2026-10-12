<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Keterangan penawaran SQ yang terstruktur: tiap baris = 1 poin dari master
 * `poin_keterangan` + nilai isian (JSON {kunci: nilai}; angka = bilangan,
 * rekening = id_bank_account, personel = id_personnel).
 * `teks_final` = teks hasil render yang dikunci saat SQ difinalkan, supaya
 * perubahan master/rekening/personel sesudahnya tidak mengubah penawaran terbit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sq_keterangan', function (Blueprint $table) {
            $table->increments('id_sq_keterangan');
            $table->unsignedInteger('id_sq');
            $table->unsignedInteger('id_poin');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->json('nilai')->nullable();
            $table->text('teks_final')->nullable();
            $table->timestamps();

            $table->index('id_sq');
            $table->index('id_poin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sq_keterangan');
    }
};
