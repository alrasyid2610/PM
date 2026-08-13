<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration 2026_08_14_000002 menyalin permission fwo-boq-other/fwo-boq-sampling
 * dari menu_group_permissions (slug 'fieldworks') — ternyata di environment ini
 * akses FWO dikelola per-user lewat tabel `user_menu_permissions` (semua user
 * punya `menu_group_id` NULL, jadi menu_group_permissions tidak dipakai sama
 * sekali). Akibatnya migration sebelumnya menyalin dari sumber yang kosong.
 *
 * Migration ini melengkapi dengan menyalin dari `user_menu_permissions` juga,
 * supaya user yang sudah punya akses baca/tulis FWO (slug 'fieldworks')
 * otomatis dapat akses ke 2 tab baru ini tanpa perlu diatur manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['fwo-boq-other', 'fwo-boq-sampling'] as $slug) {
            DB::table('user_menu_permissions')
                ->select('user_id', 'can_read', 'can_create', 'can_update', 'can_delete')
                ->where('menu_slug', 'fieldworks')
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
        DB::table('user_menu_permissions')->whereIn('menu_slug', ['fwo-boq-other', 'fwo-boq-sampling'])->delete();
    }
};
