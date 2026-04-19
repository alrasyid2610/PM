<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    Schema::create('cabangs', function (Blueprint $table) {
        $table->id();
        $table->string('nama');
        $table->foreignId('pusat_id')->constrained('pusats');
        $table->string('alamat')->nullable();
        $table->timestamps();
    });
}
