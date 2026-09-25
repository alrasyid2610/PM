<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permission untuk menu baru 'sales-quotations' — di-seed dari 'sales-orders'
 * (copy penuh CRUD, bukan cuma can_read) karena SQ adalah modul transaksi
 * setara SO yang dipakai orang yang sama (marketing/sales), beda dari pola
 * 'site-directory' yang cuma read-only. Lihat migration
 * 2026_09_07_000001_seed_site_directory_permissions untuk pola serupa.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('menu_group_permissions')
            ->select('menu_group_id', 'can_read', 'can_create', 'can_update', 'can_delete')
            ->where('menu_slug', 'sales-orders')
            ->get()
            ->each(function ($row) {
                DB::table('menu_group_permissions')->updateOrInsert(
                    ['menu_group_id' => $row->menu_group_id, 'menu_slug' => 'sales-quotations'],
                    [
                        'can_read'   => $row->can_read,
                        'can_create' => $row->can_create,
                        'can_update' => $row->can_update,
                        'can_delete' => $row->can_delete,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            });

        DB::table('user_menu_permissions')
            ->select('user_id', 'can_read', 'can_create', 'can_update', 'can_delete')
            ->where('menu_slug', 'sales-orders')
            ->get()
            ->each(function ($row) {
                DB::table('user_menu_permissions')->updateOrInsert(
                    ['user_id' => $row->user_id, 'menu_slug' => 'sales-quotations'],
                    [
                        'can_read'   => $row->can_read,
                        'can_create' => $row->can_create,
                        'can_update' => $row->can_update,
                        'can_delete' => $row->can_delete,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            });
    }

    public function down(): void
    {
        DB::table('menu_group_permissions')->where('menu_slug', 'sales-quotations')->delete();
        DB::table('user_menu_permissions')->where('menu_slug', 'sales-quotations')->delete();
    }
};
