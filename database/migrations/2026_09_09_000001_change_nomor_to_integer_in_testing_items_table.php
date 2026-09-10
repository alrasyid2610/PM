<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `testing_items.nomor` sebelumnya varchar(50) — menyebabkan ORDER BY
 * mengurutkan sebagai teks (1,10,11,...,19,2,20,... bukan 1,2,3,...), yang
 * ujung-ujungnya bikin urutan Testing Item ke-reshuffle permanen tiap kali
 * disimpan (lihat Modules/Testing Points.md untuk analisis lengkap).
 * Semua nilai yang ada sudah dicek murni numerik (393 baris, 0 non-numeric,
 * 0 null) — aman dikonversi ke integer.
 *
 * Pakai DB::statement() (bukan Schema::table()->change()) supaya tidak perlu
 * tambah dependency doctrine/dbal yang belum ter-install di project ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE testing_items MODIFY nomor INT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE testing_items MODIFY nomor VARCHAR(50) NULL');
    }
};
