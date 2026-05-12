<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('termin', function (Blueprint $table) {
            $table->increments('id_termin');
            $table->string('nomor', 50)->unique();
            $table->string('nama', 255);
            $table->decimal('persentase', 5, 2)->default(0);
            $table->decimal('nilai', 18, 2)->default(0);
            $table->date('tanggal');
            $table->enum('status', ['pending', 'proses', 'selesai'])->default('pending');
            $table->text('keterangan')->nullable();
            $table->json('attachment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('termin');
    }
};
