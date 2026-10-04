<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Status Kepegawaian (HL / Kontrak Gol. 1-3 / Magang) di tabel personnel &
 * users. Sengaja varchar + nullable (bukan enum, tidak wajib, data lama
 * dibiarkan kosong). Daftar nilai yang valid ada di
 * App\Support\StatusKepegawaian.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnel', function (Blueprint $table) {
            $table->string('status_kepegawaian', 30)->nullable()->after('is_aktif');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('status_kepegawaian', 30)->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('personnel', function (Blueprint $table) {
            $table->dropColumn('status_kepegawaian');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('status_kepegawaian');
        });
    }
};
