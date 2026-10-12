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
        // Kotak masuk notifikasi — 1 baris per penerima (fan-out saat event dikirim),
        // supaya status baca/hapus murni per user, tidak saling pengaruh.
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('event_key', 100); // denormalized dari notification_events.key
            $table->string('title', 255);
            $table->text('body')->nullable();
            $table->string('url', 500)->nullable(); // tujuan saat notifikasi diklik
            $table->timestamp('read_at')->nullable();
            $table->timestamp('deleted_at')->nullable(); // soft delete: sembunyi dari daftar user ybs saja
            $table->timestamps();
            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
