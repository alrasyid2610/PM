<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Lab Sample Reg — modul mandiri di level WO (bukan tab), pola alurnya sama
 * seperti Termin menarik Output Pekerjaan dari WO: lab_samples yang sudah ada
 * di FWO-FWO milik WO ini ditarik/di-assign ke dalam 1 "registrasi" (batch),
 * bukan dibuat data baru. Lihat Project Reference bagian "Lab Sample Reg".
 */
return new class extends Migration
{
    public function up(): void
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
            $table->unsignedInteger('id_reg')->nullable()->after('id_fwo_boq');
        });

        // Samakan permission dengan 'termin' untuk semua menu group yang sudah
        // punya akses Termin, supaya menu baru langsung muncul tanpa setup manual.
        DB::table('menu_group_permissions')
            ->select('menu_group_id', 'can_read', 'can_create', 'can_update', 'can_delete')
            ->where('menu_slug', 'termin')
            ->get()
            ->each(function ($row) {
                DB::table('menu_group_permissions')->insertOrIgnore([
                    'menu_group_id' => $row->menu_group_id,
                    'menu_slug'     => 'wo-lab-sample-reg',
                    'can_read'      => $row->can_read,
                    'can_create'    => $row->can_create,
                    'can_update'    => $row->can_update,
                    'can_delete'    => $row->can_delete,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            });
    }

    public function down(): void
    {
        DB::table('menu_group_permissions')->where('menu_slug', 'wo-lab-sample-reg')->delete();
        Schema::table('lab_samples', function (Blueprint $table) {
            $table->dropColumn('id_reg');
        });
        Schema::dropIfExists('wo_lab_sample_regs');
    }
};
