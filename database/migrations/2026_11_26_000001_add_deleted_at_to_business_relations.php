<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hapus Business Relation (soft delete): BR yang dihapus tidak tampil lagi di daftar/pilihan,
 * tapi SO/SQ/WO/Contract lama yang memakainya tetap menampilkan nama pelanggannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('business_relations', 'deleted_at')) return;

        Schema::table('business_relations', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('business_relations', 'deleted_at')) return;

        Schema::table('business_relations', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
