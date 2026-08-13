<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permission untuk "BOQ Other" & "BOQ Sampling" (tab baru di FWO) mengikuti
 * pola master data — slug sendiri (fwo-boq-other, fwo-boq-sampling), TIDAK
 * di-map ke permission 'fieldworks' lewat SLUG_MAP (dikonfirmasi user,
 * 2026-08-13). Kedua slug ini sengaja TIDAK didaftarkan di config/menus.php
 * (bukan halaman list tersendiri, murni tab di dalam FWO) — juga dikonfirmasi
 * user — jadi tidak bisa diatur lewat halaman Grup Menu, permission-nya
 * di-seed manual di sini dengan menyalin dari permission 'fieldworks' yang
 * sudah ada supaya user yang sudah punya akses FWO otomatis dapat akses ini
 * juga tanpa setup manual (pola sama seperti migration 2026_08_10_000002
 * yang menyalin dari 'termin').
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['fwo-boq-other', 'fwo-boq-sampling'] as $slug) {
            DB::table('menu_group_permissions')
                ->select('menu_group_id', 'can_read', 'can_create', 'can_update', 'can_delete')
                ->where('menu_slug', 'fieldworks')
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
        }
    }

    public function down(): void
    {
        DB::table('menu_group_permissions')->whereIn('menu_slug', ['fwo-boq-other', 'fwo-boq-sampling'])->delete();
    }
};
