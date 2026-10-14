<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penanda "Rekening Utama": rekening ini otomatis diisikan ke poin keterangan
     * bertipe rekening saat poin ditambahkan di Sales Quotation (tetap bisa diganti).
     * Dijaga maksimal 1 rekening utama di BankAccountController (bukan constraint DB).
     */
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->boolean('is_utama')->default(false)->after('keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn('is_utama');
        });
    }
};
