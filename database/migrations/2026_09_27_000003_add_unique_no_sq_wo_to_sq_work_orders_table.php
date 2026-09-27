<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `no_sq_wo` ternyata bisa dobel (ketemu saat Revisi SQ menyalin baris
 * sq_work_orders apa adanya, termasuk no_sq_wo lama, alih-alih membuatkan
 * nomor baru — bug di SalesQuotationController::revise(), sudah diperbaiki
 * di commit yang sama dengan migration ini). Ditambah unique index di sini
 * supaya DB ikut menahan kalau ada jalur lain yang lolos ke depannya.
 *
 * Data SQ sudah di-truncate sebelum migration ini (2026-09-27), jadi aman
 * langsung tambah unique tanpa perlu bersihkan duplikat dulu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sq_work_orders', function (Blueprint $table) {
            $table->unique('no_sq_wo');
        });
    }

    public function down(): void
    {
        Schema::table('sq_work_orders', function (Blueprint $table) {
            $table->dropUnique(['no_sq_wo']);
        });
    }
};
