<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard berbasis widget: tiap widget punya slug permission (config/dashboard_widgets.php).
 * Sebelumnya dashboard terbuka & tetap untuk semua user, jadi supaya tampilan semua orang
 * TIDAK berubah saat fitur ini dirilis, semua widget yang ada diberikan ke semua user dan
 * semua grup menu. Setelah itu akses dipersempit lewat Grup Menu / User Management.
 * (Widget baru ke depan tidak otomatis diberikan — dicentang manual.)
 */
return new class extends Migration
{
    private function slugs(): array
    {
        return array_column(require config_path('dashboard_widgets.php'), 'slug');
    }

    public function up(): void
    {
        $now = now();
        $row = fn () => ['can_read' => 1, 'can_create' => 0, 'can_update' => 1, 'can_delete' => 0, 'created_at' => $now, 'updated_at' => $now];

        foreach ($this->slugs() as $slug) {
            foreach (DB::table('users')->pluck('id') as $userId) {
                DB::table('user_menu_permissions')->updateOrInsert(['user_id' => $userId, 'menu_slug' => $slug], $row());
            }
            foreach (DB::table('menu_groups')->pluck('id') as $groupId) {
                DB::table('menu_group_permissions')->updateOrInsert(['menu_group_id' => $groupId, 'menu_slug' => $slug], $row());
            }
        }
    }

    public function down(): void
    {
        DB::table('user_menu_permissions')->whereIn('menu_slug', $this->slugs())->delete();
        DB::table('menu_group_permissions')->whereIn('menu_slug', $this->slugs())->delete();
    }
};
