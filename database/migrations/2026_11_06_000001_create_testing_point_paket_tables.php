<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paket Testing Point (2026-11-06): subset Testing Item milik 1 Testing Point (mis. "parameter 20" dari
 * 50 item). Paket hanya menyimpan REFERENSI ke item induk (tidak menyalin), jadi judul/nilai/unit/urutan
 * selalu ikut induk. Dipakai sebagai jalan pintas di modal Tambah Item BOQ (SQ WO & WO).
 * Tanpa FK DB (pola proyek); penghapusan item/Testing Point membersihkan baris paket lewat controller.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testing_point_paket', function (Blueprint $table) {
            $table->increments('id_paket');
            $table->unsignedInteger('id_testing_point');
            $table->string('nama', 255);
            $table->timestamps();

            $table->unique(['id_testing_point', 'nama']);
        });

        Schema::create('testing_point_paket_items', function (Blueprint $table) {
            $table->increments('id_paket_item');
            $table->unsignedInteger('id_paket');
            $table->unsignedInteger('id_testing_item');

            $table->unique(['id_paket', 'id_testing_item']);
            $table->index('id_testing_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testing_point_paket_items');
        Schema::dropIfExists('testing_point_paket');
    }
};
