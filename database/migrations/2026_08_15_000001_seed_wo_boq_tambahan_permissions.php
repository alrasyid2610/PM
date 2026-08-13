<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Versi WO dari migration 2026_08_14_000002 + 2026_08_14_000003 — permission
 * untuk "BOQ Other" & "BOQ Sampling" (tab baru di WO) mengikuti pola master
 * data — slug sendiri (wo-boq-other, wo-boq-sampling), TIDAK di-map ke
 * permission 'work-orders' lewat SLUG_MAP, dan TIDAK didaftarkan di
 * config/menus.php (murni tab di dalam WO, bukan halaman list tersendiri).
 *
 * Disalin dari 2 sumber sekaligus (pelajaran dari implementasi FWO): baik
 * `menu_group_permissions` (permission per grup menu) maupun
 * `user_menu_permissions` (permission individual per user) — supaya user
 * yang sudah bisa akses WO (slug 'work-orders') di salah satu mekanisme
 * otomatis dapat akses ini juga tanpa setup manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['wo-boq-other', 'wo-boq-sampling'] as $slug) {
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
    }

    public function down(): void
    {
        DB::table('menu_group_permissions')->whereIn('menu_slug', ['wo-boq-other', 'wo-boq-sampling'])->delete();
        DB::table('user_menu_permissions')->whereIn('menu_slug', ['wo-boq-other', 'wo-boq-sampling'])->delete();
    }
};
