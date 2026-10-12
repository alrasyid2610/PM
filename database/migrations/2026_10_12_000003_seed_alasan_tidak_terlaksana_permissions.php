<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permission menu baru 'alasan-tidak-terlaksana' (master data, grup Referensi).
 * Di-seed dari 'satuan' (master data sejenis) supaya yang sudah mengelola master
 * lain langsung bisa mengelola alasan ini; sisanya diatur manual lewat Grup Menu /
 * User Management.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('menu_group_permissions')
            ->select('menu_group_id', 'can_read', 'can_create', 'can_update', 'can_delete')
            ->where('menu_slug', 'satuan')
            ->get()
            ->each(function ($row) {
                DB::table('menu_group_permissions')->updateOrInsert(
                    ['menu_group_id' => $row->menu_group_id, 'menu_slug' => 'alasan-tidak-terlaksana'],
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
            ->where('menu_slug', 'satuan')
            ->get()
            ->each(function ($row) {
                DB::table('user_menu_permissions')->updateOrInsert(
                    ['user_id' => $row->user_id, 'menu_slug' => 'alasan-tidak-terlaksana'],
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
        DB::table('menu_group_permissions')->where('menu_slug', 'alasan-tidak-terlaksana')->delete();
        DB::table('user_menu_permissions')->where('menu_slug', 'alasan-tidak-terlaksana')->delete();
    }
};
