<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penanda "Rekening PPN": rekening yang dipakai untuk pembayaran PPN.
     * Berbeda dari is_utama, boleh lebih dari satu rekening.
     */
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->boolean('is_ppn')->default(false)->after('is_utama');
        });
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn('is_ppn');
        });
    }
};
