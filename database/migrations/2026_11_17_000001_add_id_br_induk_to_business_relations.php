<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Holding / induk perusahaan: satu BR boleh punya satu induk (BR lain), bertingkat tanpa batas.
 * Kosong = BR tidak berinduk. Tanpa FK (pola tabel lama); validasi anti-lingkaran ada di controller.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_relations', function (Blueprint $table) {
            $table->unsignedBigInteger('id_br_induk')->nullable()->after('nomor_telepon');
            $table->index('id_br_induk', 'business_relations_id_br_induk_index');
        });
    }

    public function down(): void
    {
        Schema::table('business_relations', function (Blueprint $table) {
            $table->dropIndex('business_relations_id_br_induk_index');
            $table->dropColumn('id_br_induk');
        });
    }
};
