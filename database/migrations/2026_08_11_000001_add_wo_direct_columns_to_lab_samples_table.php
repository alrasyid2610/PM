<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refactor konsep Lab Sample Reg: WO kini bisa langsung punya sample sendiri
 * tanpa perlu FWO lebih dulu. Sample WO-langsung terikat ke boq (BOQ milik WO)
 * lewat id_boq, bukan ke fieldwork_boq. id_fwo/id_fwo_boq dibuat nullable
 * supaya 1 baris lab_samples bisa berasal dari FWO (seperti sebelumnya) ATAU
 * dari WO langsung (id_wo + id_boq terisi, id_fwo/id_fwo_boq null).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_samples', function (Blueprint $table) {
            $table->unsignedInteger('id_wo')->nullable()->after('id_reg');
            $table->integer('id_boq')->nullable()->after('id_wo');
        });

        Schema::table('lab_samples', function (Blueprint $table) {
            $table->integer('id_fwo')->nullable()->change();
            $table->integer('id_fwo_boq')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('lab_samples', function (Blueprint $table) {
            $table->integer('id_fwo')->nullable(false)->change();
            $table->integer('id_fwo_boq')->nullable(false)->change();
        });

        Schema::table('lab_samples', function (Blueprint $table) {
            $table->dropColumn(['id_wo', 'id_boq']);
        });
    }
};
