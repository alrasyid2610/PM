<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Konversi boq.satuan dari varchar (teks bebas, dulu dibatasi hardcode
 * PCS/Titik/Set di frontend) menjadi FK int ke tabel master `satuan`.
 *
 * Data lama di-backfill otomatis dengan mencocokkan teks ke nama master
 * (PCS/Titik/Set, lihat migration 2026_08_13_000001). Baris yang teksnya
 * TIDAK cocok persis dengan salah satu dari 3 pilihan hardcode lama akan
 * menghasilkan id_satuan NULL setelah migration ini — perlu dicek manual
 * & diperbaiki lewat form BOQ kalau ada, karena `boq` adalah tabel
 * transaksi aktif (dipakai banyak WO/FWO yang sudah berjalan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boq', function (Blueprint $table) {
            $table->unsignedInteger('id_satuan')->nullable()->after('satuan');
        });

        DB::statement('
            UPDATE boq b
            JOIN satuan s ON s.nama = b.satuan
            SET b.id_satuan = s.id_satuan
        ');

        Schema::table('boq', function (Blueprint $table) {
            $table->dropColumn('satuan');
        });
    }

    public function down(): void
    {
        Schema::table('boq', function (Blueprint $table) {
            $table->string('satuan', 255)->nullable()->after('id_satuan');
        });

        DB::statement('
            UPDATE boq b
            JOIN satuan s ON s.id_satuan = b.id_satuan
            SET b.satuan = s.nama
        ');

        Schema::table('boq', function (Blueprint $table) {
            $table->dropColumn('id_satuan');
        });
    }
};
