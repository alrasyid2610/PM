<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PIC Lapangan (aktual) pada FWO — opsional. Merujuk business_relation_contacts.id_contact
 * (dijaga di aplikasi, tanpa FK keras, sama seperti id_pic_pelanggan_pekerjaan).
 * Dipakai printout FWO lebih dulu daripada PIC Pelanggan bila terisi. FWO lama = NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fieldworks', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pic_lapangan_aktual')->nullable()->after('id_pic_pelanggan_pekerjaan');
        });
    }

    public function down(): void
    {
        Schema::table('fieldworks', function (Blueprint $table) {
            $table->dropColumn('id_pic_lapangan_aktual');
        });
    }
};
