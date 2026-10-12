<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master "Alasan Tidak Terlaksana" — alasan sebuah titik sample FWO tidak bisa
 * diambil (hujan, mesin mati, dll). Dipilih dari master (bukan teks bebas) supaya
 * bisa dianalisis; konteks tambahan tetap lewat kolom keterangan sample.
 * `wajib_keterangan`: kalau 1, keterangan sample wajib diisi saat alasan ini dipilih
 * (dipakai untuk "Lainnya"). Alasan dinonaktifkan (`is_aktif`), bukan dihapus,
 * supaya data lama tetap terbaca.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alasan_tidak_terlaksana', function (Blueprint $table) {
            $table->increments('id_alasan');
            $table->string('nama', 100);
            $table->boolean('wajib_keterangan')->default(false);
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        $now = now();
        DB::table('alasan_tidak_terlaksana')->insert([
            ['nama' => 'Cuaca',                   'wajib_keterangan' => 0, 'is_aktif' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Alat/mesin rusak',        'wajib_keterangan' => 0, 'is_aktif' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Akses tidak diizinkan',   'wajib_keterangan' => 0, 'is_aktif' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Lainnya',                 'wajib_keterangan' => 1, 'is_aktif' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('alasan_tidak_terlaksana');
    }
};
