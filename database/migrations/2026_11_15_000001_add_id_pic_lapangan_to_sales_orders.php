<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PIC Lapangan pada Data Pelanggan SO (satu PIC, opsional).
 * Merujuk ke business_relation_contacts.id_contact (dijaga di aplikasi, tanpa FK keras
 * seperti kolom id_pic_pelanggan*). SO lama = NULL, tidak ada yang perlu diperbaiki.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pic_lapangan')->nullable()->after('id_pic_pelanggan_payment');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('id_pic_lapangan');
        });
    }
};
