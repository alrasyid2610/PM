<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_create_kantors_table.php
public function up(): void
{
    Schema::create('kantors', function (Blueprint $table) {
        $table->id();
        $table->string('nama');
        $table->enum('tipe', ['pusat', 'cabang']);
        $table->string('alamat')->nullable();
        $table->timestamps();
    });
}
};
