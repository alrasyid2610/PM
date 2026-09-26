<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permission untuk menu baru 'sq-work-orders' — modul mandiri (menu, halaman,
 * DataTable sendiri) mengikuti pola Work Order asli di bawah Sales Order.
 * Di-seed dari 'sales-quotations' (induknya) karena dipakai orang yang sama.
 * BOQ/BOQ Other/BOQ Sampling/Budget di dalamnya TIDAK dapat slug sendiri —
 * dipetakan ke 'sq-work-orders' lewat CheckMenuPermission::SLUG_MAP, pola
 * sama seperti 'boq'/'wo-budgets' dipetakan ke 'work-orders' (menghindari
 * pola 'wo-boq-other' yang pernah menyebabkan bug lupa didaftarkan menu).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('menu_group_permissions')
            ->select('menu_group_id', 'can_read', 'can_create', 'can_update', 'can_delete')
            ->where('menu_slug', 'sales-quotations')
            ->get()
            ->each(function ($row) {
                DB::table('menu_group_permissions')->updateOrInsert(
                    ['menu_group_id' => $row->menu_group_id, 'menu_slug' => 'sq-work-orders'],
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
            ->where('menu_slug', 'sales-quotations')
            ->get()
            ->each(function ($row) {
                DB::table('user_menu_permissions')->updateOrInsert(
                    ['user_id' => $row->user_id, 'menu_slug' => 'sq-work-orders'],
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
        DB::table('menu_group_permissions')->where('menu_slug', 'sq-work-orders')->delete();
        DB::table('user_menu_permissions')->where('menu_slug', 'sq-work-orders')->delete();
    }
};
