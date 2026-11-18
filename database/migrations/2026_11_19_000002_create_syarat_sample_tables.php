<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Syarat Sample (sejak 2026-11-19): master `syarat_sample` (template berisian, pola Poin Keterangan SQ) dan
 * `testing_point_syarat` (syarat yang berlaku untuk 1 Testing Point beserta nilai isiannya).
 * Dipakai Lab Sample Reg untuk menampilkan syarat sample (cek terpenuhi/tidak menyusul). Syarat per Paket menyusul
 * (kolom id_paket ditambah nanti).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syarat_sample', function (Blueprint $table) {
            $table->id('id_syarat');
            $table->text('template');
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('testing_point_syarat', function (Blueprint $table) {
            $table->id('id_tp_syarat');
            $table->unsignedBigInteger('id_testing_point');
            $table->unsignedBigInteger('id_syarat');
            $table->unsignedInteger('urutan')->default(0);
            $table->json('nilai')->nullable();
            $table->timestamps();

            // 1 syarat master hanya sekali per Testing Point
            $table->unique(['id_testing_point', 'id_syarat'], 'tp_syarat_unique');
            $table->index('id_syarat');
        });

        // Izin menu baru: salin dari Poin Keterangan (grup & user yang boleh mengelola master sejenis)
        foreach ([['menu_group_permissions', 'menu_group_id'], ['user_menu_permissions', 'user_id']] as [$tbl, $col]) {
            DB::table($tbl)
                ->select($col, 'can_read', 'can_create', 'can_update', 'can_delete')
                ->where('menu_slug', 'poin-keterangan')
                ->get()
                ->each(function ($row) use ($tbl, $col) {
                    DB::table($tbl)->updateOrInsert(
                        [$col => $row->$col, 'menu_slug' => 'syarat-sample'],
                        [
                            'can_read' => $row->can_read, 'can_create' => $row->can_create,
                            'can_update' => $row->can_update, 'can_delete' => $row->can_delete,
                            'created_at' => now(), 'updated_at' => now(),
                        ]
                    );
                });
        }
    }

    public function down(): void
    {
        DB::table('menu_group_permissions')->where('menu_slug', 'syarat-sample')->delete();
        DB::table('user_menu_permissions')->where('menu_slug', 'syarat-sample')->delete();
        Schema::dropIfExists('testing_point_syarat');
        Schema::dropIfExists('syarat_sample');
    }
};
