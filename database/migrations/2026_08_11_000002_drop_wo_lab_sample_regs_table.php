<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Lab Sample Reg sebagai entitas/menu terpisah dibatalkan — diganti tab
 * "Sample" langsung di detail WO (persis seperti tab Sample di FWO), tanpa
 * konsep "registrasi/batch". Sample WO-langsung cukup terikat ke id_wo +
 * id_boq (ditambahkan di migration 2026_08_11_000001), tidak perlu id_reg.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('menu_group_permissions')->where('menu_slug', 'wo-lab-sample-reg')->delete();

        Schema::table('lab_samples', function (Blueprint $table) {
            $table->dropColumn('id_reg');
        });

        Schema::dropIfExists('wo_lab_sample_regs');
    }

    public function down(): void
    {
        Schema::create('wo_lab_sample_regs', function (Blueprint $table) {
            $table->increments('id_reg');
            $table->unsignedInteger('id_wo');
            $table->string('no_reg', 20)->unique();
            $table->enum('status', ['open', 'completed'])->default('open');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::table('lab_samples', function (Blueprint $table) {
            $table->unsignedInteger('id_reg')->nullable()->after('id_wo');
        });
    }
};
