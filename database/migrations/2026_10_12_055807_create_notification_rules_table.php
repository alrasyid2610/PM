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
        // Aturan penerima + template pesan per event. 1 event bisa punya lebih dari
        // 1 rule (misal: broadcast ke semua yang punya akses modul X, DAN ke 1 user
        // tertentu di luar itu) — diatur dari menu "Pengaturan Notifikasi".
        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_event_id')->constrained('notification_events')->cascadeOnDelete();
            $table->enum('recipient_type', ['all', 'permission', 'users'])->default('permission');
            // recipient_type = 'permission' → kirim ke user yang punya can_read pada slug menu ini
            $table->string('permission_slug', 100)->nullable();
            // Placeholder {{key}} diisi dari data event saat dikirim, lihat NotificationDispatcher
            $table->string('message_template', 500);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_rules');
    }
};
