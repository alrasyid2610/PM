<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Daftar nama field data ({{key}}) yang tersedia untuk event ini — dicatat
        // otomatis oleh NotificationDispatcher tiap kali event dipicu (union dari
        // array_keys($data) yang pernah dikirim), supaya admin tidak perlu menebak
        // placeholder apa saja yang bisa dipakai di template pesan.
        Schema::table('notification_events', function (Blueprint $table) {
            $table->json('available_fields')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_events', function (Blueprint $table) {
            $table->dropColumn('available_fields');
        });
    }
};
