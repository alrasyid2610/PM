<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Aksi Khusus `sembunyikan-nilai` (2026-11-05): pemegangnya TIDAK melihat kartu "Total Nilai" dan "Nilai Budget"
 * di detail SQ, SQ WO, SO, dan WO. Berbentuk "sembunyikan" (bukan "lihat") supaya grup lain & grup baru tetap
 * melihat nilai seperti biasa. Diberikan ke grup "Tim Sampling" (dicari lewat nama; dilewati bila tidak ada).
 */
return new class extends Migration
{
    private string $slug = 'sembunyikan-nilai';
    private string $grup = 'Tim Sampling';

    public function up(): void
    {
        $now = now();
        foreach (DB::table('menu_groups')->where('name', $this->grup)->pluck('id') as $groupId) {
            DB::table('menu_group_permissions')->updateOrInsert(
                ['menu_group_id' => $groupId, 'menu_slug' => $this->slug],
                ['can_read' => 0, 'can_create' => 0, 'can_update' => 1, 'can_delete' => 0, 'created_at' => $now, 'updated_at' => $now]
            );
            foreach (DB::table('users')->where('menu_group_id', $groupId)->pluck('id') as $userId) {
                Cache::forget("user_perms_{$userId}");
            }
        }
    }

    public function down(): void
    {
        $userIds = DB::table('users')
            ->whereIn('menu_group_id', DB::table('menu_group_permissions')->where('menu_slug', $this->slug)->pluck('menu_group_id'))
            ->pluck('id');

        DB::table('menu_group_permissions')->where('menu_slug', $this->slug)->delete();
        DB::table('user_menu_permissions')->where('menu_slug', $this->slug)->delete();

        foreach ($userIds as $userId) {
            Cache::forget("user_perms_{$userId}");
        }
    }
};
