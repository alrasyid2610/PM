<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kode Sampling Point (ENV/WE) dilebarkan 50 → 255 karakter (2026-10-19): di lapangan kolom Kode
 * dipakai untuk label deskriptif (mis. "Outlet PT ... Blok J07 No. 0001", 53 karakter) sehingga
 * simpan gagal "kode must not be greater than 50 characters". Hanya melebarkan; data lama aman.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brs_sampling_points', function (Blueprint $table) {
            $table->string('kode', 255)->change();
        });
    }

    public function down(): void
    {
        // Mempersempit kembali bisa memotong kode yang sudah lebih dari 50 karakter — dibiarkan sengaja.
    }
};
