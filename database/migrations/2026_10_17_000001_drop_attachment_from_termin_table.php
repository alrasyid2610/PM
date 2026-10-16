<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lampiran (attachment) di modul Termin dihapus (2026-10-17): tidak diperlukan.
 * Daftar path file lama di kolom ini ikut hilang; file fisiknya di storage/app/public/attachments/termin
 * tidak dihapus oleh migration ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('termin', 'attachment')) {
            Schema::table('termin', function (Blueprint $table) {
                $table->dropColumn('attachment');
            });
        }
    }

    public function down(): void
    {
        Schema::table('termin', function (Blueprint $table) {
            $table->json('attachment')->nullable();
        });
    }
};
