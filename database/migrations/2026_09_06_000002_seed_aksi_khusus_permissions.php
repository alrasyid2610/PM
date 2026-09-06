<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed permission untuk slug "Aksi Khusus" (Selesaikan FWO/WO, Verifikasi
 * Budget WO/FWO, Siap Output Pekerjaan, Siap Kirim/Selesaikan Termin) —
 * ditambahkan 2026-09-06 supaya aksi-aksi granular ini punya izin sendiri,
 * terpisah dari permission CRUD menu induknya.
 *
 * Sebelum ini, aksi-aksi tersebut numpang di izin `can_create` milik menu
 * induk (lewat SLUG_MAP di CheckMenuPermission, karena semua route-nya
 * POST). Supaya tidak ada user yang mendadak ke-block begitu permission ini
 * dipisah, di-seed default = disalin dari `can_create` menu induknya —
 * disimpan HANYA di kolom `can_update` (satu-satunya kolom yang benar-benar
 * dicek untuk slug aksi, lihat userCan($slug, 'can_update') di controller).
 * Setelah ini jalan, admin bisa mulai membatasi manual dari halaman Grup
 * Menu / User Management, grup "Aksi Khusus".
 */
return new class extends Migration
{
    private array $map = [
        'fwo-complete'          => 'fieldworks',
        'wo-complete'           => 'work-orders',
        'wo-budget-verify'      => 'work-orders',
        'fwo-budget-verify'     => 'fieldworks',
        'output-pekerjaan-siap' => 'work-orders',
        'termin-siap-kirim'     => 'termin',
        'termin-selesai'        => 'termin',
    ];

    public function up(): void
    {
        foreach ($this->map as $actionSlug => $parentSlug) {
            // menu_group_permissions
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

            // user_menu_permissions (override per-user)
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
