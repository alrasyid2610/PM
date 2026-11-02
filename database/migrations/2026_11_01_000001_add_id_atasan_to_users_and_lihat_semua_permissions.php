<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Segregasi data SO & SQ.
 * - users.id_atasan: atasan langsung (self-reference) untuk hierarki "atasan melihat data bawahan".
 * - Aksi Khusus `so-lihat-semua` & `sq-lihat-semua`: pemegangnya melihat semua SO/SQ. Supaya tampilan
 *   semua orang TIDAK berubah saat dirilis, hak diberikan ke semua user & grup; pembatasan berlaku
 *   setelah admin mencabutnya (mis. dari grup PIC).
 */
return new class extends Migration
{
    private array $slugs = ['so-lihat-semua', 'sq-lihat-semua'];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id_atasan')->nullable()->after('menu_group_id')->index();
        });

        $now = now();
        $row = fn () => ['can_read' => 0, 'can_create' => 0, 'can_update' => 1, 'can_delete' => 0, 'created_at' => $now, 'updated_at' => $now];

        foreach ($this->slugs as $slug) {
            foreach (DB::table('users')->pluck('id') as $userId) {
                DB::table('user_menu_permissions')->updateOrInsert(['user_id' => $userId, 'menu_slug' => $slug], $row());
            }
            foreach (DB::table('menu_groups')->pluck('id') as $groupId) {
                DB::table('menu_group_permissions')->updateOrInsert(['menu_group_id' => $groupId, 'menu_slug' => $slug], $row());
            }
        }

        foreach (DB::table('users')->pluck('id') as $userId) {
            Cache::forget("user_perms_{$userId}");
        }
    }

    public function down(): void
    {
        DB::table('user_menu_permissions')->whereIn('menu_slug', $this->slugs)->delete();
        DB::table('menu_group_permissions')->whereIn('menu_slug', $this->slugs)->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['id_atasan']);
            $table->dropColumn('id_atasan');
        });

        foreach (DB::table('users')->pluck('id') as $userId) {
            Cache::forget("user_perms_{$userId}");
        }
    }
};
