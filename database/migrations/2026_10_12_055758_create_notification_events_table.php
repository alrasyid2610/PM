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
        // Katalog event notifikasi — baris baru otomatis dibuat (self-register) saat
        // sebuah event pertama kali dipicu dari kode (lihat NotificationDispatcher).
        // Admin mengatur label/deskripsi/aktif dari menu "Pengaturan Notifikasi",
        // tanpa perlu developer menyentuh tabel ini secara manual.
        Schema::create('notification_events', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique(); // contoh: 'so.created'
            $table->string('label', 150);          // contoh: 'SO Dibuat'
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_events');
    }
};
