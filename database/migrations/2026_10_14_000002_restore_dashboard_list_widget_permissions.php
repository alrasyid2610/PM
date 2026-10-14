<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Widget daftar (dash-list-*) kembali berdiri sendiri dengan izin sendiri (kartu & daftar terpisah).
 * Supaya tampilan tidak berubah: user/grup yang sudah punya widget kartunya diberi juga widget
 * daftarnya — HANYA bila barisnya belum ada (tidak menimpa izin yang sudah diatur manual).
 */
return new class extends Migration
{
    // kartu → daftar
    private const PAIRS = [
        'dash-so-outstanding'      => 'dash-list-so',
        'dash-wo-outstanding'      => 'dash-list-wo',
        'dash-fwo-outstanding'     => 'dash-list-fwo',
        'dash-termin-outstanding'  => 'dash-list-termin',
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::PAIRS as $kartu => $daftar) {
            foreach (DB::table('user_menu_permissions')->where('menu_slug', $kartu)->where('can_update', 1)->pluck('user_id') as $userId) {
                $ada = DB::table('user_menu_permissions')->where('user_id', $userId)->where('menu_slug', $daftar)->exists();
                if (!$ada) {
                    DB::table('user_menu_permissions')->insert([
                        'user_id' => $userId, 'menu_slug' => $daftar,
                        'can_read' => 1, 'can_create' => 0, 'can_update' => 1, 'can_delete' => 0,
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }
            foreach (DB::table('menu_group_permissions')->where('menu_slug', $kartu)->where('can_update', 1)->pluck('menu_group_id') as $groupId) {
                $ada = DB::table('menu_group_permissions')->where('menu_group_id', $groupId)->where('menu_slug', $daftar)->exists();
                if (!$ada) {
                    DB::table('menu_group_permissions')->insert([
                        'menu_group_id' => $groupId, 'menu_slug' => $daftar,
                        'can_read' => 1, 'can_create' => 0, 'can_update' => 1, 'can_delete' => 0,
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Tidak ada yang dibalik: izin daftar boleh tetap ada.
    }
};
