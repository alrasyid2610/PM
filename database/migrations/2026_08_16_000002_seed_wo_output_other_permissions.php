<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permission untuk "Output Other" (tab baru di WO) mengikuti pola master
 * data yang sudah dipakai di BOQ Other/Sampling — slug sendiri
 * (wo-output-other), TIDAK di-map ke permission 'work-orders' lewat
 * SLUG_MAP, TIDAK didaftarkan di config/menus.php. Disalin dari 2 sumber
 * (menu_group_permissions & user_menu_permissions) untuk slug 'work-orders',
 * pola sama dengan migration 2026_08_15_000001.
 */
return new class extends Migration
{
    public function up(): void
    {
        $slug = 'wo-output-other';

        DB::table('menu_group_permissions')
            ->select('menu_group_id', 'can_read', 'can_create', 'can_update', 'can_delete')
            ->where('menu_slug', 'work-orders')
            ->get()
            ->each(function ($row) use ($slug) {
                DB::table('menu_group_permissions')->insertOrIgnore([
                    'menu_group_id' => $row->menu_group_id,
                    'menu_slug'     => $slug,
                    'can_read'      => $row->can_read,
                    'can_create'    => $row->can_create,
                    'can_update'    => $row->can_update,
                    'can_delete'    => $row->can_delete,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            });

        DB::table('user_menu_permissions')
            ->select('user_id', 'can_read', 'can_create', 'can_update', 'can_delete')
            ->where('menu_slug', 'work-orders')
            ->get()
            ->each(function ($row) use ($slug) {
                DB::table('user_menu_permissions')->insertOrIgnore([
                    'user_id'    => $row->user_id,
                    'menu_slug'  => $slug,
                    'can_read'   => $row->can_read,
                    'can_create' => $row->can_create,
                    'can_update' => $row->can_update,
                    'can_delete' => $row->can_delete,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        DB::table('menu_group_permissions')->where('menu_slug', 'wo-output-other')->delete();
        DB::table('user_menu_permissions')->where('menu_slug', 'wo-output-other')->delete();
    }
};
