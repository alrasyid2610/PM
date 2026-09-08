<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permission untuk halaman read-only "Site" (slug 'site-directory') —
 * jalan pintas navigasi lintas Perusahaan, bukan modul CRUD baru. Mengikuti
 * pola master data — slug sendiri, di-seed dari 'business-relations' supaya
 * user yang sudah bisa baca Business Relation otomatis bisa buka halaman
 * ini juga tanpa setup manual (pola sama seperti migration
 * 2026_08_14_000002_seed_fwo_boq_tambahan_permissions).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('menu_group_permissions')
            ->select('menu_group_id', 'can_read')
            ->where('menu_slug', 'business-relations')
            ->get()
            ->each(function ($row) {
                DB::table('menu_group_permissions')->updateOrInsert(
                    ['menu_group_id' => $row->menu_group_id, 'menu_slug' => 'site-directory'],
                    [
                        'can_read'   => $row->can_read,
                        'can_create' => 0,
                        'can_update' => 0,
                        'can_delete' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            });

        DB::table('user_menu_permissions')
            ->select('user_id', 'can_read')
            ->where('menu_slug', 'business-relations')
            ->get()
            ->each(function ($row) {
                DB::table('user_menu_permissions')->updateOrInsert(
                    ['user_id' => $row->user_id, 'menu_slug' => 'site-directory'],
                    [
                        'can_read'   => $row->can_read,
                        'can_create' => 0,
                        'can_update' => 0,
                        'can_delete' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            });
    }

    public function down(): void
    {
        DB::table('menu_group_permissions')->where('menu_slug', 'site-directory')->delete();
        DB::table('user_menu_permissions')->where('menu_slug', 'site-directory')->delete();
    }
};
