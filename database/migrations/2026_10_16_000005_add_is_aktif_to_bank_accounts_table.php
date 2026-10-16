<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Status aktif/nonaktif rekening. Rekening nonaktif tidak muncul di pilihan baru
 * (SQ, rekening utama otomatis), tapi SQ yang sudah memakainya tetap menampilkannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->tinyInteger('is_aktif')->default(1)->after('is_ppn');
        });
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn('is_aktif');
        });
    }
};
