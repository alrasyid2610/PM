<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permission untuk menu baru 'sq-fieldworks' — modul mandiri (Fase 3),
 * mengikuti pola persis seed 'sq-work-orders' dari 'sales-quotations'.
 * Di-seed dari 'sq-work-orders' (induknya) karena dipakai orang yang sama.
 * BOQ/Budget di dalamnya dipetakan ke 'sq-fieldworks' lewat
 * CheckMenuPermission::SLUG_MAP, bukan slug sendiri.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('menu_group_permissions')
            ->select('menu_group_id', 'can_read', 'can_create', 'can_update', 'can_delete')
            ->where('menu_slug', 'sq-work-orders')
            ->get()
            ->each(function ($row) {
                DB::table('menu_group_permissions')->updateOrInsert(
                    ['menu_group_id' => $row->menu_group_id, 'menu_slug' => 'sq-fieldworks'],
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
            ->where('menu_slug', 'sq-work-orders')
            ->get()
            ->each(function ($row) {
                DB::table('user_menu_permissions')->updateOrInsert(
                    ['user_id' => $row->user_id, 'menu_slug' => 'sq-fieldworks'],
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
        DB::table('menu_group_permissions')->where('menu_slug', 'sq-fieldworks')->delete();
        DB::table('user_menu_permissions')->where('menu_slug', 'sq-fieldworks')->delete();
    }
};
