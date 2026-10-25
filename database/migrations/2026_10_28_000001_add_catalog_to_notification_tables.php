<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master event notifikasi (kurasi): daftar event yang bisa dipilih admin didefinisikan di
 * App\Support\NotificationCatalog. Tabel notification_events hanya menyimpan event yang sudah
 * "ditambahkan" admin (is_enabled = 1). Rule dapat memakai penerima "Peran dari data"
 * (recipient_type = 'roles', pilihan peran di recipient_roles).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_events', function (Blueprint $table) {
            $table->string('module', 50)->nullable()->after('description');
            $table->json('available_roles')->nullable()->after('available_fields');
            $table->boolean('is_enabled')->default(false)->after('is_active');
        });

        Schema::table('notification_rules', function (Blueprint $table) {
            $table->json('recipient_roles')->nullable()->after('permission_slug');
        });

        DB::statement("ALTER TABLE notification_rules MODIFY recipient_type ENUM('all','permission','users','roles') NOT NULL DEFAULT 'permission'");

        // Event yang sudah ada (so.created) tetap berjalan: otomatis dianggap sudah ditambahkan.
        DB::table('notification_events')->update(['is_enabled' => 1]);
    }

    public function down(): void
    {
        DB::table('notification_rules')->where('recipient_type', 'roles')->delete();
        DB::statement("ALTER TABLE notification_rules MODIFY recipient_type ENUM('all','permission','users') NOT NULL DEFAULT 'permission'");

        Schema::table('notification_rules', function (Blueprint $table) {
            $table->dropColumn('recipient_roles');
        });

        Schema::table('notification_events', function (Blueprint $table) {
            $table->dropColumn(['module', 'available_roles', 'is_enabled']);
        });
    }
};
