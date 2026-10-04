<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penyelia FWO — user (users.id) yang menandatangani dokumen FWO di PDF.
 * Nullable, tanpa FK (sama pola kolom referensi user lain di sistem).
 * Default di form Create = user bernama "Saldi" (lihat FieldworkController::create).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fieldworks', function (Blueprint $table) {
            $table->unsignedInteger('id_penyelia')->nullable()->after('id_pic_pelanggan_pekerjaan');
        });
    }

    public function down(): void
    {
        Schema::table('fieldworks', function (Blueprint $table) {
            $table->dropColumn('id_penyelia');
        });
    }
};
