<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan perubahan anak (BOQ, Output, Budget, dst.) ikut tampil di History modul induknya
 * (mis. WO). Kolom parent menunjuk record induk: `parent_table` + `parent_id`.
 * Log lama tidak punya parent, jadi hanya muncul di History modul asalnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('parent_table', 100)->nullable()->after('row_id');
            $table->string('parent_id', 50)->nullable()->after('parent_table');
            $table->index(['parent_table', 'parent_id'], 'audit_logs_parent_index');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_parent_index');
            $table->dropColumn(['parent_table', 'parent_id']);
        });
    }
};
