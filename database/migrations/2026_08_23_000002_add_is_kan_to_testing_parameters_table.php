<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testing_parameters', function (Blueprint $table) {
            $table->boolean('is_kan')->default(0)->after('kode');
        });
    }

    public function down(): void
    {
        Schema::table('testing_parameters', function (Blueprint $table) {
            $table->dropColumn('is_kan');
        });
    }
};
