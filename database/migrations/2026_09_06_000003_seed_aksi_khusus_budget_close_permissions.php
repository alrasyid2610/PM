<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tambahan slug "Aksi Khusus" — Selesaikan Plan Budget WO/FWO
 * (WoBudgetController::closePlan() / FwoBudgetController::closePlan()) —
 * menyusul migration 2026_09_06_000002. Sama seperti slug aksi lain: default
 * disalin dari `can_create` menu induk supaya tidak ada yang mendadak
 * ke-block begitu permission ini dipisah.
 */
return new class extends Migration
{
    private array $map = [
        'wo-budget-close'  => 'work-orders',
        'fwo-budget-close' => 'fieldworks',
    ];

    public function up(): void
    {
        foreach ($this->map as $actionSlug => $parentSlug) {
            DB::table('menu_group_permissions')
                ->select('menu_group_id', 'can_create')
                ->where('menu_slug', $parentSlug)
                ->get()
                ->each(function ($row) use ($actionSlug) {
                    DB::table('menu_group_permissions')->updateOrInsert(
                        ['menu_group_id' => $row->menu_group_id, 'menu_slug' => $actionSlug],
                        [
                            'can_read'   => 0,
                            'can_create' => 0,
                            'can_update' => $row->can_create,
                            'can_delete' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                });

            DB::table('user_menu_permissions')
                ->select('user_id', 'can_create')
                ->where('menu_slug', $parentSlug)
                ->get()
                ->each(function ($row) use ($actionSlug) {
                    DB::table('user_menu_permissions')->updateOrInsert(
                        ['user_id' => $row->user_id, 'menu_slug' => $actionSlug],
                        [
                            'can_read'   => 0,
                            'can_create' => 0,
                            'can_update' => $row->can_create,
                            'can_delete' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                });
        }
    }

    public function down(): void
    {
        DB::table('menu_group_permissions')->whereIn('menu_slug', array_keys($this->map))->delete();
        DB::table('user_menu_permissions')->whereIn('menu_slug', array_keys($this->map))->delete();
    }
};
